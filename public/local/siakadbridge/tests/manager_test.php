<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakadbridge;

defined('MOODLE_INTERNAL') || die();

/** Tests for the SIAKAD exam access gate and Moodle access reconciliation. */
final class manager_test extends \advanced_testcase {
    public function test_paid_student_in_selected_prodi_is_allowed(): void {
        $fixture = $this->create_fixture('lunas', 'TI');
        $this->assertTrue(manager::can_user_access_exam(
            $fixture->moodleuserid,
            'TI',
            '2026/2027',
            'genap',
            'UKT'
        ));
    }

    public function test_unpaid_student_is_blocked(): void {
        $fixture = $this->create_fixture('belum_lunas', 'TI');
        $decision = manager::get_exam_access_decision(
            $fixture->moodleuserid,
            'TI',
            '2026/2027',
            'genap',
            'UKT'
        );
        $this->assertFalse($decision->allowed);
        $this->assertSame('required_bill_unpaid', $decision->reason);
    }

    public function test_student_from_other_prodi_is_blocked(): void {
        $fixture = $this->create_fixture('lunas', 'SI');
        $decision = manager::get_exam_access_decision(
            $fixture->moodleuserid,
            'TI',
            '2026/2027',
            'genap',
            'UKT'
        );
        $this->assertFalse($decision->allowed);
        $this->assertSame('wrong_study_program', $decision->reason);
    }

    public function test_all_required_bills_must_be_paid(): void {
        global $DB;

        $fixture = $this->create_fixture('lunas', 'TI');
        $DB->insert_record('local_siakad_tagihan', (object) [
            'mahasiswaid' => $fixture->mahasiswaid,
            'kodetagihan' => 'PRAKTIKUM-240001',
            'tahunajaran' => '2026/2027',
            'semester' => 'genap',
            'jenis' => 'UKT',
            'nominal' => 500000,
            'status' => 'belum_lunas',
            'wajib' => 1,
            'paidat' => 0,
            'duedate' => 0,
            'timemodified' => time(),
        ]);
        $this->assertFalse(manager::can_user_access_exam(
            $fixture->moodleuserid,
            'TI',
            '2026/2027',
            'genap',
            'UKT'
        ));
    }

    public function test_optional_unpaid_bill_does_not_block(): void {
        global $DB;

        $fixture = $this->create_fixture('lunas', 'TI');
        $DB->insert_record('local_siakad_tagihan', (object) [
            'mahasiswaid' => $fixture->mahasiswaid,
            'kodetagihan' => 'ALUMNI-240001',
            'tahunajaran' => '2026/2027',
            'semester' => 'genap',
            'jenis' => 'UKT',
            'nominal' => 100000,
            'status' => 'belum_lunas',
            'wajib' => 0,
            'paidat' => 0,
            'duedate' => 0,
            'timemodified' => time(),
        ]);
        $this->assertTrue(manager::can_user_access_exam(
            $fixture->moodleuserid,
            'TI',
            '2026/2027',
            'genap',
            'UKT'
        ));
    }

    public function test_cancelled_required_bill_does_not_count_as_payable(): void {
        global $DB;

        $fixture = $this->create_fixture('lunas', 'TI');
        $DB->set_field(
            'local_siakad_tagihan',
            'status',
            'dibatalkan',
            ['mahasiswaid' => $fixture->mahasiswaid]
        );
        $decision = manager::get_exam_access_decision(
            $fixture->moodleuserid,
            'TI',
            '2026/2027',
            'genap',
            'UKT'
        );
        $this->assertFalse($decision->allowed);
        $this->assertSame('required_bill_not_found', $decision->reason);
    }

    public function test_account_is_linked_through_existing_pascasync_mapping(): void {
        global $DB;

        $this->resetAfterTest();
        $moodleuser = $this->getDataGenerator()->create_user([
            'username' => 'generated.account',
            'email' => 'generated@example.test',
            'idnumber' => 'pasca:7001',
        ]);
        $DB->insert_record('local_pascasync_map', (object) [
            'userid' => $moodleuser->id,
            'sourceid' => 7001,
            'passwordfingerprint' => hash('sha256', 'fixture'),
            'sourceupdatedat' => time(),
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
        $identityid = $DB->insert_record('local_siakad_user', (object) [
            'sourceid' => '7001',
            'username' => 'siakad.legacy.username',
            'fullname' => 'Mapped Student',
            'email' => 'different@example.test',
            'role' => 'mahasiswa',
            'moodleuserid' => 0,
            'timemodified' => time(),
        ]);
        $prodiid = $DB->insert_record('local_siakad_prodi', (object) [
            'kode' => 'TI',
            'nama' => 'Teknologi Informasi',
            'aktif' => 1,
            'categoryid' => 0,
            'timemodified' => time(),
        ]);
        $studentid = $DB->insert_record('local_siakad_mahasiswa', (object) [
            'userid' => $identityid,
            'moodleuserid' => 0,
            'nim' => '247001',
            'nama' => 'Mapped Student',
            'email' => 'different@example.test',
            'prodiid' => $prodiid,
            'status' => 'aktif',
            'timemodified' => time(),
        ]);

        $this->assertSame(1, manager::link_moodle_users());
        $this->assertEquals(
            $moodleuser->id,
            $DB->get_field('local_siakad_user', 'moodleuserid', ['id' => $identityid])
        );
        $this->assertEquals(
            $moodleuser->id,
            $DB->get_field('local_siakad_mahasiswa', 'moodleuserid', ['id' => $studentid])
        );
    }

    public function test_inactive_student_is_removed_from_generated_prodi_cohort(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/cohort/lib.php');

        $moodleuser = $this->getDataGenerator()->create_user();
        $category = \core_course_category::create((object) [
            'name' => 'Teknologi Informasi',
            'idnumber' => 'prodi-ti-test',
            'parent' => 0,
        ]);
        $cohortid = \local_pascaprodi\manager::ensure_category_cohort((int) $category->id);
        cohort_add_member((int) $cohortid, (int) $moodleuser->id);

        $identityid = $DB->insert_record('local_siakad_user', (object) [
            'username' => $moodleuser->username,
            'fullname' => fullname($moodleuser),
            'email' => $moodleuser->email,
            'role' => 'mahasiswa',
            'moodleuserid' => $moodleuser->id,
            'timemodified' => time(),
        ]);
        $prodiid = $DB->insert_record('local_siakad_prodi', (object) [
            'kode' => 'TI',
            'nama' => 'Teknologi Informasi',
            'aktif' => 1,
            'categoryid' => $category->id,
            'timemodified' => time(),
        ]);
        $DB->insert_record('local_siakad_mahasiswa', (object) [
            'userid' => $identityid,
            'moodleuserid' => $moodleuser->id,
            'nim' => '249999',
            'nama' => fullname($moodleuser),
            'email' => $moodleuser->email,
            'prodiid' => $prodiid,
            'status' => 'nonaktif',
            'timemodified' => time(),
        ]);

        manager::reconcile_moodle_access();

        $this->assertFalse($DB->record_exists('cohort_members', [
            'cohortid' => $cohortid,
            'userid' => $moodleuser->id,
        ]));
    }

    private function create_fixture(string $billstatus, string $prodicode): object {
        global $DB;

        $this->resetAfterTest();
        set_config('currentyear', '2026/2027', 'local_siakadbridge');
        set_config('currentsemester', 'genap', 'local_siakadbridge');
        set_config('gatemode', 'all_required_lunas', 'local_siakadbridge');

        $moodleuser = $this->getDataGenerator()->create_user([
            'username' => 'student' . strtolower($prodicode),
            'email' => 'student' . strtolower($prodicode) . '@example.test',
        ]);
        $siakaduserid = $DB->insert_record('local_siakad_user', (object) [
            'username' => $moodleuser->username,
            'fullname' => fullname($moodleuser),
            'email' => $moodleuser->email,
            'role' => 'mahasiswa',
            'moodleuserid' => $moodleuser->id,
            'timemodified' => time(),
        ]);
        $prodiid = $DB->insert_record('local_siakad_prodi', (object) [
            'kode' => $prodicode,
            'nama' => 'Program ' . $prodicode,
            'aktif' => 1,
            'categoryid' => 0,
            'timemodified' => time(),
        ]);
        $mahasiswaid = $DB->insert_record('local_siakad_mahasiswa', (object) [
            'userid' => $siakaduserid,
            'moodleuserid' => $moodleuser->id,
            'nim' => '240001' . $prodicode,
            'nama' => fullname($moodleuser),
            'email' => $moodleuser->email,
            'prodiid' => $prodiid,
            'status' => 'aktif',
            'timemodified' => time(),
        ]);
        $DB->insert_record('local_siakad_tagihan', (object) [
            'mahasiswaid' => $mahasiswaid,
            'kodetagihan' => 'UKT-' . $prodicode . '-240001',
            'tahunajaran' => '2026/2027',
            'semester' => 'genap',
            'jenis' => 'UKT',
            'nominal' => 2500000,
            'status' => $billstatus,
            'wajib' => 1,
            'paidat' => $billstatus === 'lunas' ? time() : 0,
            'duedate' => 0,
            'timemodified' => time(),
        ]);

        return (object) [
            'moodleuserid' => $moodleuser->id,
            'mahasiswaid' => $mahasiswaid,
        ];
    }
}

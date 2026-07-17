<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakadbridge;

defined('MOODLE_INTERNAL') || die();

/** Tests for the SIAKAD exam access gate. */
final class manager_test extends \advanced_testcase {
    public function test_paid_student_in_selected_prodi_is_allowed(): void {
        $fixture = $this->create_fixture('lunas', 'TI');
        $this->assertTrue(manager::can_user_access_exam($fixture->moodleuserid, 'TI', '2026/2027', 'genap', 'UKT'));
    }

    public function test_unpaid_student_is_blocked(): void {
        $fixture = $this->create_fixture('belum_lunas', 'TI');
        $decision = manager::get_exam_access_decision($fixture->moodleuserid, 'TI', '2026/2027', 'genap', 'UKT');
        $this->assertFalse($decision->allowed);
        $this->assertSame('required_bill_unpaid', $decision->reason);
    }

    public function test_student_from_other_prodi_is_blocked(): void {
        $fixture = $this->create_fixture('lunas', 'SI');
        $decision = manager::get_exam_access_decision($fixture->moodleuserid, 'TI', '2026/2027', 'genap', 'UKT');
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
        $this->assertFalse(manager::can_user_access_exam($fixture->moodleuserid, 'TI', '2026/2027', 'genap', 'UKT'));
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
        $this->assertTrue(manager::can_user_access_exam($fixture->moodleuserid, 'TI', '2026/2027', 'genap', 'UKT'));
    }

    public function test_cancelled_required_bill_does_not_count_as_payable(): void {
        global $DB;

        $fixture = $this->create_fixture('lunas', 'TI');
        $DB->set_field('local_siakad_tagihan', 'status', 'dibatalkan', ['mahasiswaid' => $fixture->mahasiswaid]);
        $decision = manager::get_exam_access_decision($fixture->moodleuserid, 'TI', '2026/2027', 'genap', 'UKT');
        $this->assertFalse($decision->allowed);
        $this->assertSame('required_bill_not_found', $decision->reason);
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

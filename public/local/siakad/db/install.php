<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Seed safe dummy records for local development.
 */
function xmldb_local_siakad_install(): void {
    global $DB;

    $now = time();
    $prodiids = [];
    foreach ([
        ['code' => 'MM', 'name' => 'Magister Manajemen'],
        ['code' => 'MH', 'name' => 'Magister Hukum'],
        ['code' => 'MSI', 'name' => 'Magister Sistem Informasi'],
    ] as $item) {
        $item['active'] = 1;
        $item['timecreated'] = $now;
        $item['timemodified'] = $now;
        $prodiids[$item['code']] = $DB->insert_record('local_siakad_prodi', (object) $item);
    }

    $studentuser = $DB->insert_record('local_siakad_user', (object) [
        'moodleuserid' => null,
        'externalid' => 'dummy-student-001',
        'email' => 'mahasiswa.dummy@example.test',
        'name' => 'Mahasiswa Dummy',
        'usertype' => 'mahasiswa',
        'active' => 1,
        'timecreated' => $now,
        'timemodified' => $now,
    ]);
    $studentid = $DB->insert_record('local_siakad_mahasiswa', (object) [
        'siakaduserid' => $studentuser,
        'prodiid' => $prodiids['MM'],
        'nim' => 'DUMMY2026001',
        'cohortyear' => 2026,
        'status' => 'active',
    ]);
    $DB->insert_record('local_siakad_tagihan', (object) [
        'mahasiswaid' => $studentid,
        'period' => '2026-GANJIL',
        'billtype' => 'UKT',
        'amount' => 5000000,
        'paidamount' => 5000000,
        'status' => 'paid',
        'duedate' => $now + (30 * DAYSECS),
        'paidat' => $now,
        'timemodified' => $now,
    ]);

    $lectureruser = $DB->insert_record('local_siakad_user', (object) [
        'moodleuserid' => null,
        'externalid' => 'dummy-lecturer-001',
        'email' => 'dosen.dummy@example.test',
        'name' => 'Dosen Dummy',
        'usertype' => 'dosen',
        'active' => 1,
        'timecreated' => $now,
        'timemodified' => $now,
    ]);
    $DB->insert_record('local_siakad_dosen', (object) [
        'siakaduserid' => $lectureruser,
        'prodiid' => $prodiids['MM'],
        'nidn' => 'DUMMY-NIDN-001',
        'status' => 'active',
    ]);
}

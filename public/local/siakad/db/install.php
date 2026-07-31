<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Installation seeding for local_siakad.
 *
 * @package    local_siakad
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Seed safe dummy records for local development.
 */
function xmldb_local_siakad_install(): void {
    // The SIAKAD master data is not part of install.xml: it belongs to the
    // separate SIAKAD database, which Moodle's installer knows nothing about.
    // When no separate database is configured the same schema is created on the
    // Moodle connection instead, so a single-database site still works.
    \local_siakad\external_db::install_schema();

    $DB = \local_siakad\external_db::get();

    $now = time();

    // Programmes mirror local_pascaprodi_prodi, which local_pascaprodi syncs from
    // the UNW Program Studi API.
    \local_siakad\prodi_sync::sync_from_pascaprodi();

    $prodi = $DB->get_records('local_siakad_prodi', ['active' => 1], 'id ASC', 'id', 0, 1);
    if (!$prodi) {
        // No programmes exist yet on a fresh site. Seed one placeholder so the dummy
        // student and lecturer remain resolvable; the next sync relinks it to a real
        // programme by name as soon as one appears.
        $prodiid = $DB->insert_record('local_siakad_prodi', (object) [
            'code' => 'dummy-prodi',
            'name' => 'Prodi Dummy',
            'pascaprodiid' => null,
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    } else {
        $prodiid = (int) reset($prodi)->id;
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
        'prodiid' => $prodiid,
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

    // A second student with an outstanding bill exercises the blocked path.
    $unpaiduser = $DB->insert_record('local_siakad_user', (object) [
        'moodleuserid' => null,
        'externalid' => 'dummy-student-002',
        'email' => 'mahasiswa.nunggak@example.test',
        'name' => 'Mahasiswa Belum Lunas',
        'usertype' => 'mahasiswa',
        'active' => 1,
        'timecreated' => $now,
        'timemodified' => $now,
    ]);
    $unpaidstudentid = $DB->insert_record('local_siakad_mahasiswa', (object) [
        'siakaduserid' => $unpaiduser,
        'prodiid' => $prodiid,
        'nim' => 'DUMMY2026002',
        'cohortyear' => 2026,
        'status' => 'active',
    ]);
    $DB->insert_record('local_siakad_tagihan', (object) [
        'mahasiswaid' => $unpaidstudentid,
        'period' => '2026-GANJIL',
        'billtype' => 'UKT',
        'amount' => 5000000,
        'paidamount' => 1000000,
        'status' => 'unpaid',
        'duedate' => $now + (30 * DAYSECS),
        'paidat' => null,
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
        'prodiid' => $prodiid,
        'nidn' => 'DUMMY-NIDN-001',
        'status' => 'active',
    ]);
}

<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade local_siakadbridge.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_siakadbridge_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026071700) {
        $fields = [
            'local_siakad_user' => [
                new xmldb_field('sourceid', XMLDB_TYPE_CHAR, '100', null, false, false, null, 'id'),
            ],
            'local_siakad_prodi' => [
                new xmldb_field('sourceid', XMLDB_TYPE_CHAR, '100', null, false, false, null, 'id'),
                new xmldb_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, true, false, '0', 'aktif'),
            ],
            'local_siakad_mahasiswa' => [
                new xmldb_field('sourceid', XMLDB_TYPE_CHAR, '100', null, false, false, null, 'id'),
            ],
            'local_siakad_dosen' => [
                new xmldb_field('sourceid', XMLDB_TYPE_CHAR, '100', null, false, false, null, 'id'),
            ],
            'local_siakad_tagihan' => [
                new xmldb_field('sourceid', XMLDB_TYPE_CHAR, '100', null, false, false, null, 'id'),
                new xmldb_field('jenis', XMLDB_TYPE_CHAR, '100', null, true, false, 'UKT', 'semester'),
                new xmldb_field('wajib', XMLDB_TYPE_INTEGER, '1', null, true, false, '1', 'status'),
                new xmldb_field('duedate', XMLDB_TYPE_INTEGER, '10', null, true, false, '0', 'paidat'),
            ],
        ];

        foreach ($fields as $tablename => $tablefields) {
            $table = new xmldb_table($tablename);
            if (!$dbman->table_exists($table)) {
                continue;
            }
            foreach ($tablefields as $field) {
                if (!$dbman->field_exists($table, $field)) {
                    $dbman->add_field($table, $field);
                }
            }
        }

        $logtable = new xmldb_table('local_siakad_synclog');
        if (!$dbman->table_exists($logtable)) {
            $logtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, true, true);
            $logtable->add_field('source', XMLDB_TYPE_CHAR, '30', null, true, false, 'manual');
            $logtable->add_field('status', XMLDB_TYPE_CHAR, '20', null, true, false, 'success');
            $logtable->add_field('inserted', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
            $logtable->add_field('updated', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
            $logtable->add_field('failed', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
            $logtable->add_field('message', XMLDB_TYPE_TEXT, null, null, false, false);
            $logtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, true, false, '0');
            $logtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $logtable->add_index('timecreated_ix', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);
            $dbman->create_table($logtable);
        }

        $indexes = [
            ['local_siakad_user', new xmldb_index('sourceid_uix', XMLDB_INDEX_UNIQUE, ['sourceid'])],
            ['local_siakad_prodi', new xmldb_index('sourceid_uix', XMLDB_INDEX_UNIQUE, ['sourceid'])],
            ['local_siakad_prodi', new xmldb_index('categoryid_ix', XMLDB_INDEX_NOTUNIQUE, ['categoryid'])],
            ['local_siakad_mahasiswa', new xmldb_index('sourceid_uix', XMLDB_INDEX_UNIQUE, ['sourceid'])],
            ['local_siakad_dosen', new xmldb_index('sourceid_uix', XMLDB_INDEX_UNIQUE, ['sourceid'])],
            ['local_siakad_tagihan', new xmldb_index('sourceid_uix', XMLDB_INDEX_UNIQUE, ['sourceid'])],
            ['local_siakad_tagihan', new xmldb_index(
                'mahasiswa_period_ix',
                XMLDB_INDEX_NOTUNIQUE,
                ['mahasiswaid', 'tahunajaran', 'semester', 'wajib', 'status']
            )],
        ];
        foreach ($indexes as [$tablename, $index]) {
            $table = new xmldb_table($tablename);
            if ($dbman->table_exists($table) && !$dbman->index_exists($table, $index)) {
                $dbman->add_index($table, $index);
            }
        }

        if (get_config('local_siakadbridge', 'sourcemode') === false) {
            set_config('sourcemode', 'manual', 'local_siakadbridge');
        }
        if (get_config('local_siakadbridge', 'apitimeout') === false) {
            set_config('apitimeout', 30, 'local_siakadbridge');
        }
        if (get_config('local_siakadbridge', 'currentyear') === false) {
            set_config('currentyear', '2026/2027', 'local_siakadbridge');
        }
        if (get_config('local_siakadbridge', 'currentsemester') === false) {
            set_config('currentsemester', 'genap', 'local_siakadbridge');
        }
        if (get_config('local_siakadbridge', 'gatemode') === false) {
            set_config('gatemode', 'all_required_lunas', 'local_siakadbridge');
        }
        if (get_config('local_siakadbridge', 'lecturerroleshortname') === false) {
            set_config('lecturerroleshortname', 'editingteacher', 'local_siakadbridge');
        }

        upgrade_plugin_savepoint(true, 2026071700, 'local', 'siakadbridge');
    }

    if ($oldversion < 2026071701) {
        // Version 1.0.1 adds the explicit local_pascaprodi dependency.
        upgrade_plugin_savepoint(true, 2026071701, 'local', 'siakadbridge');
    }

    if ($oldversion < 2026071702) {
        if (get_config('local_siakadbridge', 'allowprivatehost') === false) {
            set_config('allowprivatehost', 0, 'local_siakadbridge');
        }
        upgrade_plugin_savepoint(true, 2026071702, 'local', 'siakadbridge');
    }

    return true;
}

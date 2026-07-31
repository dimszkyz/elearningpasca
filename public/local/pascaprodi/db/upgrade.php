<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Upgrade steps for Pasca Prodi automation.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade callback.
 */
function xmldb_local_pascaprodi_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026071601) {
        $prefix = 'pasca:prodi-category:';
        $select = 'component = ? AND ' . $DB->sql_like('idnumber', '?', false);
        $params = ['local_pascaprodi', $prefix . '%'];

        // Generated cohorts must remain editable in Moodle's Cohorts UI so admins
        // can manually assign members. Moodle hides manual actions for cohorts
        // owned by a non-empty component.
        $DB->set_field_select('cohort', 'component', '', $select, $params);

        upgrade_plugin_savepoint(true, 2026071601, 'local', 'pascaprodi');
    }

    if ($oldversion < 2026071602) {
        if (get_config('local_pascaprodi', 'autoenrolstudents') === false) {
            set_config('autoenrolstudents', 1, 'local_pascaprodi');
        }

        upgrade_plugin_savepoint(true, 2026071602, 'local', 'pascaprodi');
    }

    if ($oldversion < 2026071603) {
        unset_config('autoenrolteachers', 'local_pascaprodi');
        unset_config('teachernameprefix', 'local_pascaprodi');

        \local_pascaprodi\manager::cleanup_old_teacher_cohorts();

        upgrade_plugin_savepoint(true, 2026071603, 'local', 'pascaprodi');
    }

    if ($oldversion < 2026071605) {
        if (get_config('local_pascaprodi', 'syncapiurl') === false) {
            set_config('syncapiurl', \local_pascaprodi\manager::DEFAULT_API_URL, 'local_pascaprodi');
        }
        if (get_config('local_pascaprodi', 'syncapitimeout') === false) {
            set_config('syncapitimeout', 30, 'local_pascaprodi');
        }

        upgrade_plugin_savepoint(true, 2026071605, 'local', 'pascaprodi');
    }

    if ($oldversion < 2026071606) {
        upgrade_plugin_savepoint(true, 2026071606, 'local', 'pascaprodi');
    }

    if ($oldversion < 2026071607) {
        upgrade_plugin_savepoint(true, 2026071607, 'local', 'pascaprodi');
    }

    if ($oldversion < 2026071608) {
        upgrade_plugin_savepoint(true, 2026071608, 'local', 'pascaprodi');
    }

    if ($oldversion < 2026072900) {
        // Study programmes stop being course categories and become plugin data, so
        // the plugin gains its first table. Existing sites created the categories
        // already; cli/migrate_prodi_categories.php converts them.
        $table = new xmldb_table('local_pascaprodi_prodi');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('code', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('jenjang', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('facultyname', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('facultycode', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('cohortid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('code_uniq', XMLDB_INDEX_UNIQUE, ['code']);
        $table->add_index('cohortid_idx', XMLDB_INDEX_NOTUNIQUE, ['cohortid']);
        $table->add_index('active_idx', XMLDB_INDEX_NOTUNIQUE, ['active']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // A course category no longer implies a programme, so there is nothing to
        // derive automatically when a course is created. Programmes are chosen on
        // the course form instead.
        unset_config('autoenrolstudents', 'local_pascaprodi');

        upgrade_plugin_savepoint(true, 2026072900, 'local', 'pascaprodi');
    }

    return true;
}

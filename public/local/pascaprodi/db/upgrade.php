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

    return true;
}

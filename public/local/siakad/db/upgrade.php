<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Upgrade steps for local_siakad.
 *
 * @package    local_siakad
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade callback.
 */
function xmldb_local_siakad_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026072700) {
        // Programmes became a projection of an external structure rather than
        // hardcoded seed rows. Nothing to do here beyond the savepoint; the
        // 2026072900 step below rebuilds the link against local_pascaprodi.
        upgrade_plugin_savepoint(true, 2026072700, 'local', 'siakad');
    }

    if ($oldversion < 2026072900) {
        $table = new xmldb_table('local_siakad_prodi');

        // Programme codes now mirror local_pascaprodi, whose codes are API slugs
        // up to 100 characters. The old 32-char limit truncated and could collide.
        // The unique index has to come off first; a column cannot be widened while
        // an index depends on it.
        $codeindex = new xmldb_index('code_uniq', XMLDB_INDEX_UNIQUE, ['code']);
        $hadcodeindex = $dbman->index_exists($table, $codeindex);
        if ($hadcodeindex) {
            $dbman->drop_index($table, $codeindex);
        }

        $code = new xmldb_field('code', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'id');
        $dbman->change_field_precision($table, $code);

        if ($hadcodeindex) {
            $dbman->add_index($table, $codeindex);
        }

        // Programmes are no longer course categories, so the link points at the
        // local_pascaprodi programme row instead.
        $pascaprodiid = new xmldb_field('pascaprodiid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'name');
        if (!$dbman->field_exists($table, $pascaprodiid)) {
            $dbman->add_field($table, $pascaprodiid);
        }

        $index = new xmldb_index('pascaprodiid_idx', XMLDB_INDEX_NOTUNIQUE, ['pascaprodiid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Link by code where local_pascaprodi already holds the programme. Rows
        // left unlinked are picked up by the next sync, once
        // local/pascaprodi/cli/migrate_prodi_categories.php has run.
        \local_siakad\prodi_sync::sync_from_pascaprodi();

        $moodlecategoryid = new xmldb_field('moodlecategoryid');
        if ($dbman->field_exists($table, $moodlecategoryid)) {
            $dbman->drop_field($table, $moodlecategoryid);
        }

        upgrade_plugin_savepoint(true, 2026072900, 'local', 'siakad');
    }

    if ($oldversion < 2026072901) {
        // The SIAKAD master data moved out of install.xml into its own database.
        // Make sure the schema exists on whichever connection is configured; on a
        // site with no separate database this finds the existing tables and does
        // nothing. Existing rows stay where they are until
        // cli/setup_external_db.php --migrate is run.
        \local_siakad\external_db::install_schema();

        upgrade_plugin_savepoint(true, 2026072901, 'local', 'siakad');
    }

    return true;
}

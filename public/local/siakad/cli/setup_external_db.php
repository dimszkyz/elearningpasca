<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Build the standalone SIAKAD database and move existing rows into it.
 *
 * @package    local_siakad
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

use local_siakad\external_db;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'install' => false,
        'migrate' => false,
        'status' => false,
        'purge-source' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognized)));
}

if ($options['help'] || (!$options['install'] && !$options['migrate'] && !$options['status'])) {
    cli_writeln(<<<EOT
Set up the standalone SIAKAD database configured under Site administration >
Plugins > Local plugins > Dummy SIAKAD integration.

Options:
  -h, --help       Print this help.
      --status     Show the configured connection and whether the schema exists.
      --install    Create any missing SIAKAD table on that connection.
      --migrate    Copy SIAKAD rows from the Moodle database into it. IDs are
                   preserved and rows already present are left alone, so the
                   command is safe to re-run and tops up a partial migration.
      --purge-source  After a successful --migrate, drop the old SIAKAD tables
                   from the Moodle database. Take a backup first.

Example:
  php local/siakad/cli/setup_external_db.php --install --migrate
EOT);
    exit(0);
}

if (!external_db::is_external()) {
    cli_error('No separate SIAKAD database is configured. Set the database type and name in the plugin settings first.');
}

$siakaddb = external_db::get();

if ($options['status']) {
    cli_writeln('Connection: ' . $siakaddb->get_dbvendor() . ' ' . $siakaddb->get_name());
    cli_writeln('Schema installed: ' . (external_db::schema_installed() ? 'yes' : 'no'));

    foreach (external_db::TABLES as $table) {
        $here = $DB->get_manager()->table_exists($table) ? (int) $DB->count_records($table) : -1;
        $there = $siakaddb->get_manager()->table_exists($table) ? (int) $siakaddb->count_records($table) : -1;
        cli_writeln(sprintf(
            '  %-28s moodle: %s  siakad: %s',
            $table,
            $here < 0 ? 'absent' : $here,
            $there < 0 ? 'absent' : $there
        ));
    }
}

if ($options['install']) {
    $created = external_db::install_schema();
    cli_writeln($created
        ? 'Created: ' . implode(', ', $created)
        : 'Schema already complete, nothing to create.');
}

if ($options['migrate']) {
    if (!external_db::schema_installed()) {
        cli_error('Schema is incomplete. Run with --install first.');
    }

    $moodlemanager = $DB->get_manager();
    $failed = false;

    // Parents before children, so a partially migrated set never dangles.
    foreach (external_db::TABLES as $table) {
        if (!$moodlemanager->table_exists($table)) {
            cli_writeln(sprintf('  %-28s no source table, skipped.', $table));
            continue;
        }

        $expected = (int) $DB->count_records($table);
        $copied = 0;
        $skipped = 0;

        $rs = $DB->get_recordset($table, null, 'id ASC');
        foreach ($rs as $record) {
            // Re-runs top up whatever is missing rather than restarting, so a
            // partial migration can be completed without touching existing rows.
            if ($siakaddb->record_exists($table, ['id' => $record->id])) {
                $skipped++;
                continue;
            }

            // Keep the original ID: quizprodi.prodiid and the cohort links point at it.
            $siakaddb->import_record($table, $record);
            $copied++;
        }
        $rs->close();

        // Count the target rather than trusting the loop counter: a row that was
        // silently rejected must not be reported as migrated.
        $actual = (int) $siakaddb->count_records($table);
        cli_writeln(sprintf(
            '  %-28s copied %d, already present %d, now holds %d of %d.',
            $table,
            $copied,
            $skipped,
            $actual,
            $expected
        ));

        if ($actual < $expected) {
            $failed = true;
            cli_writeln(sprintf('  %-28s WARNING: %d row(s) did not arrive.', $table, $expected - $actual));
        }
    }

    // import_record() writes explicit IDs without advancing the sequence, so
    // the next insert would collide on databases that track one.
    foreach (external_db::TABLES as $table) {
        if ($siakaddb->get_manager()->table_exists($table)) {
            $siakaddb->get_manager()->reset_sequence($table);
        }
    }

    if ($failed) {
        cli_error('Migration incomplete: some rows did not arrive. Do not run --purge-source.');
    }

    cli_writeln('Migration complete.');
}

if ($options['purge-source']) {
    if (!external_db::schema_installed()) {
        cli_error('Refusing to purge: the SIAKAD schema is incomplete.');
    }

    $moodlemanager = $DB->get_manager();

    // Children before parents, the reverse of the copy order.
    foreach (array_reverse(external_db::TABLES) as $table) {
        if (!$moodlemanager->table_exists($table)) {
            continue;
        }

        $here = (int) $DB->count_records($table);
        $there = (int) $siakaddb->count_records($table);
        if ($here > $there) {
            cli_error("Refusing to drop {$table}: the Moodle copy holds {$here} row(s) but the SIAKAD copy only {$there}.");
        }

        $moodlemanager->drop_table(new xmldb_table($table));
        cli_writeln("  dropped {$table} from the Moodle database.");
    }
}

exit(0);

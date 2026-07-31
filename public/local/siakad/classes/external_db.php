<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_siakad;

defined('MOODLE_INTERNAL') || die();

/**
 * Connection to the standalone SIAKAD database.
 *
 * The SIAKAD master data (identities, programmes, students, lecturers, bills)
 * models an external academic system, so it lives in its own database rather
 * than alongside the Moodle tables. When no connection is configured the main
 * Moodle connection is returned instead, which keeps single-database sites and
 * the original dummy seed working unchanged.
 *
 * Quiz targeting ({local_siakad_quizprodi}) is Moodle-side configuration and
 * always stays on $DB, so never route it through here.
 *
 * @package    local_siakad
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class external_db {
    /** Tables owned by the SIAKAD database. */
    public const TABLES = [
        'local_siakad_user',
        'local_siakad_prodi',
        'local_siakad_mahasiswa',
        'local_siakad_dosen',
        'local_siakad_tagihan',
    ];

    /** @var \moodle_database|null Lazily built connection. */
    private static $db = null;

    /**
     * Return the connection holding the SIAKAD tables.
     *
     * Falls back to the Moodle connection when nothing is configured.
     */
    public static function get(): \moodle_database {
        global $DB;

        if (self::$db !== null) {
            return self::$db;
        }

        $config = self::get_config();
        if ($config === null) {
            self::$db = $DB;

            return self::$db;
        }

        $db = \moodle_database::get_driver_instance($config['dbtype'], $config['dblibrary'], true);
        $db->connect(
            $config['dbhost'],
            $config['dbuser'],
            $config['dbpass'],
            $config['dbname'],
            $config['prefix'],
            $config['dboptions']
        );

        self::$db = $db;

        return self::$db;
    }

    /**
     * Whether a separate SIAKAD database is configured.
     */
    public static function is_external(): bool {
        return self::get_config() !== null;
    }

    /**
     * Drop the cached connection, so the next get() reconnects.
     *
     * Only settings changes and the setup CLI need this.
     */
    public static function reset(): void {
        if (self::$db !== null && self::is_external()) {
            self::$db->dispose();
        }

        self::$db = null;
    }

    /**
     * Whether the SIAKAD schema exists on the configured connection.
     */
    public static function schema_installed(): bool {
        $manager = self::get()->get_manager();

        foreach (self::TABLES as $table) {
            if (!$manager->table_exists($table)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create any missing SIAKAD table on the configured connection.
     *
     * @return string[] Tables created by this call.
     */
    public static function install_schema(): array {
        global $CFG;

        $manager = self::get()->get_manager();
        $missing = [];

        foreach (self::TABLES as $table) {
            if (!$manager->table_exists($table)) {
                $missing[] = $table;
            }
        }

        if ($missing) {
            // install_from_xmldb_file() skips tables that already exist, so a
            // partially built schema is completed rather than rejected.
            $manager->install_from_xmldb_file($CFG->dirroot . '/local/siakad/db/install_external.xml');
        }

        return $missing;
    }

    /**
     * Read the configured connection, or null when the main database is used.
     *
     * @return array|null
     */
    private static function get_config(): ?array {
        $settings = get_config('local_siakad');

        $dbtype = trim((string) ($settings->dbtype ?? ''));
        $dbname = trim((string) ($settings->dbname ?? ''));
        if ($dbtype === '' || $dbname === '') {
            return null;
        }

        $dboptions = [];
        $dbport = (int) ($settings->dbport ?? 0);
        if ($dbport > 0) {
            $dboptions['dbport'] = $dbport;
        }
        $dbsocket = trim((string) ($settings->dbsocket ?? ''));
        if ($dbsocket !== '') {
            $dboptions['dbsocket'] = $dbsocket;
        }

        return [
            'dbtype' => $dbtype,
            'dblibrary' => trim((string) ($settings->dblibrary ?? '')) ?: 'native',
            'dbhost' => trim((string) ($settings->dbhost ?? '')) ?: 'localhost',
            'dbuser' => (string) ($settings->dbuser ?? ''),
            'dbpass' => (string) ($settings->dbpass ?? ''),
            'dbname' => $dbname,
            'prefix' => (string) ($settings->dbprefix ?? ''),
            'dboptions' => $dboptions,
        ];
    }
}

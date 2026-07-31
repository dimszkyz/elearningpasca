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
 * Mirror local_pascaprodi study programmes into the dummy SIAKAD programme table.
 *
 * local_pascaprodi owns the programme model: it syncs {local_pascaprodi_prodi}
 * from the UNW API and generates one student cohort per programme. This class
 * keeps local_siakad_prodi as a thin projection of that table so a real SIAKAD
 * integration can later replace the source without touching the exam gate.
 *
 * @package    local_siakad
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class prodi_sync {
    /** Plugin component name. */
    public const COMPONENT = 'local_siakad';

    /** Source table owned by local_pascaprodi. */
    public const SOURCE_TABLE = 'local_pascaprodi_prodi';

    /**
     * Mirror every local_pascaprodi programme into local_siakad_prodi.
     *
     * Programmes whose source row disappeared or was deactivated are deactivated
     * instead of deleted, so historical mahasiswa, dosen, and quiz rows keep
     * resolving.
     *
     * @return array{created:int,updated:int,unchanged:int,deactivated:int,skipped:int}
     */
    public static function sync_from_pascaprodi(): array {
        global $DB;

        $siakaddb = external_db::get();

        $result = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'deactivated' => 0,
            'skipped' => 0,
        ];

        if (!self::source_available()) {
            // local_pascaprodi is absent or not upgraded yet. Leaving the table
            // untouched is safer than deactivating every programme.
            $result['skipped'] = $siakaddb->count_records('local_siakad_prodi');

            return $result;
        }

        $sources = $DB->get_records(self::SOURCE_TABLE, ['active' => 1], 'sortorder ASC, name ASC');
        $seen = [];

        foreach ($sources as $source) {
            $prodiid = self::ensure_prodi((int) $source->id, $result);
            if ($prodiid) {
                $seen[$prodiid] = true;
            }
        }

        foreach ($siakaddb->get_records('local_siakad_prodi', ['active' => 1], '', 'id') as $prodi) {
            if (isset($seen[(int) $prodi->id])) {
                continue;
            }
            $siakaddb->update_record('local_siakad_prodi', (object) [
                'id' => $prodi->id,
                'active' => 0,
                'timemodified' => time(),
            ]);
            $result['deactivated']++;
        }

        return $result;
    }

    /**
     * Create or refresh the programme row backing one local_pascaprodi programme.
     *
     * @param int $pascaprodiid local_pascaprodi programme ID.
     * @param array $result Counter array updated by reference.
     * @return int|null Programme ID, or null when the source row does not exist.
     */
    public static function ensure_prodi(int $pascaprodiid, array &$result = []): ?int {
        global $DB;

        if ($pascaprodiid <= 0 || !self::source_available()) {
            return null;
        }

        $source = $DB->get_record(self::SOURCE_TABLE, ['id' => $pascaprodiid], 'id,code,name');
        if (!$source) {
            return null;
        }

        $siakaddb = external_db::get();

        $now = time();
        $code = self::trim_field((string) $source->code, 100);
        $name = self::trim_field((string) $source->name, 255);

        $existing = $siakaddb->get_record('local_siakad_prodi', ['pascaprodiid' => $pascaprodiid]);
        if (!$existing) {
            // A programme may pre-date the link, for example dummy seed data or rows
            // created while programmes were still course categories. Adopt it by
            // code, then by name, so upgrades relink instead of duplicating.
            $existing = $siakaddb->get_record('local_siakad_prodi', ['code' => $code])
                ?: self::find_unlinked_by_name($name);
        }

        if ($existing) {
            $changed = (string) $existing->code !== $code
                || (string) $existing->name !== $name
                || (int) $existing->pascaprodiid !== $pascaprodiid
                || (int) $existing->active !== 1;

            if ($changed) {
                $siakaddb->update_record('local_siakad_prodi', (object) [
                    'id' => $existing->id,
                    'code' => $code,
                    'name' => $name,
                    'pascaprodiid' => $pascaprodiid,
                    'active' => 1,
                    'timemodified' => $now,
                ]);
                self::bump($result, 'updated');
            } else {
                self::bump($result, 'unchanged');
            }

            return (int) $existing->id;
        }

        $prodiid = (int) $siakaddb->insert_record('local_siakad_prodi', (object) [
            'code' => $code,
            'name' => $name,
            'pascaprodiid' => $pascaprodiid,
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        self::bump($result, 'created');

        return $prodiid;
    }

    /**
     * Deactivate the programme linked to a removed local_pascaprodi programme.
     */
    public static function deactivate_prodi(int $pascaprodiid): void {
        $DB = external_db::get();

        $prodi = $DB->get_record('local_siakad_prodi', ['pascaprodiid' => $pascaprodiid]);
        if (!$prodi || (int) $prodi->active === 0) {
            return;
        }

        $DB->update_record('local_siakad_prodi', (object) [
            'id' => $prodi->id,
            'active' => 0,
            'timemodified' => time(),
        ]);
    }

    /**
     * Return active programmes indexed by ID, ordered by name.
     *
     * @return \stdClass[]
     */
    public static function get_active_prodi(): array {
        return external_db::get()->get_records('local_siakad_prodi', ['active' => 1], 'name ASC');
    }

    /**
     * Return the local_pascaprodi programme ID backing a programme, or 0.
     */
    public static function get_pascaprodi_id(int $prodiid): int {
        $pascaprodiid = external_db::get()->get_field('local_siakad_prodi', 'pascaprodiid', ['id' => $prodiid]);

        return $pascaprodiid ? (int) $pascaprodiid : 0;
    }

    /**
     * Whether the local_pascaprodi programme table is installed and usable.
     */
    public static function source_available(): bool {
        global $DB;

        static $available = null;

        if ($available === null) {
            $available = class_exists('\\local_pascaprodi\\manager')
                && $DB->get_manager()->table_exists(self::SOURCE_TABLE);
        }

        return $available;
    }

    /**
     * Find a programme with this name that is not yet bound to a source row.
     */
    private static function find_unlinked_by_name(string $name): ?\stdClass {
        $DB = external_db::get();

        $records = $DB->get_records_select(
            'local_siakad_prodi',
            'pascaprodiid IS NULL AND ' . $DB->sql_equal('name', ':name', false),
            ['name' => $name],
            'id ASC',
            '*',
            0,
            1
        );

        return $records ? reset($records) : null;
    }

    /**
     * Cut a value to the column width without breaking multibyte characters.
     */
    private static function trim_field(string $value, int $length): string {
        $value = trim($value);

        return \core_text::strlen($value) > $length ? \core_text::substr($value, 0, $length) : $value;
    }

    /**
     * Increment a counter when the caller passed a result array.
     */
    private static function bump(array &$result, string $key): void {
        if (array_key_exists($key, $result)) {
            $result[$key]++;
        }
    }
}

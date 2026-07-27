<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascaprodi;

defined('MOODLE_INTERNAL') || die();

/**
 * Synchronises every study program returned by the UNW API into Moodle categories.
 *
 * The legacy category synchroniser only accepted Magister records. This service
 * intentionally does not filter by jenjang, so Diploma, Sarjana, Profesi,
 * Magister, and future levels returned by the API are handled uniformly.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class category_sync_service {
    /**
     * Fetch the remote API and create or update root Moodle categories.
     *
     * @return array{created:int,updated:int,unchanged:int,skipped:int,failed:int,cohortcreated:int,cohortupdated:int,items:array<int,array<string,string|int>>}
     */
    public static function run(): array {
        global $CFG;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/filelib.php');

        $result = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'failed' => 0,
            'cohortcreated' => 0,
            'cohortupdated' => 0,
            'items' => [],
        ];

        $payload = self::fetch_remote_payload();
        $rawitems = self::extract_items($payload);
        if (!$rawitems) {
            throw new \moodle_exception('apisyncinvaliddata', manager::COMPONENT);
        }

        $seen = [];
        foreach ($rawitems as $rawitem) {
            $item = self::normalise_program_studi_item($rawitem);
            if ($item === null) {
                $result['skipped']++;
                continue;
            }

            if (isset($seen[$item['idnumber']])) {
                $result['skipped']++;
                $result['items'][] = [
                    'status' => 'skipped',
                    'name' => $item['name'],
                    'idnumber' => $item['idnumber'],
                    'categoryid' => 0,
                    'cohortid' => 0,
                    'message' => 'Duplicate category ID number in the API response.',
                ];
                continue;
            }
            $seen[$item['idnumber']] = true;

            try {
                $synced = self::sync_one_category($item['name'], $item['idnumber']);
                $result[$synced['status']]++;
                if (!empty($synced['cohortcreated'])) {
                    $result['cohortcreated']++;
                } else if (!empty($synced['cohortid'])) {
                    $result['cohortupdated']++;
                }
                $result['items'][] = [
                    'status' => $synced['status'],
                    'name' => $item['name'],
                    'idnumber' => $item['idnumber'],
                    'categoryid' => $synced['categoryid'],
                    'cohortid' => $synced['cohortid'],
                    'message' => '',
                ];
            } catch (\Throwable $exception) {
                $result['failed']++;
                $result['items'][] = [
                    'status' => 'failed',
                    'name' => $item['name'],
                    'idnumber' => $item['idnumber'],
                    'categoryid' => 0,
                    'cohortid' => 0,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return $result;
    }

    /**
     * Normalise all usable items from an API payload without filtering jenjang.
     *
     * @param mixed $payload
     * @return array<int,array{name:string,idnumber:string,jenjang:string}>
     */
    public static function normalise_payload(mixed $payload): array {
        $normalised = [];
        foreach (self::extract_items($payload) as $item) {
            $record = self::normalise_program_studi_item($item);
            if ($record !== null) {
                $normalised[] = $record;
            }
        }
        return $normalised;
    }

    /**
     * Normalise one study-program item from the UNW API.
     *
     * Category names use "jenjang + space + nama". The API slug is the
     * preferred category ID number, with code or source ID used only as a
     * defensive fallback when a future API response omits slug.
     *
     * @param mixed $item
     * @return array{name:string,idnumber:string,jenjang:string}|null
     */
    public static function normalise_program_studi_item(mixed $item): ?array {
        if (is_array($item)) {
            $item = (object) $item;
        }
        if (!is_object($item)) {
            return null;
        }

        $jenjang = self::first_text($item, [
            'jenjang', 'level', 'nama_jenjang', 'namaJenjang', 'education_level',
        ]);
        $nama = self::first_text($item, [
            'nama', 'name', 'nama_program_studi', 'nama_prodi', 'namaProgramStudi', 'namaProdi',
        ]);
        $idnumber = self::first_text($item, [
            'slug', 'kode', 'code', 'kode_program_studi', 'kode_prodi', 'kodeProgramStudi',
            'kodeProdi', 'id', 'id_program_studi', 'id_prodi', 'program_studi_id', 'prodi_id',
        ]);

        if ($nama === '' || $idnumber === '') {
            return null;
        }

        $categoryname = trim($jenjang . ' ' . $nama);
        if ($categoryname === '') {
            return null;
        }

        $idnumber = clean_param($idnumber, PARAM_TEXT);
        if (\core_text::strlen($categoryname) > 255) {
            $categoryname = \core_text::substr($categoryname, 0, 255);
        }
        if (\core_text::strlen($idnumber) > 100) {
            $idnumber = \core_text::substr($idnumber, 0, 100);
        }
        if ($idnumber === '') {
            return null;
        }

        return [
            'name' => $categoryname,
            'idnumber' => $idnumber,
            'jenjang' => $jenjang,
        ];
    }

    /**
     * Fetch and decode the configured API payload.
     *
     * @return mixed
     */
    private static function fetch_remote_payload(): mixed {
        $url = manager::get_sync_api_url();
        $timeout = manager::get_sync_api_timeout();
        $response = download_file_content($url, null, null, false, $timeout, 20);

        if ($response === false || trim((string) $response) === '') {
            throw new \moodle_exception('apisyncfailed', manager::COMPONENT, '', $url);
        }

        try {
            return json_decode((string) $response, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \moodle_exception(
                'apisyncinvalidjson',
                manager::COMPONENT,
                '',
                $exception->getMessage()
            );
        }
    }

    /**
     * Extract the item list from common API envelopes.
     *
     * @param mixed $payload
     * @return array<int,mixed>
     */
    private static function extract_items(mixed $payload): array {
        if (is_array($payload)) {
            if (array_is_list($payload)) {
                return $payload;
            }
            $payload = (object) $payload;
        }
        if (!is_object($payload)) {
            return [];
        }

        foreach ([
            'data', 'items', 'results', 'prodi', 'programs', 'program_studi',
            'programStudi', 'study_programs', 'studyPrograms',
        ] as $property) {
            if (!property_exists($payload, $property)) {
                continue;
            }
            $value = $payload->{$property};
            if (is_array($value)) {
                return $value;
            }
            if (is_object($value)) {
                $nested = self::extract_items($value);
                if ($nested) {
                    return $nested;
                }
            }
        }

        return [];
    }

    /**
     * Return the first non-empty scalar field from a list of possible names.
     */
    private static function first_text(object $item, array $properties): string {
        foreach ($properties as $property) {
            if (!property_exists($item, $property)) {
                continue;
            }
            $value = $item->{$property};
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }

    /**
     * Create or update one root Moodle category and its student cohort.
     *
     * @return array{status:string,categoryid:int,cohortid:int,cohortcreated:bool}
     */
    private static function sync_one_category(string $name, string $idnumber): array {
        global $DB;

        $status = 'unchanged';
        $existing = $DB->get_records('course_categories', ['idnumber' => $idnumber], 'id ASC', '*', 0, 1);
        $record = $existing ? reset($existing) : false;

        if ($record) {
            $category = \core_course_category::get((int) $record->id, MUST_EXIST, true);
            $changes = [];
            if ((string) $record->name !== $name) {
                $changes['name'] = $name;
            }
            if ((int) $record->parent !== 0) {
                $changes['parent'] = 0;
            }

            if ($changes) {
                $category->update((object) $changes);
                $status = 'updated';
            }
            $categoryid = (int) $category->id;
        } else {
            $category = \core_course_category::create((object) [
                'name' => $name,
                'idnumber' => $idnumber,
                'parent' => 0,
                'description' => '',
                'descriptionformat' => FORMAT_HTML,
                'visible' => 1,
            ]);
            $categoryid = (int) $category->id;
            $status = 'created';
        }

        $cohortidnumber = manager::cohort_idnumber($categoryid);
        $cohortexisted = $DB->record_exists('cohort', ['idnumber' => $cohortidnumber]);
        $cohortid = manager::ensure_category_cohort($categoryid);

        return [
            'status' => $status,
            'categoryid' => $categoryid,
            'cohortid' => (int) $cohortid,
            'cohortcreated' => !$cohortexisted && !empty($cohortid),
        ];
    }
}

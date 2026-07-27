<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakadbridge\sync;

defined('MOODLE_INTERNAL') || die();

/**
 * Synchronises the campus-wide study-program master from a dedicated REST endpoint.
 *
 * The adapter deliberately accepts several common response envelopes and field names
 * because the external UNW endpoint is maintained outside Moodle. Internal records are
 * always normalised to id, kode, nama and aktif before they reach the bridge importer.
 */
final class program_study_service {
    /** Run the configured dedicated study-program synchronisation. */
    public static function run(): object {
        $mode = (string) get_config('local_siakadbridge', 'sourcemode');
        if ($mode !== 'rest') {
            return self::result(0, 0, 0, 'Manual mode: no remote study-program synchronisation was performed.');
        }

        $url = trim((string) get_config('local_siakadbridge', 'programapiurl'));
        if ($url === '') {
            return self::result(0, 0, 0, 'No dedicated study-program endpoint is configured.');
        }
        self::validate_url($url);

        $token = trim((string) get_config('local_siakadbridge', 'programapitoken'));
        if ($token === '') {
            $token = trim((string) get_config('local_siakadbridge', 'apitoken'));
        }

        $lockfactory = \core\lock\lock_config::get_lock_factory('local_siakadbridge');
        $lock = $lockfactory->get_lock('program-study-sync', 0);
        if (!$lock) {
            throw new \moodle_exception('syncfailed', 'local_siakadbridge', '',
                'Another study-program synchronisation is already running.');
        }

        try {
            $payload = self::fetch_json($url, $token);
            $programs = self::normalise_payload($payload);
            if (!$programs) {
                throw new \moodle_exception('syncfailed', 'local_siakadbridge', '',
                    'The study-program endpoint returned no usable study programs.');
            }

            $result = service::import_payload((object) ['prodi' => $programs], 'rest-program-studi');
            $deactivated = 0;
            if ((bool) get_config('local_siakadbridge', 'programapifullsnapshot')) {
                $deactivated = self::deactivate_missing($programs);
                $result->updated += $deactivated;
                if ($deactivated > 0) {
                    \local_siakadbridge\manager::reconcile_moodle_access();
                }
            }
            $result->message = sprintf(
                'Study-program API synchronised: %d active/incoming records, %d missing records deactivated.',
                count($programs),
                $deactivated
            );
            return $result;
        } finally {
            $lock->release();
        }
    }

    /**
     * Convert an external response into the bridge study-program contract.
     *
     * Supported envelopes include a root array and objects containing data, items,
     * results, prodi, programs, program_studi or programStudi.
     *
     * @return array<int, object>
     */
    public static function normalise_payload(mixed $payload): array {
        $items = self::extract_items($payload);
        $programs = [];

        foreach ($items as $index => $item) {
            if (is_array($item)) {
                $item = (object) $item;
            }
            if (!is_object($item)) {
                continue;
            }

            $sourceid = self::first_text($item, [
                'id', 'sourceid', 'id_program_studi', 'id_prodi', 'program_studi_id',
                'programStudyId', 'prodi_id', 'uuid',
            ]);
            $kode = self::first_text($item, [
                'kode', 'code', 'kode_program_studi', 'kode_prodi', 'kodeProgramStudi',
                'kodeProdi', 'kd_prodi', 'program_studi_kode',
            ]);
            $nama = self::first_text($item, [
                'nama', 'name', 'nama_program_studi', 'nama_prodi', 'namaProgramStudi',
                'namaProdi', 'program_studi_nama', 'program_studi', 'prodi',
            ]);

            if ($kode === '' && $sourceid !== '') {
                $kode = $sourceid;
            }
            if ($sourceid === '' && $kode !== '') {
                $sourceid = $kode;
            }
            if ($kode === '' || $nama === '') {
                throw new \invalid_parameter_exception(
                    'Study-program item #' . ($index + 1) . ' must contain a code/id and a name.'
                );
            }

            $kode = \core_text::strtoupper($kode);
            $programs[$kode] = (object) [
                'id' => $sourceid,
                'kode' => $kode,
                'nama' => $nama,
                'aktif' => self::active_value($item),
            ];
        }

        ksort($programs, SORT_NATURAL | SORT_FLAG_CASE);
        return array_values($programs);
    }

    /** @return array<int, mixed> */
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
            'prodi', 'programs', 'program_studi', 'programStudi', 'study_programs',
            'studyPrograms', 'items', 'results',
        ] as $property) {
            if (property_exists($payload, $property)) {
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
        }

        if (property_exists($payload, 'data')) {
            return self::extract_items($payload->data);
        }
        return [];
    }

    private static function first_text(object $item, array $properties): string {
        foreach ($properties as $property) {
            if (!property_exists($item, $property)) {
                continue;
            }
            $value = $item->{$property};
            if (is_object($value) || is_array($value)) {
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }
        return '';
    }

    private static function active_value(object $item): bool {
        foreach (['aktif', 'active', 'is_active', 'isActive', 'status_aktif', 'statusAktif', 'status'] as $property) {
            if (!property_exists($item, $property)) {
                continue;
            }
            $value = $item->{$property};
            if (is_bool($value)) {
                return $value;
            }
            if (is_int($value) || is_float($value)) {
                return (int) $value !== 0;
            }
            $normalised = \core_text::strtolower(trim((string) $value));
            if (in_array($normalised, ['0', 'false', 'no', 'n', 'inactive', 'nonaktif', 'deleted', 'hapus'], true)) {
                return false;
            }
            if (in_array($normalised, ['1', 'true', 'yes', 'y', 'active', 'aktif'], true)) {
                return true;
            }
        }
        return true;
    }

    private static function validate_url(string $url): void {
        $parts = parse_url($url);
        if (!is_array($parts) || !in_array($parts['scheme'] ?? '', ['http', 'https'], true) || empty($parts['host'])) {
            throw new \moodle_exception('syncfailed', 'local_siakadbridge', '',
                'The study-program API URL is invalid.');
        }
    }

    private static function fetch_json(string $url, string $token): mixed {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');
        $allowprivatehost = (bool) get_config('local_siakadbridge', 'allowprivatehost');
        $curl = new \curl(['ignoresecurity' => $allowprivatehost]);
        $headers = [
            'Accept: application/json',
            'Cache-Control: no-cache',
            'Expect:',
        ];
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $curl->setHeader($headers);

        $timeout = (int) get_config('local_siakadbridge', 'apitimeout');
        $timeout = max(5, min(300, $timeout > 0 ? $timeout : 30));
        $response = $curl->get($url, [], [
            'CONNECTTIMEOUT' => min(15, $timeout),
            'TIMEOUT' => $timeout,
            'RETURNTRANSFER' => true,
            'HEADER' => false,
        ]);
        if (!empty($curl->errno)) {
            throw new \moodle_exception('syncfailed', 'local_siakadbridge', '',
                clean_param((string) $curl->error, PARAM_TEXT));
        }

        $status = (int) ($curl->info['http_code'] ?? 0);
        if ($response === false || $status < 200 || $status >= 300) {
            throw new \moodle_exception('syncfailed', 'local_siakadbridge', '',
                'HTTP ' . $status . ' while requesting the study-program API.');
        }

        try {
            return json_decode((string) $response, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \moodle_exception('syncfailed', 'local_siakadbridge', '',
                'Invalid study-program JSON: ' . $exception->getMessage());
        }
    }

    /** @param array<int, object> $programs */
    private static function deactivate_missing(array $programs): int {
        global $DB;

        $codes = array_values(array_unique(array_map(
            static fn(object $program): string => \core_text::strtoupper(trim((string) $program->kode)),
            $programs
        )));
        if (!$codes) {
            return 0;
        }

        [$notinsql, $params] = $DB->get_in_or_equal($codes, SQL_PARAMS_NAMED, 'program', false);
        $records = $DB->get_records_select('local_siakad_prodi', 'kode ' . $notinsql . ' AND aktif = 1', $params, '', 'id');
        foreach ($records as $record) {
            $DB->set_field('local_siakad_prodi', 'aktif', 0, ['id' => $record->id]);
            $DB->set_field('local_siakad_prodi', 'timemodified', time(), ['id' => $record->id]);
        }
        return count($records);
    }

    private static function result(
        int $inserted = 0,
        int $updated = 0,
        int $failed = 0,
        string $message = ''
    ): object {
        return (object) compact('inserted', 'updated', 'failed', 'message');
    }
}

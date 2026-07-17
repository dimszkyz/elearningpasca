<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakadbridge\sync;

defined('MOODLE_INTERNAL') || die();

use local_siakadbridge\manager;

/**
 * Imports a documented SIAKAD REST payload into Moodle-local bridge tables.
 */
final class service {
    /**
     * Synchronise using the configured source.
     */
    public static function run(): object {
        $mode = (string) get_config('local_siakadbridge', 'sourcemode');
        if ($mode !== 'rest') {
            return self::result(0, 0, 0, 'Manual mode: no remote synchronisation was performed.');
        }

        $url = trim((string) get_config('local_siakadbridge', 'apiurl'));
        if ($url === '') {
            throw new \moodle_exception('syncfailed', 'local_siakadbridge', '', 'SIAKAD API URL is empty.');
        }

        $lockfactory = \core\lock\lock_config::get_lock_factory('local_siakadbridge');
        $lock = $lockfactory->get_lock('sync', 0);
        if (!$lock) {
            throw new \moodle_exception('syncfailed', 'local_siakadbridge', '',
                'Another SIAKAD synchronisation is already running.');
        }

        try {
            $payload = self::fetch_payload($url);
            return self::import_payload($payload, 'rest');
        } finally {
            $lock->release();
        }
    }

    /**
     * Import a decoded payload. Public for CLI/tests and future adapters.
     */
    public static function import_payload(object $payload, string $source = 'manual'): object {
        global $DB;

        $data = isset($payload->data) && is_object($payload->data) ? $payload->data : $payload;
        $transaction = $DB->start_delegated_transaction();
        $result = self::result();

        try {
            $prodis = self::items($data, 'prodi');
            $users = self::items($data, 'users');
            $students = self::items($data, 'mahasiswa');
            $lecturers = self::items($data, 'dosen');
            $bills = self::items($data, 'tagihan');

            foreach ($prodis as $item) {
                self::upsert_prodi($item, $result);
            }
            foreach ($users as $item) {
                self::upsert_user($item, $result);
            }
            foreach ($students as $item) {
                self::upsert_mahasiswa($item, $result);
            }
            foreach ($lecturers as $item) {
                self::upsert_dosen($item, $result);
            }
            foreach ($bills as $item) {
                self::upsert_tagihan($item, $result);
            }
            if (!empty($data->fullsnapshot)) {
                self::deactivate_missing($prodis, $students, $lecturers, $bills);
            }
            $transaction->allow_commit();
        } catch (\Throwable $exception) {
            $result->failed++;
            $result->message = $exception->getMessage();
            try {
                $transaction->rollback($exception);
            } catch (\Throwable $rolledback) {
                self::write_log($source, 'failed', $result);
                throw $rolledback;
            }
            self::write_log($source, 'failed', $result);
            throw $exception;
        }

        try {
            manager::reconcile_moodle_access();
        } catch (\Throwable $exception) {
            $result->failed++;
            $result->message = 'Data imported, but Moodle access reconciliation failed: ' . $exception->getMessage();
            self::write_log($source, 'failed', $result);
            throw $exception;
        }

        self::write_log($source, 'success', $result);
        return $result;
    }

    private static function fetch_payload(string $url): object {
        global $CFG;

        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $headers = ['Accept: application/json'];
        $token = trim((string) get_config('local_siakadbridge', 'apitoken'));
        if ($token !== '') {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        $curl->setHeader($headers);

        $timeout = (int) get_config('local_siakadbridge', 'apitimeout');
        $timeout = $timeout > 0 ? $timeout : 30;
        $response = $curl->get($url, [], [
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
        ]);
        $info = $curl->get_info();
        $status = (int) ($info['http_code'] ?? 0);
        if ($response === false || $status < 200 || $status >= 300) {
            throw new \moodle_exception(
                'syncfailed',
                'local_siakadbridge',
                '',
                'HTTP ' . $status . ' while requesting SIAKAD API.'
            );
        }

        $payload = json_decode((string) $response);
        if (!is_object($payload) || json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception(
                'syncfailed',
                'local_siakadbridge',
                '',
                'Invalid JSON: ' . json_last_error_msg()
            );
        }
        return $payload;
    }

    /** @return array<int, object> */
    private static function items(object $data, string $property): array {
        $items = $data->{$property} ?? [];
        if (!is_array($items)) {
            throw new \invalid_parameter_exception($property . ' must be an array.');
        }
        return array_values(array_filter($items, 'is_object'));
    }

    /**
     * Mark records absent from an explicitly declared full snapshot as inactive/cancelled.
     *
     * @param array<int, object> $prodis
     * @param array<int, object> $students
     * @param array<int, object> $lecturers
     * @param array<int, object> $bills
     */
    private static function deactivate_missing(array $prodis, array $students, array $lecturers, array $bills): void {
        self::update_missing(
            'local_siakad_prodi',
            'kode',
            array_map(static fn(object $item): string => \core_text::strtoupper(trim((string) ($item->kode ?? ''))), $prodis),
            ['aktif' => 0, 'timemodified' => time()],
            'prodi'
        );
        self::update_missing(
            'local_siakad_mahasiswa',
            'nim',
            array_map(static fn(object $item): string => trim((string) ($item->nim ?? '')), $students),
            ['status' => 'nonaktif', 'timemodified' => time()],
            'student'
        );
        self::update_missing(
            'local_siakad_dosen',
            'nidn',
            array_map(static fn(object $item): string => trim((string) ($item->nidn ?? '')), $lecturers),
            ['status' => 'nonaktif', 'timemodified' => time()],
            'lecturer'
        );
        self::update_missing(
            'local_siakad_tagihan',
            'kodetagihan',
            array_map(static fn(object $item): string => trim((string) ($item->kodetagihan ?? '')), $bills),
            ['status' => manager::STATUS_DIBATALKAN, 'wajib' => 0, 'timemodified' => time()],
            'bill'
        );
    }

    private static function update_missing(
        string $table,
        string $identityfield,
        array $values,
        array $updates,
        string $prefix
    ): void {
        global $DB;

        $values = array_values(array_unique(array_filter($values, static fn(string $value): bool => $value !== '')));
        $select = '1 = 1';
        $params = [];
        if ($values) {
            [$insql, $inparams] = $DB->get_in_or_equal($values, SQL_PARAMS_NAMED, $prefix, false);
            $select = $identityfield . ' ' . $insql;
            $params = $inparams;
        }
        foreach ($updates as $field => $value) {
            $DB->set_field_select($table, $field, $value, $select, $params);
        }
    }

    private static function upsert_prodi(object $item, object $result): void {
        $kode = \core_text::strtoupper(self::required_text($item, 'kode'));
        $sourceid = self::optional_text($item, 'id');
        $record = (object) [
            'sourceid' => $sourceid !== '' ? $sourceid : null,
            'kode' => $kode,
            'nama' => self::required_text($item, 'nama'),
            'aktif' => isset($item->aktif) ? (int) (bool) $item->aktif : 1,
            'categoryid' => isset($item->categoryid) ? max(0, (int) $item->categoryid) : 0,
            'timemodified' => time(),
        ];
        self::upsert('local_siakad_prodi', self::identity('sourceid', $sourceid, 'kode', $kode), $record, $result);
    }

    private static function upsert_user(object $item, object $result): void {
        $username = \core_text::strtolower(self::required_text($item, 'username'));
        $sourceid = self::optional_text($item, 'id');
        $record = (object) [
            'sourceid' => $sourceid !== '' ? $sourceid : null,
            'username' => $username,
            'fullname' => self::required_text($item, 'fullname'),
            'email' => self::optional_text($item, 'email'),
            'role' => \core_text::strtolower(self::optional_text($item, 'role', 'mahasiswa')),
            'moodleuserid' => isset($item->moodleuserid) ? max(0, (int) $item->moodleuserid) : 0,
            'timemodified' => time(),
        ];
        self::upsert('local_siakad_user', self::identity('sourceid', $sourceid, 'username', $username), $record, $result);
    }

    private static function upsert_mahasiswa(object $item, object $result): void {
        global $DB;

        $nim = self::required_text($item, 'nim');
        $username = \core_text::strtolower(self::required_text($item, 'username'));
        $prodicode = \core_text::strtoupper(self::required_text($item, 'prodi'));
        $sourceid = self::optional_text($item, 'id');
        $user = $DB->get_record('local_siakad_user', ['username' => $username], '*', MUST_EXIST);
        $prodi = $DB->get_record('local_siakad_prodi', ['kode' => $prodicode], '*', MUST_EXIST);
        $record = (object) [
            'sourceid' => $sourceid !== '' ? $sourceid : null,
            'userid' => $user->id,
            'moodleuserid' => isset($item->moodleuserid) ? max(0, (int) $item->moodleuserid) : (int) $user->moodleuserid,
            'nim' => $nim,
            'nama' => self::required_text($item, 'nama'),
            'email' => self::optional_text($item, 'email'),
            'prodiid' => $prodi->id,
            'status' => \core_text::strtolower(self::optional_text($item, 'status', manager::STATUS_AKTIF)),
            'timemodified' => time(),
        ];
        self::upsert('local_siakad_mahasiswa', self::identity('sourceid', $sourceid, 'nim', $nim), $record, $result);
    }

    private static function upsert_dosen(object $item, object $result): void {
        global $DB;

        $nidn = self::required_text($item, 'nidn');
        $username = \core_text::strtolower(self::required_text($item, 'username'));
        $prodicode = \core_text::strtoupper(self::required_text($item, 'prodi'));
        $sourceid = self::optional_text($item, 'id');
        $user = $DB->get_record('local_siakad_user', ['username' => $username], '*', MUST_EXIST);
        $prodi = $DB->get_record('local_siakad_prodi', ['kode' => $prodicode], '*', MUST_EXIST);
        $record = (object) [
            'sourceid' => $sourceid !== '' ? $sourceid : null,
            'userid' => $user->id,
            'moodleuserid' => isset($item->moodleuserid) ? max(0, (int) $item->moodleuserid) : (int) $user->moodleuserid,
            'nidn' => $nidn,
            'nama' => self::required_text($item, 'nama'),
            'email' => self::optional_text($item, 'email'),
            'prodiid' => $prodi->id,
            'status' => \core_text::strtolower(self::optional_text($item, 'status', manager::STATUS_AKTIF)),
            'timemodified' => time(),
        ];
        self::upsert('local_siakad_dosen', self::identity('sourceid', $sourceid, 'nidn', $nidn), $record, $result);
    }

    private static function upsert_tagihan(object $item, object $result): void {
        global $DB;

        $code = self::required_text($item, 'kodetagihan');
        $nim = self::required_text($item, 'nim');
        $sourceid = self::optional_text($item, 'id');
        $student = $DB->get_record('local_siakad_mahasiswa', ['nim' => $nim], '*', MUST_EXIST);
        $status = \core_text::strtolower(self::optional_text($item, 'status', manager::STATUS_BELUM_LUNAS));
        if (!in_array($status, [manager::STATUS_LUNAS, manager::STATUS_BELUM_LUNAS, manager::STATUS_DIBATALKAN], true)) {
            throw new \invalid_parameter_exception('Invalid billing status: ' . $status);
        }
        $record = (object) [
            'sourceid' => $sourceid !== '' ? $sourceid : null,
            'mahasiswaid' => $student->id,
            'kodetagihan' => $code,
            'tahunajaran' => self::required_text($item, 'tahunajaran'),
            'semester' => \core_text::strtolower(self::required_text($item, 'semester')),
            'jenis' => self::optional_text($item, 'jenis', 'UKT'),
            'nominal' => max(0, (int) ($item->nominal ?? 0)),
            'status' => $status,
            'wajib' => isset($item->wajib) ? (int) (bool) $item->wajib : 1,
            'paidat' => self::timestamp($item->paidat ?? 0),
            'duedate' => self::timestamp($item->duedate ?? 0),
            'timemodified' => time(),
        ];
        if ($record->status === manager::STATUS_LUNAS && $record->paidat === 0) {
            $record->paidat = time();
        }
        if ($record->status !== manager::STATUS_LUNAS) {
            $record->paidat = 0;
        }
        self::upsert('local_siakad_tagihan', self::identity('sourceid', $sourceid, 'kodetagihan', $code), $record, $result);
    }

    private static function upsert(string $table, array $identity, object $record, object $result): void {
        global $DB;

        $existing = $DB->get_record($table, $identity, 'id', IGNORE_MISSING);
        // Manual/dummy records can predate source IDs. When the first real payload
        // introduces sourceid, reuse the natural unique key instead of inserting a duplicate.
        if (!$existing && !empty($record->sourceid)) {
            $fallbackfields = [
                'local_siakad_user' => 'username',
                'local_siakad_prodi' => 'kode',
                'local_siakad_mahasiswa' => 'nim',
                'local_siakad_dosen' => 'nidn',
                'local_siakad_tagihan' => 'kodetagihan',
            ];
            $fallbackfield = $fallbackfields[$table] ?? null;
            if ($fallbackfield !== null && property_exists($record, $fallbackfield)) {
                $existing = $DB->get_record(
                    $table,
                    [$fallbackfield => $record->{$fallbackfield}],
                    'id',
                    IGNORE_MISSING
                );
            }
        }

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record($table, $record);
            $result->updated++;
        } else {
            $DB->insert_record($table, $record);
            $result->inserted++;
        }
    }

    private static function identity(string $sourcefield, string $sourceid, string $fallbackfield, string $fallback): array {
        return $sourceid !== '' ? [$sourcefield => $sourceid] : [$fallbackfield => $fallback];
    }

    private static function required_text(object $item, string $property): string {
        $value = trim((string) ($item->{$property} ?? ''));
        if ($value === '') {
            throw new \invalid_parameter_exception('Missing required property: ' . $property);
        }
        return $value;
    }

    private static function optional_text(object $item, string $property, string $default = ''): string {
        return trim((string) ($item->{$property} ?? $default));
    }

    private static function timestamp(mixed $value): int {
        if (is_int($value) || ctype_digit((string) $value)) {
            return max(0, (int) $value);
        }
        $timestamp = strtotime((string) $value);
        return $timestamp === false ? 0 : $timestamp;
    }

    private static function result(int $inserted = 0, int $updated = 0, int $failed = 0, string $message = ''): object {
        return (object) compact('inserted', 'updated', 'failed', 'message');
    }

    private static function write_log(string $source, string $status, object $result): void {
        global $DB;

        $DB->insert_record('local_siakad_synclog', (object) [
            'source' => $source,
            'status' => $status,
            'inserted' => (int) $result->inserted,
            'updated' => (int) $result->updated,
            'failed' => (int) $result->failed,
            'message' => (string) $result->message,
            'timecreated' => time(),
        ]);
    }
}

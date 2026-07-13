<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascasync;

use stdClass;

/**
 * Synchronises Pasca users into Moodle accounts.
 *
 * @package    local_pascasync
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sync_service {
    /** @var api_client Pasca API client. */
    private api_client $client;

    /**
     * Constructor.
     *
     * @param api_client|null $client Optional injected client.
     */
    public function __construct(?api_client $client = null) {
        global $CFG;

        require_once($CFG->dirroot . '/user/lib.php');
        $this->client = $client ?? new api_client();
    }

    /**
     * Execute a full or incremental sync.
     *
     * @param string|null $updatedafter ISO-8601 timestamp, or null for full sync.
     * @return sync_result
     */
    public function sync(?string $updatedafter = null): sync_result {
        $result = new sync_result();
        $page = 1;
        $lastpage = 1;

        do {
            $payload = $this->client->fetch_page($page, $updatedafter);
            $lastpage = max(1, (int) ($payload['meta']['last_page'] ?? 1));

            foreach ($payload['data'] as $source) {
                if (!is_array($source)) {
                    $result->record_failure([], get_string('invaliduserrecord', 'local_pascasync'));
                    continue;
                }

                try {
                    $status = $this->sync_user($source);
                    $result->record_status($status);
                } catch (sync_conflict_exception $exception) {
                    $result->record_conflict($source, $exception->getMessage());
                } catch (\Throwable $exception) {
                    debugging($exception->getMessage(), DEBUG_DEVELOPER);
                    $result->record_failure($source, get_string('userprocessingfailed', 'local_pascasync'));
                }
            }

            $page++;
        } while ($page <= $lastpage);

        $enableemaillogin = get_config('local_pascasync', 'enableemaillogin');
        if ($enableemaillogin === false || (bool) $enableemaillogin) {
            set_config('authloginviaemail', 1);
        }

        return $result;
    }

    /**
     * Synchronise one source record.
     *
     * @param array $source Pasca user payload.
     * @return string created, updated, or unchanged.
     */
    private function sync_user(array $source): string {
        global $DB, $CFG;

        $sourceid = (int) ($source['source_id'] ?? 0);
        $name = preg_replace('/\s+/u', ' ', trim((string) ($source['name'] ?? '')));
        $email = \core_text::strtolower(trim((string) ($source['email'] ?? '')));
        $passwordhash = (string) ($source['password_hash'] ?? '');

        if ($sourceid < 1 || $name === '' || !validate_email($email)) {
            throw new sync_conflict_exception(get_string('invalidsourceidentity', 'local_pascasync'));
        }
        if (!$this->is_supported_password_hash($passwordhash)) {
            throw new sync_conflict_exception(get_string('unsupportedpasswordhash', 'local_pascasync'));
        }

        $transaction = $DB->start_delegated_transaction();
        try {
            [$firstname, $lastname] = $this->split_name($name);
            $idnumber = 'pasca:' . $sourceid;
            [$user, $map] = $this->find_user($sourceid, $idnumber, $email);

            if ($user !== null) {
                if (isguestuser($user) || is_siteadmin($user)) {
                    throw new sync_conflict_exception(get_string('protectedaccount', 'local_pascasync'));
                }
                if ($user->auth !== 'manual') {
                    throw new sync_conflict_exception(get_string('externalauthaccount', 'local_pascasync'));
                }
            }

            $this->assert_email_available($email, $user?->id);
            $this->assert_idnumber_available($idnumber, $user?->id);

            if ($user !== null) {
                $othermap = $DB->get_record('local_pascasync_map', ['userid' => $user->id]);
                if ($othermap && (int) $othermap->sourceid !== $sourceid) {
                    throw new sync_conflict_exception(get_string('usermappedtoothersource', 'local_pascasync'));
                }
            }

            $username = $this->generate_unique_username($name, $sourceid, $user?->id);
            $profilechanged = false;
            $created = false;

            if ($user === null) {
                $newuser = (object) [
                    'auth' => 'manual',
                    'confirmed' => 1,
                    'mnethostid' => $CFG->mnet_localhost_id,
                    'username' => $username,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email,
                    'idnumber' => $idnumber,
                ];
                $userid = user_create_user($newuser, false, true);
                $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
                $created = true;
                $profilechanged = true;
            } else {
                $changes = (object) ['id' => $user->id];
                foreach ([
                    'username' => $username,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'email' => $email,
                    'idnumber' => $idnumber,
                    'confirmed' => 1,
                ] as $field => $value) {
                    if ((string) $user->{$field} !== (string) $value) {
                        $changes->{$field} = $value;
                        $profilechanged = true;
                    }
                }

                if ($profilechanged) {
                    user_update_user($changes, false, true);
                    $user = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
                }
            }

            $fingerprint = hash('sha256', $passwordhash);
            $passwordchanged = !$map || empty($map->passwordfingerprint) ||
                !hash_equals((string) $map->passwordfingerprint, $fingerprint);
            if ($passwordchanged) {
                $this->set_raw_password_hash((int) $user->id, $passwordhash);
            }

            $this->save_map(
                $map,
                (int) $user->id,
                $sourceid,
                $fingerprint,
                $this->parse_source_time($source['updated_at'] ?? null),
            );

            $transaction->allow_commit();

            if ($created) {
                return 'created';
            }

            return ($profilechanged || $passwordchanged) ? 'updated' : 'unchanged';
        } catch (\Throwable $exception) {
            $transaction->rollback($exception);
        }
    }

    /**
     * Find a Moodle user by mapping, idnumber, then unique email.
     *
     * @return array{0: stdClass|null, 1: stdClass|null}
     */
    private function find_user(int $sourceid, string $idnumber, string $email): array {
        global $DB, $CFG;

        $map = $DB->get_record('local_pascasync_map', ['sourceid' => $sourceid]);
        if ($map) {
            $user = $DB->get_record('user', ['id' => $map->userid, 'deleted' => 0]);
            if ($user) {
                return [$user, $map];
            }
            $DB->delete_records('local_pascasync_map', ['id' => $map->id]);
            $map = null;
        }

        $idmatches = $DB->get_records('user', [
            'idnumber' => $idnumber,
            'deleted' => 0,
            'mnethostid' => $CFG->mnet_localhost_id,
        ], 'id ASC', '*', 0, 2);
        if (count($idmatches) > 1) {
            throw new sync_conflict_exception(get_string('duplicateidnumber', 'local_pascasync'));
        }
        if ($idmatches) {
            return [reset($idmatches), null];
        }

        $emailcondition = $DB->sql_equal('email', ':email', false);
        $emailmatches = $DB->get_records_select(
            'user',
            "mnethostid = :mnethostid AND deleted = 0 AND {$emailcondition}",
            ['mnethostid' => $CFG->mnet_localhost_id, 'email' => $email],
            'id ASC',
            '*',
            0,
            2,
        );
        if (count($emailmatches) > 1) {
            throw new sync_conflict_exception(get_string('duplicateemail', 'local_pascasync'));
        }

        return [$emailmatches ? reset($emailmatches) : null, null];
    }

    /**
     * Ensure an email belongs to at most the target account.
     */
    private function assert_email_available(string $email, ?int $userid): void {
        global $DB, $CFG;

        $emailcondition = $DB->sql_equal('email', ':email', false);
        $select = "mnethostid = :mnethostid AND deleted = 0 AND {$emailcondition}";
        $params = ['mnethostid' => $CFG->mnet_localhost_id, 'email' => $email];
        if ($userid !== null) {
            $select .= ' AND id <> :userid';
            $params['userid'] = $userid;
        }

        if ($DB->record_exists_select('user', $select, $params)) {
            throw new sync_conflict_exception(get_string('emailalreadyused', 'local_pascasync'));
        }
    }

    /**
     * Ensure an idnumber belongs to at most the target account.
     */
    private function assert_idnumber_available(string $idnumber, ?int $userid): void {
        global $DB, $CFG;

        $select = 'idnumber = :idnumber AND deleted = 0 AND mnethostid = :mnethostid';
        $params = ['idnumber' => $idnumber, 'mnethostid' => $CFG->mnet_localhost_id];
        if ($userid !== null) {
            $select .= ' AND id <> :userid';
            $params['userid'] = $userid;
        }

        if ($DB->record_exists_select('user', $select, $params)) {
            throw new sync_conflict_exception(get_string('idnumberalreadyused', 'local_pascasync'));
        }
    }

    /**
     * Generate a stable unique Moodle username from the Pasca name.
     */
    private function generate_unique_username(string $name, int $sourceid, ?int $userid): string {
        global $DB, $CFG;

        $base = \core_text::strtolower($name);
        $base = preg_replace('/[^\pL\pN._-]+/u', '.', $base);
        $base = trim((string) $base, '.-_');
        $base = clean_param($base, PARAM_USERNAME);
        if ($base === '') {
            $base = 'pasca.' . $sourceid;
        }
        $base = \core_text::substr($base, 0, 100);

        $candidate = $base;
        $counter = 0;
        while (true) {
            $existing = $DB->get_record('user', [
                'username' => $candidate,
                'mnethostid' => $CFG->mnet_localhost_id,
            ], 'id');
            if (!$existing || ($userid !== null && (int) $existing->id === $userid)) {
                return $candidate;
            }

            $suffix = '.' . $sourceid . ($counter > 0 ? '.' . $counter : '');
            $candidate = \core_text::substr($base, 0, 100 - \core_text::strlen($suffix)) . $suffix;
            $counter++;
        }
    }

    /**
     * Split one full name while preserving its displayed order.
     *
     * @return array{0: string, 1: string}
     */
    private function split_name(string $name): array {
        $parts = preg_split('/\s+/u', trim($name), 2);
        $firstname = (string) ($parts[0] ?? '');
        $lastname = (string) ($parts[1] ?? '-');

        return [$firstname, $lastname];
    }

    /**
     * Save or update the source mapping record.
     */
    private function save_map(
        ?stdClass $map,
        int $userid,
        int $sourceid,
        string $fingerprint,
        int $sourceupdatedat,
    ): void {
        global $DB;

        $now = time();
        if ($map) {
            $map->userid = $userid;
            $map->sourceid = $sourceid;
            $map->passwordfingerprint = $fingerprint;
            $map->sourceupdatedat = $sourceupdatedat;
            $map->timemodified = $now;
            $DB->update_record('local_pascasync_map', $map);
            return;
        }

        $DB->insert_record('local_pascasync_map', (object) [
            'userid' => $userid,
            'sourceid' => $sourceid,
            'passwordfingerprint' => $fingerprint,
            'sourceupdatedat' => $sourceupdatedat,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Store the source bcrypt hash without hashing it again.
     */
    private function set_raw_password_hash(int $userid, string $passwordhash): void {
        global $DB, $CFG;

        $DB->set_field('user', 'password', $passwordhash, ['id' => $userid]);
        \core\session\manager::destroy_user_sessions($userid);

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        \core\event\user_password_updated::create_from_user($user)->trigger();

        if (!empty($CFG->passwordchangetokendeletion)) {
            require_once($CFG->dirroot . '/webservice/lib.php');
            \webservice::delete_user_ws_tokens($userid);
        }
    }

    /**
     * Confirm the source hash is Moodle-compatible Laravel bcrypt.
     */
    private function is_supported_password_hash(string $hash): bool {
        return (bool) preg_match('/^\$2y\$[0-9]{2}\$[A-Za-z0-9.\/]{53}$/', $hash);
    }

    /**
     * Parse an API ISO-8601 timestamp to Unix time.
     */
    private function parse_source_time(mixed $value): int {
        if (!is_string($value) || $value === '') {
            return 0;
        }

        $timestamp = strtotime($value);
        return $timestamp === false ? 0 : $timestamp;
    }
}

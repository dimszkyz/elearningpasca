<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_siakadbridge;

/**
 * Helper methods for the local SIAKAD dummy tables.
 *
 * The dummy tables intentionally use the local_siakad_* prefix to avoid
 * collisions with Moodle core tables such as {user}. They model the SIAKAD
 * entities requested by the integration: user, mahasiswa, prodi, tagihan, and dosen.
 *
 * @package    local_siakadbridge
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** Paid bill status in the dummy SIAKAD table. */
    public const STATUS_LUNAS = 'lunas';

    /** Active student/lecturer status in the dummy SIAKAD table. */
    public const STATUS_AKTIF = 'aktif';

    /** Wildcard code used by the availability condition for any study program. */
    public const PRODI_ANY = '*';

    /**
     * Returns active study programs as an array suitable for UI selectors.
     *
     * @return array<int, object> Objects with code and name properties.
     */
    public static function get_active_prodi_options(): array {
        global $DB;

        $records = $DB->get_records('local_siakad_prodi', ['aktif' => 1], 'nama ASC', 'id,kode,nama');
        $options = [];
        foreach ($records as $record) {
            $options[] = (object) [
                'code' => $record->kode,
                'name' => $record->nama . ' (' . $record->kode . ')',
            ];
        }

        return $options;
    }

    /**
     * Checks whether a Moodle user may access an exam for the selected study program.
     *
     * Access is granted when:
     * - the Moodle user can be matched to an active SIAKAD mahasiswa record, and
     * - the mahasiswa belongs to the selected prodi, unless wildcard is used, and
     * - the mahasiswa has at least one lunas tagihan record.
     *
     * @param int $moodleuserid Moodle user id.
     * @param string $prodicode Study program code, or self::PRODI_ANY.
     * @return bool
     */
    public static function can_user_access_exam(int $moodleuserid, string $prodicode): bool {
        global $DB;

        $mahasiswa = self::get_mahasiswa_for_moodle_user($moodleuserid);
        if (!$mahasiswa) {
            return false;
        }

        if (!self::is_user_in_prodi((int) $mahasiswa->prodiid, $prodicode)) {
            return false;
        }

        return $DB->record_exists('local_siakad_tagihan', [
            'mahasiswaid' => $mahasiswa->id,
            'status' => self::STATUS_LUNAS,
        ]);
    }

    /**
     * Finds the SIAKAD mahasiswa record for a Moodle user.
     *
     * The primary mapping is local_siakad_mahasiswa.moodleuserid. For dummy
     * development, it also falls back to matching Moodle username/email with
     * local_siakad_user.username/email, so testing is possible without manually
     * storing the Moodle id in the dummy table.
     *
     * @param int $moodleuserid Moodle user id.
     * @return object|null
     */
    public static function get_mahasiswa_for_moodle_user(int $moodleuserid): ?object {
        global $DB;

        $moodleuser = $DB->get_record('user', ['id' => $moodleuserid], 'id,username,email', IGNORE_MISSING);

        $conditions = ['m.moodleuserid = :moodleuserid'];
        $params = [
            'moodleuserid' => $moodleuserid,
            'status' => self::STATUS_AKTIF,
        ];

        if ($moodleuser && $moodleuser->username !== '') {
            $conditions[] = 'u.username = :username';
            $params['username'] = $moodleuser->username;
        }

        if ($moodleuser && $moodleuser->email !== '') {
            $conditions[] = 'u.email = :email';
            $params['email'] = $moodleuser->email;
        }

        $sql = 'SELECT m.*
                  FROM {local_siakad_mahasiswa} m
                  JOIN {local_siakad_user} u ON u.id = m.userid
                 WHERE m.status = :status
                   AND (' . implode(' OR ', $conditions) . ')
              ORDER BY m.id ASC';

        $record = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);

        return $record ?: null;
    }

    /**
     * Checks whether a prodi id matches the requested prodi code.
     *
     * @param int $prodiid SIAKAD prodi id.
     * @param string $prodicode Requested prodi code or wildcard.
     * @return bool
     */
    private static function is_user_in_prodi(int $prodiid, string $prodicode): bool {
        global $DB;

        $prodicode = trim($prodicode);
        if ($prodicode === self::PRODI_ANY) {
            return true;
        }

        $prodi = $DB->get_record('local_siakad_prodi', ['id' => $prodiid, 'aktif' => 1], 'id,kode', IGNORE_MISSING);
        if (!$prodi) {
            return false;
        }

        return self::normalise_code($prodi->kode) === self::normalise_code($prodicode);
    }

    /**
     * Normalises a study program code for comparison.
     *
     * @param string $code Prodi code.
     * @return string
     */
    private static function normalise_code(string $code): string {
        return \core_text::strtolower(trim($code));
    }
}

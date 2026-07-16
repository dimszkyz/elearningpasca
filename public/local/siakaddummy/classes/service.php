<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_siakaddummy;

/**
 * Service layer for dummy SIAKAD data and quiz eligibility checks.
 *
 * The access-rule plugin calls this class instead of querying tables directly.
 * A future real SIAKAD API adapter can therefore replace this implementation
 * without changing the quiz access rule.
 *
 * @package    local_siakaddummy
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class service {
    /**
     * Insert or refresh deterministic demonstration data.
     *
     * @return array<string, int> Current record counts.
     */
    public static function seed_demo_data(): array {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $now = time();

        $prodiids = [];
        foreach ([
            'MM' => 'Magister Manajemen',
            'MH' => 'Magister Hukum',
            'MP' => 'Magister Pendidikan',
        ] as $code => $name) {
            $record = $DB->get_record('local_siad_prodi', ['code' => $code]);
            if ($record) {
                $record->name = $name;
                $record->active = 1;
                $record->timemodified = $now;
                $DB->update_record('local_siad_prodi', $record);
                $prodiids[$code] = (int) $record->id;
            } else {
                $prodiids[$code] = (int) $DB->insert_record('local_siad_prodi', (object) [
                    'code' => $code,
                    'name' => $name,
                    'active' => 1,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ]);
            }
        }

        $students = [
            [
                'sourceid' => 1001,
                'name' => 'Ahmad Rizky',
                'email' => 'ahmad.rizky@example.test',
                'username' => 'ahmad.rizky',
                'nim' => '2601001001',
                'prodi' => 'MM',
                'billstatus' => 'lunas',
            ],
            [
                'sourceid' => 1002,
                'name' => 'Siti Aminah',
                'email' => 'siti.aminah@example.test',
                'username' => 'siti.aminah',
                'nim' => '2602001002',
                'prodi' => 'MH',
                'billstatus' => 'belum_bayar',
            ],
            [
                'sourceid' => 1003,
                'name' => 'Rina Permata',
                'email' => 'rina.permata@example.test',
                'username' => 'rina.permata',
                'nim' => '2603001003',
                'prodi' => 'MP',
                'billstatus' => 'sebagian',
            ],
        ];

        foreach ($students as $item) {
            $sourceuserid = self::upsert_source_user(
                $item['sourceid'],
                $item['name'],
                $item['email'],
                $item['username'],
                'mahasiswa',
                $now,
            );

            $student = $DB->get_record('local_siad_mahasiswa', ['userid' => $sourceuserid]);
            $studentdata = (object) [
                'userid' => $sourceuserid,
                'nim' => $item['nim'],
                'prodiid' => $prodiids[$item['prodi']],
                'cohortyear' => 2026,
                'currentsemester' => 1,
                'status' => 'aktif',
                'timemodified' => $now,
            ];
            if ($student) {
                $studentdata->id = $student->id;
                $DB->update_record('local_siad_mahasiswa', $studentdata);
                $studentid = (int) $student->id;
            } else {
                $studentdata->timecreated = $now;
                $studentid = (int) $DB->insert_record('local_siad_mahasiswa', $studentdata);
            }

            $invoice = 'INV-UJIAN-2026-' . $item['nim'];
            $bill = $DB->get_record('local_siad_tagihan', ['invoice' => $invoice]);
            $billdata = (object) [
                'mahasiswaid' => $studentid,
                'invoice' => $invoice,
                'type' => 'ujian_semester',
                'academicyear' => '2026/2027',
                'semester' => 1,
                'amount' => 5000000,
                'status' => $item['billstatus'],
                'examrequirement' => 1,
                'dueat' => strtotime('2026-07-31 23:59:59'),
                'paidat' => $item['billstatus'] === 'lunas' ? $now : null,
                'timemodified' => $now,
            ];
            if ($bill) {
                $billdata->id = $bill->id;
                $DB->update_record('local_siad_tagihan', $billdata);
            } else {
                $billdata->timecreated = $now;
                $DB->insert_record('local_siad_tagihan', $billdata);
            }
        }

        $lectureruserid = self::upsert_source_user(
            2001,
            'Dr. Budi Santoso',
            'budi.santoso@example.test',
            'budi.santoso',
            'dosen',
            $now,
        );
        $lecturer = $DB->get_record('local_siad_dosen', ['userid' => $lectureruserid]);
        $lecturerdata = (object) [
            'userid' => $lectureruserid,
            'nidn' => '0016078001',
            'prodiid' => $prodiids['MM'],
            'status' => 'aktif',
            'timemodified' => $now,
        ];
        if ($lecturer) {
            $lecturerdata->id = $lecturer->id;
            $DB->update_record('local_siad_dosen', $lecturerdata);
        } else {
            $lecturerdata->timecreated = $now;
            $DB->insert_record('local_siad_dosen', $lecturerdata);
        }

        $transaction->allow_commit();

        return self::get_counts();
    }

    /**
     * Match dummy users to existing Moodle users by unique email address.
     *
     * @return int Number of links created or refreshed.
     */
    public static function link_moodle_users_by_email(): int {
        global $DB;

        $linked = 0;
        $now = time();
        foreach ($DB->get_records('local_siad_user', ['active' => 1]) as $sourceuser) {
            $matches = $DB->get_records('user', [
                'email' => $sourceuser->email,
                'deleted' => 0,
            ], 'id ASC', 'id,email', 0, 2);

            if (count($matches) !== 1) {
                continue;
            }

            $moodleuser = reset($matches);
            if ((int) $sourceuser->moodleuserid !== (int) $moodleuser->id) {
                $sourceuser->moodleuserid = $moodleuser->id;
                $sourceuser->timemodified = $now;
                $DB->update_record('local_siad_user', $sourceuser);
            }
            $linked++;
        }

        return $linked;
    }

    /**
     * Change a dummy bill status from the administration page.
     *
     * @param int $billid Billing record id.
     * @param string $status New status.
     */
    public static function set_bill_status(int $billid, string $status): void {
        global $DB;

        $allowed = ['belum_bayar', 'sebagian', 'lunas', 'dibatalkan'];
        if (!in_array($status, $allowed, true)) {
            throw new \invalid_parameter_exception('Unsupported billing status.');
        }

        $bill = $DB->get_record('local_siad_tagihan', ['id' => $billid], '*', MUST_EXIST);
        $bill->status = $status;
        $bill->paidat = $status === 'lunas' ? time() : null;
        $bill->timemodified = time();
        $DB->update_record('local_siad_tagihan', $bill);
    }

    /**
     * Return active study programmes for quiz settings.
     *
     * @return array<int, string>
     */
    public static function get_prodi_options(): array {
        global $DB;

        $options = [];
        foreach ($DB->get_records('local_siad_prodi', ['active' => 1], 'name ASC') as $prodi) {
            $options[(int) $prodi->id] = $prodi->code . ' - ' . $prodi->name;
        }
        return $options;
    }

    /**
     * Return selected programme names.
     *
     * @param int[] $prodiids Programme ids.
     * @return string[]
     */
    public static function get_prodi_names(array $prodiids): array {
        global $DB;

        $prodiids = array_values(array_unique(array_filter(array_map('intval', $prodiids))));
        if (!$prodiids) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($prodiids, SQL_PARAMS_NAMED, 'siadprodi');
        $records = $DB->get_records_select('local_siad_prodi', "id {$insql}", $params, 'name ASC');
        return array_map(static fn($record): string => $record->code . ' - ' . $record->name, $records);
    }

    /**
     * Evaluate whether a Moodle user may access a restricted quiz.
     *
     * @param int $moodleuserid Moodle user id.
     * @param int[] $allowedprodiids Study programmes selected by the lecturer.
     * @param bool $requirepayment Whether required bills must be paid.
     * @param string $academicyear Academic year filter.
     * @param int $semester Semester filter.
     * @param string $billtype Optional billing type filter.
     * @return array{allowed: bool, reason: string}
     */
    public static function evaluate_quiz_access(
        int $moodleuserid,
        array $allowedprodiids,
        bool $requirepayment,
        string $academicyear,
        int $semester,
        string $billtype,
    ): array {
        global $DB;

        $sourceuser = $DB->get_record('local_siad_user', [
            'moodleuserid' => $moodleuserid,
            'role' => 'mahasiswa',
            'active' => 1,
        ]);

        if (!$sourceuser) {
            $moodleuser = $DB->get_record('user', ['id' => $moodleuserid, 'deleted' => 0], 'id,email');
            if ($moodleuser) {
                $sourceuser = $DB->get_record('local_siad_user', [
                    'email' => $moodleuser->email,
                    'role' => 'mahasiswa',
                    'active' => 1,
                ]);
            }
        }

        if (!$sourceuser) {
            return [
                'allowed' => false,
                'reason' => get_string('accessdeniednotlinked', 'local_siakaddummy'),
            ];
        }

        $student = $DB->get_record('local_siad_mahasiswa', ['userid' => $sourceuser->id]);
        if (!$student || $student->status !== 'aktif') {
            return [
                'allowed' => false,
                'reason' => get_string('accessdeniedinactive', 'local_siakaddummy'),
            ];
        }

        $allowedprodiids = array_values(array_unique(array_filter(array_map('intval', $allowedprodiids))));
        if ($allowedprodiids && !in_array((int) $student->prodiid, $allowedprodiids, true)) {
            $prodi = $DB->get_record('local_siad_prodi', ['id' => $student->prodiid]);
            return [
                'allowed' => false,
                'reason' => get_string('accessdeniedprodi', 'local_siakaddummy',
                    $prodi ? $prodi->name : get_string('unknown')),
            ];
        }

        if (!$requirepayment) {
            return ['allowed' => true, 'reason' => ''];
        }

        $conditions = [
            'mahasiswaid = :mahasiswaid',
            'examrequirement = 1',
            "status <> :cancelled",
        ];
        $params = [
            'mahasiswaid' => $student->id,
            'cancelled' => 'dibatalkan',
        ];

        if ($academicyear !== '') {
            $conditions[] = 'academicyear = :academicyear';
            $params['academicyear'] = $academicyear;
        }
        if ($semester > 0) {
            $conditions[] = 'semester = :semester';
            $params['semester'] = $semester;
        }
        if ($billtype !== '') {
            $conditions[] = 'type = :billtype';
            $params['billtype'] = $billtype;
        }

        $bills = $DB->get_records_select(
            'local_siad_tagihan',
            implode(' AND ', $conditions),
            $params,
            'id ASC',
        );

        if (!$bills) {
            return [
                'allowed' => false,
                'reason' => get_string('accessdeniednobill', 'local_siakaddummy'),
            ];
        }

        foreach ($bills as $bill) {
            if ($bill->status !== 'lunas') {
                return [
                    'allowed' => false,
                    'reason' => get_string('accessdeniedunpaid', 'local_siakaddummy', (object) [
                        'invoice' => $bill->invoice,
                        'status' => get_string('billstatus_' . $bill->status, 'local_siakaddummy'),
                    ]),
                ];
            }
        }

        return ['allowed' => true, 'reason' => ''];
    }

    /**
     * Return record totals for the administration summary.
     *
     * @return array<string, int>
     */
    public static function get_counts(): array {
        global $DB;

        return [
            'users' => $DB->count_records('local_siad_user'),
            'students' => $DB->count_records('local_siad_mahasiswa'),
            'programmes' => $DB->count_records('local_siad_prodi'),
            'bills' => $DB->count_records('local_siad_tagihan'),
            'lecturers' => $DB->count_records('local_siad_dosen'),
        ];
    }

    /**
     * Insert or update one source user while preserving its Moodle link.
     */
    private static function upsert_source_user(
        int $sourceid,
        string $name,
        string $email,
        string $username,
        string $role,
        int $now,
    ): int {
        global $DB;

        $record = $DB->get_record('local_siad_user', ['sourceid' => $sourceid]);
        if ($record) {
            $record->name = $name;
            $record->email = $email;
            $record->username = $username;
            $record->role = $role;
            $record->active = 1;
            $record->timemodified = $now;
            $DB->update_record('local_siad_user', $record);
            return (int) $record->id;
        }

        return (int) $DB->insert_record('local_siad_user', (object) [
            'sourceid' => $sourceid,
            'moodleuserid' => null,
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'passwordhash' => password_hash('Password123!', PASSWORD_BCRYPT),
            'role' => $role,
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}

<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakadbridge;

defined('MOODLE_INTERNAL') || die();

/**
 * Core SIAKAD bridge service.
 */
final class manager {
    public const STATUS_LUNAS = 'lunas';
    public const STATUS_BELUM_LUNAS = 'belum_lunas';
    public const STATUS_DIBATALKAN = 'dibatalkan';
    public const STATUS_AKTIF = 'aktif';
    public const PRODI_ANY = '*';

    /** @return array<int, object> */
    public static function get_active_prodi_options(): array {
        global $DB;
        $records = $DB->get_records('local_siakad_prodi', ['aktif' => 1], 'nama ASC', 'id,kode,nama');
        $options = [];
        foreach ($records as $record) {
            $options[] = (object) ['code' => $record->kode, 'name' => $record->nama . ' (' . $record->kode . ')'];
        }
        return $options;
    }

    public static function get_availability_defaults(): object {
        $year = get_config('local_siakadbridge', 'currentyear');
        $semester = get_config('local_siakadbridge', 'currentsemester');
        return (object) [
            'tahunajaran' => $year === false ? '2026/2027' : trim((string) $year),
            'semester' => $semester === false ? 'genap' : trim((string) $semester),
            'jenis' => '',
        ];
    }

    public static function can_user_access_exam(int $moodleuserid, string $prodicode,
            string $tahunajaran = '', string $semester = '', string $jenis = ''): bool {
        return self::get_exam_access_decision($moodleuserid, $prodicode, $tahunajaran, $semester, $jenis)->allowed;
    }

    public static function get_exam_access_decision(int $moodleuserid, string $prodicode,
            string $tahunajaran = '', string $semester = '', string $jenis = ''): object {
        global $DB;

        $mahasiswa = self::get_mahasiswa_for_moodle_user($moodleuserid);
        if (!$mahasiswa) {
            return self::decision(false, 'student_not_found');
        }
        if (!self::is_user_in_prodi((int) $mahasiswa->prodiid, $prodicode)) {
            return self::decision(false, 'wrong_study_program');
        }

        $defaults = self::get_availability_defaults();
        $tahunajaran = trim($tahunajaran) !== '' ? trim($tahunajaran) : $defaults->tahunajaran;
        $semester = trim($semester) !== '' ? self::normalise_code($semester) : self::normalise_code($defaults->semester);
        $jenis = trim($jenis);
        $where = ['mahasiswaid = :mahasiswaid'];
        $params = ['mahasiswaid' => (int) $mahasiswa->id];
        if ($tahunajaran !== '') {
            $where[] = 'tahunajaran = :tahunajaran';
            $params['tahunajaran'] = $tahunajaran;
        }
        if ($semester !== '') {
            $where[] = 'LOWER(semester) = :semester';
            $params['semester'] = $semester;
        }
        if ($jenis !== '') {
            $where[] = 'LOWER(jenis) = :jenis';
            $params['jenis'] = self::normalise_code($jenis);
        }

        $bills = $DB->get_records_select('local_siakad_tagihan', implode(' AND ', $where), $params, 'id ASC');
        if (!$bills) {
            return self::decision(false, 'bill_not_found');
        }
        $mode = (string) get_config('local_siakadbridge', 'gatemode');
        if ($mode === 'any_lunas') {
            foreach ($bills as $bill) {
                if (self::normalise_code($bill->status) === self::STATUS_LUNAS) {
                    return self::decision(true, 'paid_bill_found', [$bill->id]);
                }
            }
            return self::decision(false, 'no_paid_bill');
        }

        $required = array_filter($bills, static fn($bill): bool =>
            (int) $bill->wajib === 1 && self::normalise_code($bill->status) !== self::STATUS_DIBATALKAN
        );
        if (!$required) {
            return self::decision(false, 'required_bill_not_found');
        }
        $unpaidids = [];
        foreach ($required as $bill) {
            if (self::normalise_code($bill->status) !== self::STATUS_LUNAS) {
                $unpaidids[] = (int) $bill->id;
            }
        }
        if ($unpaidids) {
            return self::decision(false, 'required_bill_unpaid', $unpaidids);
        }
        return self::decision(true, 'all_required_bills_paid', array_map('intval', array_keys($required)));
    }

    public static function get_mahasiswa_for_moodle_user(int $moodleuserid): ?object {
        global $DB;
        $moodleuser = $DB->get_record('user', ['id' => $moodleuserid], 'id,username,email', IGNORE_MISSING);
        if (!$moodleuser) {
            return null;
        }
        $conditions = ['m.moodleuserid = :studentmoodleid', 'u.moodleuserid = :usermoodleid'];
        $params = ['studentmoodleid' => $moodleuserid, 'usermoodleid' => $moodleuserid, 'status' => self::STATUS_AKTIF];
        if ($moodleuser->username !== '') {
            $conditions[] = 'LOWER(u.username) = :username';
            $params['username'] = self::normalise_code($moodleuser->username);
        }
        if ($moodleuser->email !== '') {
            $conditions[] = 'LOWER(u.email) = :email';
            $params['email'] = self::normalise_code($moodleuser->email);
        }
        $sql = 'SELECT m.* FROM {local_siakad_mahasiswa} m
                  JOIN {local_siakad_user} u ON u.id = m.userid
                 WHERE LOWER(m.status) = :status
                   AND (' . implode(' OR ', $conditions) . ')
              ORDER BY m.id ASC';
        $record = $DB->get_record_sql($sql, $params, IGNORE_MULTIPLE);
        return $record ?: null;
    }

    public static function link_moodle_users(): int {
        global $DB;
        $linked = 0;
        foreach ($DB->get_records('local_siakad_user') as $record) {
            $moodleuser = false;
            if ($record->username !== '') {
                $moodleuser = $DB->get_record('user', ['username' => $record->username, 'deleted' => 0], 'id', IGNORE_MISSING);
            }
            if (!$moodleuser && $record->email !== '') {
                $users = $DB->get_records('user', ['email' => $record->email, 'deleted' => 0], 'id ASC', 'id', 0, 2);
                if (count($users) === 1) {
                    $moodleuser = reset($users);
                }
            }
            if (!$moodleuser) {
                continue;
            }
            $changed = false;
            if ((int) $record->moodleuserid !== (int) $moodleuser->id) {
                $record->moodleuserid = (int) $moodleuser->id;
                $record->timemodified = time();
                $DB->update_record('local_siakad_user', $record);
                $changed = true;
            }
            foreach ($DB->get_records('local_siakad_mahasiswa', ['userid' => $record->id], '', 'id,moodleuserid') as $student) {
                if ((int) $student->moodleuserid !== (int) $moodleuser->id) {
                    $DB->set_field('local_siakad_mahasiswa', 'moodleuserid', $moodleuser->id, ['id' => $student->id]);
                    $changed = true;
                }
            }
            foreach ($DB->get_records('local_siakad_dosen', ['userid' => $record->id], '', 'id,moodleuserid') as $lecturer) {
                if ((int) $lecturer->moodleuserid !== (int) $moodleuser->id) {
                    $DB->set_field('local_siakad_dosen', 'moodleuserid', $moodleuser->id, ['id' => $lecturer->id]);
                    $changed = true;
                }
            }
            if ($changed) {
                $linked++;
            }
        }
        return $linked;
    }

    public static function reconcile_moodle_access(): object {
        global $CFG, $DB;
        $result = (object) ['linked' => self::link_moodle_users(), 'students' => 0, 'lecturers' => 0];
        require_once($CFG->dirroot . '/cohort/lib.php');

        $students = $DB->get_records_select('local_siakad_mahasiswa',
            'moodleuserid > 0 AND LOWER(status) = :status', ['status' => self::STATUS_AKTIF]);
        foreach ($students as $student) {
            $prodi = $DB->get_record('local_siakad_prodi', ['id' => $student->prodiid, 'aktif' => 1]);
            if (!$prodi || (int) $prodi->categoryid <= 0 || !class_exists('\\local_pascaprodi\\manager')) {
                continue;
            }
            $cohortidnumber = \local_pascaprodi\manager::cohort_idnumber((int) $prodi->categoryid);
            $cohort = $DB->get_record('cohort', ['idnumber' => $cohortidnumber], 'id', IGNORE_MISSING);
            if (!$cohort) {
                \local_pascaprodi\manager::ensure_category_cohort((int) $prodi->categoryid);
                $cohort = $DB->get_record('cohort', ['idnumber' => $cohortidnumber], 'id', IGNORE_MISSING);
            }
            if ($cohort) {
                $like = $DB->sql_like('c.idnumber', ':prefix', false);
                $memberships = $DB->get_records_sql(
                    'SELECT cm.id, cm.cohortid FROM {cohort_members} cm
                       JOIN {cohort} c ON c.id = cm.cohortid
                      WHERE cm.userid = :userid AND ' . $like . ' AND c.id <> :targetcohort',
                    ['userid' => $student->moodleuserid, 'prefix' => 'pasca:prodi-category:%', 'targetcohort' => $cohort->id]
                );
                foreach ($memberships as $membership) {
                    cohort_remove_member((int) $membership->cohortid, (int) $student->moodleuserid);
                }
                if (!$DB->record_exists('cohort_members', ['cohortid' => $cohort->id, 'userid' => $student->moodleuserid])) {
                    cohort_add_member((int) $cohort->id, (int) $student->moodleuserid);
                    $result->students++;
                }
            }
        }

        $roleshortname = trim((string) get_config('local_siakadbridge', 'lecturerroleshortname'));
        if ($roleshortname === '') {
            $roleshortname = 'editingteacher';
        }
        $role = $DB->get_record('role', ['shortname' => $roleshortname], 'id', IGNORE_MISSING);
        if ($role) {
            role_unassign_all(['component' => 'local_siakadbridge']);
            $lecturers = $DB->get_records_select('local_siakad_dosen',
                'moodleuserid > 0 AND LOWER(status) = :status', ['status' => self::STATUS_AKTIF]);
            foreach ($lecturers as $lecturer) {
                $prodi = $DB->get_record('local_siakad_prodi', ['id' => $lecturer->prodiid, 'aktif' => 1]);
                if (!$prodi || (int) $prodi->categoryid <= 0) {
                    continue;
                }
                $context = \context_coursecat::instance((int) $prodi->categoryid, IGNORE_MISSING);
                if (!$context) {
                    continue;
                }
                if (!$DB->record_exists('role_assignments', [
                    'roleid' => $role->id, 'userid' => $lecturer->moodleuserid, 'contextid' => $context->id,
                    'component' => 'local_siakadbridge', 'itemid' => $prodi->id,
                ])) {
                    role_assign((int) $role->id, (int) $lecturer->moodleuserid, (int) $context->id,
                        'local_siakadbridge', (int) $prodi->id);
                    $result->lecturers++;
                }
            }
        }
        return $result;
    }

    public static function category_name(int $categoryid): string {
        global $DB;
        return $categoryid > 0
            ? (string) $DB->get_field('course_categories', 'name', ['id' => $categoryid], IGNORE_MISSING)
            : '';
    }

    private static function is_user_in_prodi(int $prodiid, string $prodicode): bool {
        global $DB;
        $prodicode = trim($prodicode);
        if ($prodicode === self::PRODI_ANY) {
            return true;
        }
        $prodi = $DB->get_record('local_siakad_prodi', ['id' => $prodiid, 'aktif' => 1], 'id,kode', IGNORE_MISSING);
        return $prodi && self::normalise_code($prodi->kode) === self::normalise_code($prodicode);
    }

    private static function decision(bool $allowed, string $reason, array $billids = []): object {
        return (object) ['allowed' => $allowed, 'reason' => $reason, 'billids' => $billids];
    }

    private static function normalise_code(string $value): string {
        return \core_text::strtolower(trim($value));
    }
}

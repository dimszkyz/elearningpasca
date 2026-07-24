<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakad;

defined('MOODLE_INTERNAL') || die();

/**
 * Resolves a Moodle user's SIAKAD programme and payment eligibility.
 */
final class payment_gate {
    /**
     * Return the active student record linked to a Moodle user.
     */
    public static function get_student(int $moodleuserid): ?\stdClass {
        global $DB;

        $sql = "SELECT m.*, u.moodleuserid, u.active AS useractive
                  FROM {local_siakad_mahasiswa} m
                  JOIN {local_siakad_user} u ON u.id = m.siakaduserid
                 WHERE u.moodleuserid = :userid
                   AND u.usertype = :usertype
                   AND u.active = 1
                   AND m.status = :status";

        return $DB->get_record_sql($sql, [
            'userid' => $moodleuserid,
            'usertype' => 'mahasiswa',
            'status' => 'active',
        ]) ?: null;
    }

    /**
     * A student is paid when no outstanding bill exists for the selected period.
     */
    public static function is_paid(int $mahasiswaid, string $period): bool {
        global $DB;

        $period = trim($period);
        if ($mahasiswaid <= 0 || $period === '') {
            return false;
        }

        $bills = $DB->get_records('local_siakad_tagihan', [
            'mahasiswaid' => $mahasiswaid,
            'period' => $period,
        ]);
        if (!$bills) {
            return false;
        }

        foreach ($bills as $bill) {
            if ($bill->status !== 'paid' || (float) $bill->paidamount < (float) $bill->amount) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate whether a Moodle user may access a configured quiz.
     *
     * @return array{allowed:bool,reason:string}
     */
    public static function can_access_quiz(int $moodleuserid, int $quizid): array {
        global $DB;

        $rules = $DB->get_records('local_siakad_quizprodi', ['quizid' => $quizid]);
        if (!$rules) {
            return ['allowed' => true, 'reason' => ''];
        }

        $student = self::get_student($moodleuserid);
        if (!$student) {
            return ['allowed' => false, 'reason' => get_string('notlinkedstudent', 'local_siakad')];
        }

        $matching = null;
        foreach ($rules as $rule) {
            if ((int) $rule->prodiid === (int) $student->prodiid) {
                $matching = $rule;
                break;
            }
        }
        if (!$matching) {
            return ['allowed' => false, 'reason' => get_string('wrongprogramme', 'local_siakad')];
        }

        if (!empty($matching->requirepaid) && !self::is_paid((int) $student->id, (string) $matching->period)) {
            return ['allowed' => false, 'reason' => get_string('unpaidbill', 'local_siakad', $matching->period)];
        }

        return ['allowed' => true, 'reason' => ''];
    }
}

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
 * Resolves a Moodle user's SIAKAD programme and payment eligibility.
 *
 * @package    local_siakad
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class payment_gate {
    /**
     * Return the active student record linked to a Moodle user.
     */
    public static function get_student(int $moodleuserid): ?\stdClass {
        $DB = external_db::get();

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
     * Return the active lecturer record linked to a Moodle user.
     */
    public static function get_lecturer(int $moodleuserid): ?\stdClass {
        $DB = external_db::get();

        $sql = "SELECT d.*, u.moodleuserid
                  FROM {local_siakad_dosen} d
                  JOIN {local_siakad_user} u ON u.id = d.siakaduserid
                 WHERE u.moodleuserid = :userid
                   AND u.usertype = :usertype
                   AND u.active = 1
                   AND d.status = :status";

        return $DB->get_record_sql($sql, [
            'userid' => $moodleuserid,
            'usertype' => 'dosen',
            'status' => 'active',
        ]) ?: null;
    }

    /**
     * A student is paid when no outstanding bill exists for the selected period.
     */
    public static function is_paid(int $mahasiswaid, string $period): bool {
        return billing::is_settled($mahasiswaid, $period);
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

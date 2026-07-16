<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascaprodi;

use stdClass;

/**
 * Helper for applying Prodi role and cohort setup when a user is created.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_setup {
    /**
     * Apply role/category setup from the Add new user form.
     *
     * Student-like users are added to the generated student cohort for the
     * selected Prodi. Teacher/globalteacher/course creator/manager roles are
     * assigned at the selected category context instead, so they do not become
     * students through the student cohort sync.
     *
     * @param int $userid Moodle user ID.
     * @param int $roleid Selected Moodle role ID, or 0 for no role change.
     * @param int[] $categoryids Selected Prodi/category IDs.
     */
    public static function apply(int $userid, int $roleid = 0, array $categoryids = []): void {
        global $CFG, $DB;

        if ($userid <= 0) {
            return;
        }

        $categoryids = self::normalise_category_ids($categoryids);
        $systemcontext = \context_system::instance();
        $role = null;
        $iscategoryrole = false;

        if ($roleid > 0) {
            $role = $DB->get_record('role', ['id' => $roleid], '*', MUST_EXIST);
            $iscategoryrole = manager::is_category_role($role);
        }

        if ($role && $iscategoryrole && $categoryids) {
            foreach ($categoryids as $categoryid) {
                \core_course_category::get($categoryid, MUST_EXIST, true);
                $categorycontext = \context_coursecat::instance($categoryid);
                require_capability('moodle/role:assign', $categorycontext);

                if (!$DB->record_exists('role_assignments', [
                    'roleid' => $roleid,
                    'contextid' => $categorycontext->id,
                    'userid' => $userid,
                ])) {
                    role_assign($roleid, $userid, $categorycontext->id);
                }
            }

            return;
        }

        if ($role && !$categoryids && strtolower((string) $role->shortname) !== 'student') {
            require_capability('moodle/role:assign', $systemcontext);

            if (!$DB->record_exists('role_assignments', [
                'roleid' => $roleid,
                'contextid' => $systemcontext->id,
                'userid' => $userid,
            ])) {
                role_assign($roleid, $userid, $systemcontext->id);
            }
        }

        if (!$categoryids) {
            return;
        }

        require_once($CFG->dirroot . '/cohort/lib.php');
        require_capability('moodle/cohort:assign', $systemcontext);

        foreach ($categoryids as $categoryid) {
            \core_course_category::get($categoryid, MUST_EXIST, true);
            $cohortid = manager::ensure_category_cohort($categoryid);

            if (!$cohortid) {
                continue;
            }

            if (!$DB->record_exists('cohort_members', [
                'cohortid' => $cohortid,
                'userid' => $userid,
            ])) {
                cohort_add_member($cohortid, $userid);
            }
        }
    }

    /**
     * Normalise category IDs.
     *
     * @param mixed $categoryids Raw category IDs from a Moodle form.
     * @return int[]
     */
    public static function normalise_category_ids($categoryids): array {
        $ids = [];
        foreach ((array) $categoryids as $categoryid) {
            $categoryid = (int) $categoryid;
            if ($categoryid > 0) {
                $ids[$categoryid] = $categoryid;
            }
        }

        return array_values($ids);
    }

    /**
     * Check whether the selected role may be assigned to multiple Prodi categories.
     */
    public static function allows_multiple_categories(int $roleid): bool {
        global $DB;

        if ($roleid <= 0) {
            return false;
        }

        $role = $DB->get_record('role', ['id' => $roleid]);
        return $role ? manager::is_category_role($role) : false;
    }
}

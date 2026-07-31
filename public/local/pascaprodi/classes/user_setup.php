<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascaprodi;

/**
 * Helper for applying role and study programme setup when a user is created.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class user_setup {
    /**
     * Apply role and programme setup from the Add new user form.
     *
     * The role is assigned at system context. Programmes are not Moodle contexts,
     * so they only drive membership of the generated student cohort, which in turn
     * enrols the user into every course linked to that programme.
     *
     * @param int $userid Moodle user ID.
     * @param int $roleid Selected Moodle role ID, or 0 for no role change.
     * @param int[] $prodiids Selected programme IDs.
     */
    public static function apply(int $userid, int $roleid = 0, array $prodiids = []): void {
        global $CFG, $DB;

        if ($userid <= 0) {
            return;
        }

        $prodiids = self::normalise_prodi_ids($prodiids);
        $systemcontext = \context_system::instance();

        if ($roleid > 0) {
            $role = $DB->get_record('role', ['id' => $roleid], '*', MUST_EXIST);

            // Students get their access through the programme cohort, so a system
            // level student assignment would only widen it unnecessarily.
            if (strtolower((string) $role->shortname) !== 'student') {
                require_capability('moodle/role:assign', $systemcontext);

                if (!$DB->record_exists('role_assignments', [
                    'roleid' => $roleid,
                    'contextid' => $systemcontext->id,
                    'userid' => $userid,
                ])) {
                    role_assign($roleid, $userid, $systemcontext->id);
                }
            }
        }

        if (!$prodiids) {
            return;
        }

        require_once($CFG->dirroot . '/cohort/lib.php');
        require_capability('moodle/cohort:assign', $systemcontext);

        foreach ($prodiids as $prodiid) {
            $cohortid = manager::ensure_prodi_cohort($prodiid);

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
     * Normalise programme IDs coming from a Moodle form.
     *
     * @param mixed $prodiids Raw programme IDs.
     * @return int[]
     */
    public static function normalise_prodi_ids($prodiids): array {
        $ids = [];
        foreach ((array) $prodiids as $prodiid) {
            $prodiid = (int) $prodiid;
            if ($prodiid > 0) {
                $ids[$prodiid] = $prodiid;
            }
        }

        return array_values($ids);
    }
}

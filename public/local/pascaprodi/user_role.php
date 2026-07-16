<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Combined helper for assigning a system role and an optional cohort to a user.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

admin_externalpage_setup('local_pascaprodi_userrole');

$systemcontext = context_system::instance();
require_capability('moodle/role:assign', $systemcontext);

$url = new moodle_url('/local/pascaprodi/user_role.php');
$PAGE->set_url($url);
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('userrolepage', 'local_pascaprodi'));
$PAGE->set_heading(get_string('userrolepage', 'local_pascaprodi'));

$mform = new \local_pascaprodi\form\user_role_form($url);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php', ['section' => 'local_pascaprodi']));
}

if ($data = $mform->get_data()) {
    $userid = (int) $data->userid;
    $roleid = (int) $data->roleid;
    $cohortid = (int) $data->cohortid;
    $messages = [];

    $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

    if ($roleid > 0) {
        $role = $DB->get_record('role', ['id' => $roleid], '*', MUST_EXIST);
        if (!user_can_assign($systemcontext, $roleid)) {
            throw new required_capability_exception($systemcontext, 'moodle/role:assign', 'nopermissions', 'error');
        }

        if (!$DB->record_exists('role_assignments', [
            'roleid' => $roleid,
            'contextid' => $systemcontext->id,
            'userid' => $userid,
        ])) {
            role_assign($roleid, $userid, $systemcontext->id);
            $messages[] = get_string('roleassigned', 'local_pascaprodi', role_get_name($role, $systemcontext));
        } else {
            $messages[] = get_string('rolealreadyassigned', 'local_pascaprodi', role_get_name($role, $systemcontext));
        }
    }

    if ($cohortid > 0) {
        require_capability('moodle/cohort:assign', $systemcontext);
        $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);

        if (!$DB->record_exists('cohort_members', [
            'cohortid' => $cohortid,
            'userid' => $userid,
        ])) {
            cohort_add_member($cohortid, $userid);
            $messages[] = get_string('cohortassigned', 'local_pascaprodi', format_string($cohort->name));
        } else {
            $messages[] = get_string('cohortalreadyassigned', 'local_pascaprodi', format_string($cohort->name));
        }
    }

    if (!$messages) {
        $messages[] = get_string('nochangesmade', 'local_pascaprodi');
    }

    $message = get_string('userroleassigned', 'local_pascaprodi', fullname($user)) . '<br>' . implode('<br>', $messages);
    redirect($url, $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('userrolepage', 'local_pascaprodi'));
echo $OUTPUT->notification(get_string('userrolepage_desc', 'local_pascaprodi'), \core\output\notification::NOTIFY_INFO);
$mform->display();
echo $OUTPUT->footer();

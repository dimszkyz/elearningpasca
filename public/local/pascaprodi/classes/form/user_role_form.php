<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascaprodi\form;

/**
 * Form for assigning a user to a system role and an optional Prodi cohort.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class user_role_form extends \moodleform {
    /**
     * Define form fields.
     */
    protected function definition(): void {
        global $DB;

        $mform = $this->_form;
        $systemcontext = \context_system::instance();

        $users = $DB->get_records_select(
            'user',
            'deleted = 0 AND id <> :guestid',
            ['guestid' => guest_user()->id],
            'firstname ASC, lastname ASC, username ASC',
            'id,firstname,lastname,email,username'
        );

        $useroptions = [];
        foreach ($users as $user) {
            $label = fullname($user) . ' (' . $user->username;
            if (!empty($user->email)) {
                $label .= ' - ' . $user->email;
            }
            $label .= ')';
            $useroptions[(int) $user->id] = $label;
        }

        $mform->addElement('autocomplete', 'userid', get_string('field_user', 'local_pascaprodi'), $useroptions);
        $mform->addRule('userid', get_string('required'), 'required', null, 'client');

        $roles = get_assignable_roles($systemcontext, ROLENAME_ORIGINAL, false);
        $roleoptions = [0 => get_string('norolechange', 'local_pascaprodi')] + $roles;
        $mform->addElement('select', 'roleid', get_string('field_systemrole', 'local_pascaprodi'), $roleoptions);
        $mform->setDefault('roleid', 0);
        $mform->addHelpButton('roleid', 'field_systemrole', 'local_pascaprodi');

        $cohortoptions = [0 => get_string('nocohortchange', 'local_pascaprodi')];
        $cohorts = $DB->get_records('cohort', ['visible' => 1], 'name ASC', 'id,name,idnumber');
        foreach ($cohorts as $cohort) {
            $label = format_string($cohort->name);
            if (!empty($cohort->idnumber)) {
                $label .= ' (' . $cohort->idnumber . ')';
            }
            $cohortoptions[(int) $cohort->id] = $label;
        }

        $mform->addElement('autocomplete', 'cohortid', get_string('field_cohort', 'local_pascaprodi'), $cohortoptions);
        $mform->setDefault('cohortid', 0);
        $mform->addHelpButton('cohortid', 'field_cohort', 'local_pascaprodi');

        $this->add_action_buttons(false, get_string('savechanges'));
    }
}

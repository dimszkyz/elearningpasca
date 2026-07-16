<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascaprodi\form;

use local_pascaprodi\manager;

/**
 * Form for assigning a user to a role and one or more Prodi categories.
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

        $roleoptions = [0 => get_string('norolechange', 'local_pascaprodi')];
        $roles = $DB->get_records('role', null, 'sortorder ASC', 'id,name,shortname,archetype');
        foreach ($roles as $role) {
            $label = role_get_name($role, $systemcontext, ROLENAME_ORIGINAL) . ' (' . $role->shortname . ')';
            $roleoptions[(int) $role->id] = $label;
        }

        $mform->addElement('select', 'roleid', get_string('field_accessrole', 'local_pascaprodi'), $roleoptions);
        $mform->setDefault('roleid', 0);
        $mform->addHelpButton('roleid', 'field_accessrole', 'local_pascaprodi');

        $categoryoptions = [];
        $categories = $DB->get_records('course_categories', null, 'sortorder ASC', 'id,name,path');
        foreach ($categories as $category) {
            $path = trim((string) $category->path, '/');
            $depth = $path === '' ? 0 : max(0, substr_count($path, '/') - 0);
            $categoryoptions[(int) $category->id] = str_repeat('— ', $depth) . format_string($category->name);
        }

        $mform->addElement('autocomplete', 'categoryids', get_string('field_categories', 'local_pascaprodi'), $categoryoptions, [
            'multiple' => true,
        ]);
        $mform->addHelpButton('categoryids', 'field_categories', 'local_pascaprodi');

        $this->add_action_buttons(false, get_string('savechanges'));
    }

    /**
     * Validate role and category selection.
     */
    public function validation($data, $files): array {
        global $DB;

        $errors = parent::validation($data, $files);
        $roleid = (int) ($data['roleid'] ?? 0);
        $categoryids = array_values(array_filter(array_map('intval', (array) ($data['categoryids'] ?? []))));

        if ($roleid <= 0 && !$categoryids) {
            $errors['categoryids'] = get_string('error_select_role_or_category', 'local_pascaprodi');
            return $errors;
        }

        if (count($categoryids) > 1) {
            $allowmultiple = false;
            if ($roleid > 0) {
                $role = $DB->get_record('role', ['id' => $roleid]);
                $allowmultiple = $role ? manager::is_category_role($role) : false;
            }

            if (!$allowmultiple) {
                $errors['categoryids'] = get_string('error_multiple_categories_teacher_role', 'local_pascaprodi');
            }
        }

        return $errors;
    }
}

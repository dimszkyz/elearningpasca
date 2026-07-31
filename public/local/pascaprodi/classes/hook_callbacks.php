<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascaprodi;

/**
 * Course form hook callbacks for study programme selection.
 *
 * Course categories model content structure only. A course is attached to one or
 * more study programmes through this field, which adds the Cohort sync enrolment
 * method of each selected programme.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {
    /** Form field holding the selected programme IDs. */
    public const FIELD = 'pascaprodiids';

    /**
     * Add the programme selector to the course settings form.
     */
    public static function after_form_definition(\core_course\hook\after_form_definition $hook): void {
        if (!manager::is_enabled()) {
            return;
        }

        $mform = $hook->mform;
        $options = manager::get_prodi_options();
        if (!$options) {
            return;
        }

        $mform->addElement(
            'autocomplete',
            self::FIELD,
            get_string('prodi', manager::COMPONENT),
            $options,
            ['multiple' => true]
        );
        $mform->setType(self::FIELD, PARAM_INT);
        $mform->addHelpButton(self::FIELD, 'prodi', manager::COMPONENT);

        // Sit directly under the core course category selector rather than at the
        // very end of the form, where the relationship would not be obvious.
        if ($mform->elementExists('visible')) {
            $mform->insertElementBefore($mform->removeElement(self::FIELD, false), 'visible');
        }
    }

    /**
     * Preselect the programmes already linked to the course being edited.
     */
    public static function after_form_definition_after_data(
        \core_course\hook\after_form_definition_after_data $hook
    ): void {
        if (!manager::is_enabled()) {
            return;
        }

        $mform = $hook->mform;
        if (!$mform->elementExists(self::FIELD)) {
            return;
        }

        $courseid = (int) ($hook->formwrapper->get_course()->id ?? 0);
        if ($courseid <= 0) {
            return;
        }

        $mform->setDefault(self::FIELD, manager::get_course_prodi($courseid));
    }

    /**
     * Apply the selected programmes after the course has been saved.
     */
    public static function after_form_submission(\core_course\hook\after_form_submission $hook): void {
        if (!manager::is_enabled()) {
            return;
        }

        $data = $hook->get_data();
        $courseid = (int) ($data->id ?? 0);
        if ($courseid <= 0 || !property_exists($data, self::FIELD)) {
            return;
        }

        manager::set_course_prodi($courseid, (array) $data->{self::FIELD});
    }
}

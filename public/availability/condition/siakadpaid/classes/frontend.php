<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace availability_siakadpaid;

/**
 * Front-end class for the SIAKAD paid availability condition.
 *
 * @package    availability_siakadpaid
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    protected function get_javascript_strings() {
        return [
            'title',
            'label_prodi',
            'anyprodi',
            'error_selectprodi',
        ];
    }

    protected function get_javascript_init_params($course, ?\cm_info $cm = null,
            ?\section_info $section = null) {
        if (!class_exists('\local_siakadbridge\manager')) {
            return [[]];
        }

        return [\local_siakadbridge\manager::get_active_prodi_options()];
    }

    protected function allow_add($course, ?\cm_info $cm = null,
            ?\section_info $section = null) {
        return class_exists('\local_siakadbridge\manager');
    }
}

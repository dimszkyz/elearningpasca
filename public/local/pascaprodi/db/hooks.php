<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Hook callbacks for local_pascaprodi.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core_course\hook\after_form_definition::class,
        'callback' => '\\local_pascaprodi\\hook_callbacks::after_form_definition',
    ],
    [
        'hook' => \core_course\hook\after_form_definition_after_data::class,
        'callback' => '\\local_pascaprodi\\hook_callbacks::after_form_definition_after_data',
    ],
    [
        'hook' => \core_course\hook\after_form_submission::class,
        'callback' => '\\local_pascaprodi\\hook_callbacks::after_form_submission',
    ],
];

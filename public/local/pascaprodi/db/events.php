<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Event observers for local_pascaprodi.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\\core\\event\\course_category_created',
        'callback' => '\\local_pascaprodi\\observer::course_category_created',
    ],
    [
        'eventname' => '\\core\\event\\course_category_updated',
        'callback' => '\\local_pascaprodi\\observer::course_category_updated',
    ],
    [
        'eventname' => '\\core\\event\\course_category_deleted',
        'callback' => '\\local_pascaprodi\\observer::course_category_deleted',
    ],
];

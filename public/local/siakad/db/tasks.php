<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Scheduled tasks for local_siakad.
 *
 * @package    local_siakad
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'local_siakad\\task\\sync_prodi_task',
        'blocking' => 0,
        'minute' => '15',
        'hour' => '*/6',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ],
];

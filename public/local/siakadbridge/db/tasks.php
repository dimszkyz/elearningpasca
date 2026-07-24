<?php
// This file is part of Moodle - http://moodle.org/
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\\local_siakadbridge\\task\\sync_program_studies',
        'blocking' => 0,
        'minute' => '*/15',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => '\\local_siakadbridge\\task\\sync_siakad',
        'blocking' => 0,
        'minute' => '7-59/15',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];

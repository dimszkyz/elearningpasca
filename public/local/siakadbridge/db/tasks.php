<?php
// This file is part of Moodle - http://moodle.org/
defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\\local_siakadbridge\\task\\sync_program_studies',
        'blocking' => 0,
        'minute' => '0,15,30,45',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => '\\local_siakadbridge\\task\\sync_siakad',
        'blocking' => 0,
        'minute' => '7,22,37,52',
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];

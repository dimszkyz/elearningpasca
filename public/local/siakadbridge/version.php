<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_siakadbridge';
$plugin->version = 2026072400;
$plugin->requires = 2026042000;
$plugin->dependencies = [
    'local_pascaprodi' => 2026071608,
];
$plugin->maturity = MATURITY_BETA;
$plugin->release = '1.1.0';

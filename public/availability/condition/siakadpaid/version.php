<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'availability_siakadpaid';
$plugin->version = 2026071700;
$plugin->requires = 2026042000;
$plugin->dependencies = [
    'local_siakadbridge' => 2026071700,
];
$plugin->maturity = MATURITY_BETA;
$plugin->release = '1.0.0';

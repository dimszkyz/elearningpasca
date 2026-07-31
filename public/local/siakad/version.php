<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_siakad';
$plugin->version = 2026072901;
$plugin->requires = 2026042000;
$plugin->dependencies = [
    'local_pascaprodi' => 2026072900,
];
$plugin->maturity = MATURITY_ALPHA;
$plugin->release = '0.3.0';

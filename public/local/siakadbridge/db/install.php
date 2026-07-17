<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/** Set safe initial configuration for a fresh installation. */
function xmldb_local_siakadbridge_install(): void {
    set_config('sourcemode', 'manual', 'local_siakadbridge');
    set_config('apitimeout', 30, 'local_siakadbridge');
    set_config('currentyear', '2026/2027', 'local_siakadbridge');
    set_config('currentsemester', 'genap', 'local_siakadbridge');
    set_config('gatemode', 'all_required_lunas', 'local_siakadbridge');
    set_config('lecturerroleshortname', 'editingteacher', 'local_siakadbridge');
}

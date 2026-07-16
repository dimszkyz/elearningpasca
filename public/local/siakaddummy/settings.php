<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Administration navigation for the dummy SIAKAD plugin.
 *
 * @package    local_siakaddummy
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_siakaddummy',
        get_string('pluginname', 'local_siakaddummy'),
        new moodle_url('/local/siakaddummy/index.php'),
        'moodle/site:config',
    ));
}

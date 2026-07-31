<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Connection settings for the standalone SIAKAD database.
 *
 * @package    local_siakad
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_siakad', get_string('pluginname', 'local_siakad'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading(
        'local_siakad/dbheading',
        get_string('dbheading', 'local_siakad'),
        get_string('dbheading_desc', 'local_siakad')
    ));

    $drivers = ['' => get_string('dbtype_none', 'local_siakad')];
    foreach (['mysqli', 'mariadb', 'pgsql', 'sqlsrv', 'oci'] as $driver) {
        $drivers[$driver] = $driver;
    }
    $settings->add(new admin_setting_configselect(
        'local_siakad/dbtype',
        get_string('dbtype', 'local_siakad'),
        get_string('dbtype_desc', 'local_siakad'),
        '',
        $drivers
    ));

    $settings->add(new admin_setting_configtext(
        'local_siakad/dbhost',
        get_string('dbhost', 'local_siakad'),
        get_string('dbhost_desc', 'local_siakad'),
        'localhost'
    ));

    $settings->add(new admin_setting_configtext(
        'local_siakad/dbname',
        get_string('dbname', 'local_siakad'),
        get_string('dbname_desc', 'local_siakad'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_siakad/dbuser',
        get_string('dbuser', 'local_siakad'),
        get_string('dbuser_desc', 'local_siakad'),
        ''
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_siakad/dbpass',
        get_string('dbpass', 'local_siakad'),
        get_string('dbpass_desc', 'local_siakad'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_siakad/dbprefix',
        get_string('dbprefix', 'local_siakad'),
        get_string('dbprefix_desc', 'local_siakad'),
        ''
    ));

    $settings->add(new admin_setting_configtext(
        'local_siakad/dbport',
        get_string('dbport', 'local_siakad'),
        get_string('dbport_desc', 'local_siakad'),
        '',
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_siakad/dbsocket',
        get_string('dbsocket', 'local_siakad'),
        get_string('dbsocket_desc', 'local_siakad'),
        ''
    ));
}

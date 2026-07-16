<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Settings for local_pascaprodi.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_pascaprodi', get_string('pluginname', 'local_pascaprodi'));

    $settings->add(new admin_setting_configcheckbox(
        'local_pascaprodi/enabled',
        get_string('setting_enabled', 'local_pascaprodi'),
        get_string('setting_enabled_desc', 'local_pascaprodi'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_pascaprodi/nameprefix',
        get_string('setting_nameprefix', 'local_pascaprodi'),
        get_string('setting_nameprefix_desc', 'local_pascaprodi'),
        get_string('defaultnameprefix', 'local_pascaprodi'),
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_pascaprodi/updatenames',
        get_string('setting_updatenames', 'local_pascaprodi'),
        get_string('setting_updatenames_desc', 'local_pascaprodi'),
        1
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_pascaprodi/archiveondeleted',
        get_string('setting_archiveondeleted', 'local_pascaprodi'),
        get_string('setting_archiveondeleted_desc', 'local_pascaprodi'),
        1
    ));

    $settings->add(new admin_setting_configtext(
        'local_pascaprodi/archiveprefix',
        get_string('setting_archiveprefix', 'local_pascaprodi'),
        get_string('setting_archiveprefix_desc', 'local_pascaprodi'),
        get_string('defaultarchiveprefix', 'local_pascaprodi'),
        PARAM_TEXT
    ));

    $ADMIN->add('localplugins', $settings);
}

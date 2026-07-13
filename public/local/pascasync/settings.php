<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Admin settings for the Pasca user synchronisation plugin.
 *
 * @package    local_pascasync
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_pascasync', get_string('pluginname', 'local_pascasync'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_heading(
        'local_pascasync/connectionheading',
        get_string('connectionsettings', 'local_pascasync'),
        get_string('connectionsettings_desc', 'local_pascasync'),
    ));

    $settings->add(new admin_setting_configtext(
        'local_pascasync/apiurl',
        get_string('apiurl', 'local_pascasync'),
        get_string('apiurl_desc', 'local_pascasync'),
        'http://host.docker.internal:8001/api/integrations/moodle/users',
        PARAM_URL,
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_pascasync/apitoken',
        get_string('apitoken', 'local_pascasync'),
        get_string('apitoken_desc', 'local_pascasync'),
        '',
    ));

    $settings->add(new admin_setting_configtext(
        'local_pascasync/timeout',
        get_string('timeout', 'local_pascasync'),
        get_string('timeout_desc', 'local_pascasync'),
        30,
        PARAM_INT,
    ));

    $settings->add(new admin_setting_configtext(
        'local_pascasync/perpage',
        get_string('perpage', 'local_pascasync'),
        get_string('perpage_desc', 'local_pascasync'),
        100,
        PARAM_INT,
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_pascasync/allowprivatehost',
        get_string('allowprivatehost', 'local_pascasync'),
        get_string('allowprivatehost_desc', 'local_pascasync'),
        0,
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_pascasync/enableemaillogin',
        get_string('enableemaillogin', 'local_pascasync'),
        get_string('enableemaillogin_desc', 'local_pascasync'),
        1,
    ));

}

$ADMIN->add('accounts', new admin_externalpage(
    'local_pascasync_sync',
    get_string('syncusers', 'local_pascasync'),
    new moodle_url('/local/pascasync/index.php'),
    'local/pascasync:sync',
));

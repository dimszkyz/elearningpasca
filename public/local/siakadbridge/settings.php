<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_siakadbridge_settings', get_string('settings', 'local_siakadbridge'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configselect(
        'local_siakadbridge/sourcemode',
        get_string('sourcemode', 'local_siakadbridge'),
        get_string('sourcemode_desc', 'local_siakadbridge'),
        'manual',
        [
            'manual' => get_string('source_manual', 'local_siakadbridge'),
            'rest' => get_string('source_rest', 'local_siakadbridge'),
        ]
    ));
    $settings->add(new admin_setting_configtext(
        'local_siakadbridge/apiurl',
        get_string('apiurl', 'local_siakadbridge'),
        get_string('apiurl_desc', 'local_siakadbridge'),
        '',
        PARAM_URL
    ));
    $settings->add(new admin_setting_configpasswordunmask(
        'local_siakadbridge/apitoken',
        get_string('apitoken', 'local_siakadbridge'),
        get_string('apitoken_desc', 'local_siakadbridge'),
        ''
    ));
    $settings->add(new admin_setting_configtext(
        'local_siakadbridge/apitimeout',
        get_string('apitimeout', 'local_siakadbridge'),
        get_string('apitimeout_desc', 'local_siakadbridge'),
        30,
        PARAM_INT
    ));
    $settings->add(new admin_setting_configtext(
        'local_siakadbridge/currentyear',
        get_string('currentyear', 'local_siakadbridge'),
        get_string('currentyear_desc', 'local_siakadbridge'),
        '2026/2027',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configselect(
        'local_siakadbridge/currentsemester',
        get_string('currentsemester', 'local_siakadbridge'),
        get_string('currentsemester_desc', 'local_siakadbridge'),
        'genap',
        [
            'ganjil' => get_string('semester_ganjil', 'local_siakadbridge'),
            'genap' => get_string('semester_genap', 'local_siakadbridge'),
        ]
    ));
    $settings->add(new admin_setting_configselect(
        'local_siakadbridge/gatemode',
        get_string('gatemode', 'local_siakadbridge'),
        get_string('gatemode_desc', 'local_siakadbridge'),
        'all_required_lunas',
        [
            'all_required_lunas' => get_string('gatemode_all', 'local_siakadbridge'),
            'any_lunas' => get_string('gatemode_any', 'local_siakadbridge'),
        ]
    ));
    $settings->add(new admin_setting_configtext(
        'local_siakadbridge/lecturerroleshortname',
        get_string('lecturerroleshortname', 'local_siakadbridge'),
        get_string('lecturerroleshortname_desc', 'local_siakadbridge'),
        'editingteacher',
        PARAM_ALPHANUMEXT
    ));

    $ADMIN->add('localplugins', new admin_externalpage(
        'local_siakadbridge_manage',
        get_string('managetagihan', 'local_siakadbridge'),
        new moodle_url('/local/siakadbridge/index.php'),
        'local/siakadbridge:manage'
    ));
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_siakadbridge_prodi',
        get_string('manageprodi', 'local_siakadbridge'),
        new moodle_url('/local/siakadbridge/prodi.php'),
        'local/siakadbridge:manage'
    ));
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_siakadbridge_sync',
        get_string('manualsync', 'local_siakadbridge'),
        new moodle_url('/local/siakadbridge/sync.php'),
        'local/siakadbridge:sync'
    ));
}

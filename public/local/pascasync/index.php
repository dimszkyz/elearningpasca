<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Pasca user synchronisation administration page.
 *
 * @package    local_pascasync
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_pascasync\api_client;
use local_pascasync\sync_result;
use local_pascasync\sync_service;

admin_externalpage_setup('local_pascasync_sync');
require_capability('local/pascasync:sync', context_system::instance());

$action = optional_param('action', '', PARAM_ALPHA);
$mode = optional_param('mode', 'full', PARAM_ALPHA);
$returnurl = new moodle_url('/local/pascasync/index.php');

$PAGE->set_title(get_string('syncusers', 'local_pascasync'));
$PAGE->set_heading(get_string('syncusers', 'local_pascasync'));

if ($action === 'confirm') {
    $mode = in_array($mode, ['full', 'incremental'], true) ? $mode : 'full';
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('syncusers', 'local_pascasync'));

    $yesurl = new moodle_url('/local/pascasync/index.php', [
        'action' => 'sync',
        'mode' => $mode,
        'sesskey' => sesskey(),
    ]);
    $yesbutton = new single_button($yesurl, get_string('confirmsyncyes', 'local_pascasync'), 'post',
        single_button::BUTTON_PRIMARY);
    echo $OUTPUT->confirm(get_string('confirmsync', 'local_pascasync'), $yesbutton, $returnurl);
    echo $OUTPUT->footer();
    exit;
}

$result = null;
$connectionmeta = null;

if ($action === 'test' && confirm_sesskey()) {
    try {
        $payload = (new api_client())->fetch_page(1);
        $connectionmeta = $payload['meta'];
        \core\notification::success(get_string('connectionsuccess', 'local_pascasync', (object) [
            'total' => (int) ($connectionmeta['total'] ?? 0),
        ]));
    } catch (Throwable $exception) {
        \core\notification::error(get_string('connectionerror', 'local_pascasync', $exception->getMessage()));
    }
}

if ($action === 'sync' && confirm_sesskey()) {
    $mode = in_array($mode, ['full', 'incremental'], true) ? $mode : 'full';
    $startedat = time();
    $updatedafter = null;

    if ($mode === 'incremental') {
        $lastsyncat = (int) get_config('local_pascasync', 'lastsyncat');
        if ($lastsyncat > 0) {
            $updatedafter = gmdate('c', $lastsyncat);
        }
    }

    try {
        \core_php_time_limit::raise(0);
        raise_memory_limit(MEMORY_EXTRA);
        $result = (new sync_service())->sync($updatedafter);

        if ($result->failed === 0) {
            set_config('lastsyncat', $startedat, 'local_pascasync');
        }

        if ($result->failed > 0 || $result->conflicts > 0) {
            \core\notification::warning(get_string('synccompletedwithissues', 'local_pascasync'));
        } else {
            \core\notification::success(get_string('synccompleted', 'local_pascasync'));
        }
    } catch (Throwable $exception) {
        \core\notification::error(get_string('syncfatalerror', 'local_pascasync', $exception->getMessage()));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('syncusers', 'local_pascasync'));

echo html_writer::tag('p', get_string('syncdescription', 'local_pascasync'));

$apiurl = trim((string) get_config('local_pascasync', 'apiurl'));
$apitoken = trim((string) get_config('local_pascasync', 'apitoken'));
if ($apiurl === '' || $apitoken === '') {
    $settingsurl = new moodle_url('/admin/settings.php', ['section' => 'local_pascasync']);
    echo $OUTPUT->notification(
        get_string('configurefirst', 'local_pascasync', html_writer::link($settingsurl,
            get_string('pluginsettings', 'local_pascasync'))),
        \core\output\notification::NOTIFY_WARNING,
    );
}

$statusitems = [];
$statusitems[] = get_string('statusapiurl', 'local_pascasync', $apiurl !== '' ? s($apiurl) : '-');
$statusitems[] = get_string('statustoken', 'local_pascasync', $apitoken !== '' ? get_string('configured', 'local_pascasync') :
    get_string('notconfigured', 'local_pascasync'));
$statusitems[] = get_string('statusemaillogin', 'local_pascasync', !empty($CFG->authloginviaemail) ?
    get_string('enabled', 'local_pascasync') : get_string('disabled', 'local_pascasync'));
$lastsyncat = (int) get_config('local_pascasync', 'lastsyncat');
$statusitems[] = get_string('statuslastsync', 'local_pascasync', $lastsyncat > 0 ? userdate($lastsyncat) :
    get_string('never', 'local_pascasync'));

echo html_writer::alist($statusitems);

$buttoncontainer = html_writer::start_div('d-flex flex-wrap gap-2 mb-4');
$testbutton = new single_button(new moodle_url('/local/pascasync/index.php', [
    'action' => 'test',
    'sesskey' => sesskey(),
]), get_string('testconnection', 'local_pascasync'), 'post');
$buttoncontainer .= $OUTPUT->render($testbutton);

$fullbutton = new single_button(new moodle_url('/local/pascasync/index.php', [
    'action' => 'confirm',
    'mode' => 'full',
]), get_string('startfullsync', 'local_pascasync'), 'get', single_button::BUTTON_PRIMARY);
$buttoncontainer .= $OUTPUT->render($fullbutton);

if ($lastsyncat > 0) {
    $incrementalbutton = new single_button(new moodle_url('/local/pascasync/index.php', [
        'action' => 'confirm',
        'mode' => 'incremental',
    ]), get_string('startincrementalsync', 'local_pascasync'), 'get');
    $buttoncontainer .= $OUTPUT->render($incrementalbutton);
}
$buttoncontainer .= html_writer::end_div();
echo $buttoncontainer;

if ($result instanceof sync_result) {
    echo $OUTPUT->heading(get_string('syncresult', 'local_pascasync'), 3);

    $summarytable = new html_table();
    $summarytable->head = [get_string('resultitem', 'local_pascasync'), get_string('resultcount', 'local_pascasync')];
    $summarytable->data = [
        [get_string('resulttotal', 'local_pascasync'), $result->total],
        [get_string('resultcreated', 'local_pascasync'), $result->created],
        [get_string('resultupdated', 'local_pascasync'), $result->updated],
        [get_string('resultunchanged', 'local_pascasync'), $result->unchanged],
        [get_string('resultconflicts', 'local_pascasync'), $result->conflicts],
        [get_string('resultfailed', 'local_pascasync'), $result->failed],
    ];
    echo html_writer::table($summarytable);

    $issues = $result->get_issues();
    if ($issues) {
        echo $OUTPUT->heading(get_string('syncissues', 'local_pascasync'), 3);
        $issuetable = new html_table();
        $issuetable->head = [
            get_string('issuetype', 'local_pascasync'),
            get_string('sourceid', 'local_pascasync'),
            get_string('fullname'),
            get_string('email'),
            get_string('issue', 'local_pascasync'),
        ];
        foreach ($issues as $issue) {
            $issuetable->data[] = [
                s((string) $issue['type']),
                (int) $issue['sourceid'],
                s((string) $issue['name']),
                s((string) $issue['email']),
                s((string) $issue['message']),
            ];
        }
        echo html_writer::table($issuetable);
    }
}

echo $OUTPUT->footer();

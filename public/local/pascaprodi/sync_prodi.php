<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Sync all Program Studi from the UNW API into the local programme table.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

admin_externalpage_setup('local_pascaprodi_syncprodi');

$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

$url = new moodle_url('/local/pascaprodi/sync_prodi.php');
$PAGE->set_url($url);
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('syncprodipage', 'local_pascaprodi'));
$PAGE->set_heading(get_string('syncprodipage', 'local_pascaprodi'));

$dosync = optional_param('sync', 0, PARAM_BOOL);
$result = null;
$error = null;

if ($dosync) {
    require_sesskey();
    try {
        $result = \local_pascaprodi\manager::sync_remote_prodi();
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('syncprodipage', 'local_pascaprodi'));

echo $OUTPUT->notification(get_string('syncprodipage_desc', 'local_pascaprodi', (object) [
    'url' => s(\local_pascaprodi\manager::get_sync_api_url()),
]), 'info');

$buttonurl = new moodle_url($url, ['sync' => 1, 'sesskey' => sesskey()]);
echo html_writer::div(
    $OUTPUT->single_button($buttonurl, get_string('syncprodibutton', 'local_pascaprodi'), 'post'),
    'mb-3'
);

if ($error !== null) {
    echo $OUTPUT->notification(get_string('syncprodifailed', 'local_pascaprodi', $error), 'error');
}

if ($result !== null) {
    $summary = get_string('syncprodisuccess', 'local_pascaprodi', (object) [
        'created' => $result['created'],
        'updated' => $result['updated'],
        'unchanged' => $result['unchanged'],
        'skipped' => $result['skipped'],
        'failed' => $result['failed'],
        'deactivated' => $result['deactivated'],
        'cohortcreated' => $result['cohortcreated'],
        'cohortupdated' => $result['cohortupdated'],
    ]);
    echo $OUTPUT->notification($summary, 'success');

    if (!empty($result['items'])) {
        $table = new html_table();
        $table->head = [
            get_string('status'),
            get_string('prodi', 'local_pascaprodi'),
            get_string('prodicode', 'local_pascaprodi'),
            get_string('prodiid', 'local_pascaprodi'),
            get_string('cohortid', 'local_pascaprodi'),
            get_string('syncmessage', 'local_pascaprodi'),
        ];
        $table->attributes['class'] = 'generaltable table table-striped';

        foreach ($result['items'] as $item) {
            $table->data[] = [
                s((string) ($item['status'] ?? '')),
                s((string) ($item['name'] ?? '')),
                s((string) ($item['code'] ?? '')),
                s((string) ($item['prodiid'] ?? '')),
                s((string) ($item['cohortid'] ?? '')),
                s((string) ($item['message'] ?? '')),
            ];
        }

        echo html_writer::table($table);
    }
}

echo $OUTPUT->continue_button(new moodle_url('/admin/category.php', ['category' => 'localplugins']));
echo $OUTPUT->footer();

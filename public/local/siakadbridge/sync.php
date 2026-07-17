<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

require_login();
require_capability('local/siakadbridge:sync', context_system::instance());

$run = optional_param('run', 0, PARAM_BOOL);
if ($run && confirm_sesskey()) {
    try {
        $result = \local_siakadbridge\sync\service::run();
        redirect(new moodle_url('/local/siakadbridge/sync.php'), get_string('syncsuccess', 'local_siakadbridge', $result));
    } catch (Throwable $exception) {
        redirect(
            new moodle_url('/local/siakadbridge/sync.php'),
            get_string('syncfailed', 'local_siakadbridge', $exception->getMessage()),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/siakadbridge/sync.php'));
$PAGE->set_title(get_string('manualsync', 'local_siakadbridge'));
$PAGE->set_heading(get_string('manualsync', 'local_siakadbridge'));

echo $OUTPUT->header();
echo $OUTPUT->single_button(
    new moodle_url('/local/siakadbridge/sync.php', ['run' => 1, 'sesskey' => sesskey()]),
    get_string('syncnow', 'local_siakadbridge'),
    'post'
);
echo $OUTPUT->heading(get_string('synclogs', 'local_siakadbridge'), 3);
$table = new html_table();
$table->head = ['Time', 'Source', 'Status', 'Inserted', 'Updated', 'Failed', 'Message'];
foreach ($DB->get_records('local_siakad_synclog', null, 'timecreated DESC', '*', 0, 20) as $log) {
    $table->data[] = [
        userdate($log->timecreated),
        s($log->source),
        s($log->status),
        $log->inserted,
        $log->updated,
        $log->failed,
        s($log->message ?? ''),
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();

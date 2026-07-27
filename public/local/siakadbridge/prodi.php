<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

require_login();
require_capability('local/siakadbridge:manage', context_system::instance());

$reconcile = optional_param('reconcile', 0, PARAM_BOOL);
if ($reconcile && confirm_sesskey()) {
    $result = \local_siakadbridge\manager::reconcile_moodle_access();
    redirect(
        new moodle_url('/local/siakadbridge/prodi.php'),
        get_string('reconciled', 'local_siakadbridge', $result)
    );
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/siakadbridge/prodi.php'));
$PAGE->set_title(get_string('manageprodi', 'local_siakadbridge'));
$PAGE->set_heading(get_string('manageprodi', 'local_siakadbridge'));

echo $OUTPUT->header();
echo $OUTPUT->single_button(new moodle_url('/local/siakadbridge/edit_prodi.php'), get_string('addprodi', 'local_siakadbridge'), 'get');
echo $OUTPUT->single_button(
    new moodle_url('/local/siakadbridge/prodi.php', ['reconcile' => 1, 'sesskey' => sesskey()]),
    get_string('reconcile', 'local_siakadbridge'),
    'post'
);

$table = new html_table();
$table->head = [
    get_string('prodi_code', 'local_siakadbridge'),
    get_string('prodi_name', 'local_siakadbridge'),
    get_string('active', 'local_siakadbridge'),
    get_string('moodle_category', 'local_siakadbridge'),
    get_string('actions'),
];
foreach ($DB->get_records('local_siakad_prodi', null, 'nama ASC') as $record) {
    $table->data[] = [
        s($record->kode),
        s($record->nama),
        (int) $record->aktif === 1 ? get_string('yes') : get_string('no'),
        s(\local_siakadbridge\manager::category_name((int) $record->categoryid) ?: get_string('notmapped', 'local_siakadbridge')),
        html_writer::link(
            new moodle_url('/local/siakadbridge/edit_prodi.php', ['id' => $record->id]),
            get_string('edit', 'local_siakadbridge')
        ),
    ];
}
echo html_writer::table($table);
echo $OUTPUT->footer();

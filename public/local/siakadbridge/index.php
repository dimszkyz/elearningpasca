<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

require_login();
require_capability('local/siakadbridge:manage', context_system::instance());

$deleteid = optional_param('delete', 0, PARAM_INT);
if ($deleteid && confirm_sesskey()) {
    $DB->delete_records('local_siakad_tagihan', ['id' => $deleteid]);
    redirect(new moodle_url('/local/siakadbridge/index.php'));
}

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/siakadbridge/index.php'));
$PAGE->set_title(get_string('managetagihan', 'local_siakadbridge'));
$PAGE->set_heading(get_string('managetagihan', 'local_siakadbridge'));

echo $OUTPUT->header();
echo $OUTPUT->single_button(
    new moodle_url('/local/siakadbridge/edit_tagihan.php'),
    get_string('addtagihan', 'local_siakadbridge'),
    'get'
);

$sql = 'SELECT t.*, m.nim, m.nama, p.kode AS prodikode
          FROM {local_siakad_tagihan} t
          JOIN {local_siakad_mahasiswa} m ON m.id = t.mahasiswaid
          JOIN {local_siakad_prodi} p ON p.id = m.prodiid
      ORDER BY t.tahunajaran DESC, t.semester DESC, m.nama ASC';
$records = $DB->get_records_sql($sql);
$table = new html_table();
$table->head = [
    get_string('student', 'local_siakadbridge'),
    get_string('code', 'local_siakadbridge'),
    get_string('academic_year', 'local_siakadbridge'),
    get_string('semester', 'local_siakadbridge'),
    get_string('bill_type', 'local_siakadbridge'),
    get_string('amount', 'local_siakadbridge'),
    get_string('status', 'local_siakadbridge'),
    get_string('required', 'local_siakadbridge'),
    get_string('actions'),
];
foreach ($records as $record) {
    $editurl = new moodle_url('/local/siakadbridge/edit_tagihan.php', ['id' => $record->id]);
    $deleteurl = new moodle_url('/local/siakadbridge/index.php', ['delete' => $record->id, 'sesskey' => sesskey()]);
    $actions = html_writer::link($editurl, get_string('edit', 'local_siakadbridge')) . ' | ' .
        html_writer::link($deleteurl, get_string('delete', 'local_siakadbridge'), [
            'onclick' => "return confirm('" . addslashes_js(get_string('confirmdelete', 'local_siakadbridge')) . "');",
        ]);
    $table->data[] = [
        s($record->nim . ' - ' . $record->nama . ' (' . $record->prodikode . ')'),
        s($record->kodetagihan),
        s($record->tahunajaran),
        s($record->semester),
        s($record->jenis),
        format_float((float) $record->nominal, 0),
        s(get_string('status_' . $record->status, 'local_siakadbridge')),
        (int) $record->wajib === 1 ? get_string('yes') : get_string('no'),
        $actions,
    ];
}
echo html_writer::table($table);

echo $OUTPUT->footer();

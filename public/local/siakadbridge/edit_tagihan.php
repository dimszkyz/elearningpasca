<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

$id = optional_param('id', 0, PARAM_INT);
require_login();
require_capability('local/siakadbridge:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/siakadbridge/edit_tagihan.php', ['id' => $id]));
$PAGE->set_title(get_string($id ? 'edittagihan' : 'addtagihan', 'local_siakadbridge'));
$PAGE->set_heading(get_string($id ? 'edittagihan' : 'addtagihan', 'local_siakadbridge'));

$form = new \local_siakadbridge\form\tagihan_form();
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/siakadbridge/index.php'));
}
if ($data = $form->get_data()) {
    $record = (object) [
        'mahasiswaid' => (int) $data->mahasiswaid,
        'kodetagihan' => trim($data->kodetagihan),
        'tahunajaran' => trim($data->tahunajaran),
        'semester' => trim($data->semester),
        'jenis' => trim($data->jenis),
        'nominal' => max(0, (int) $data->nominal),
        'status' => $data->status,
        'wajib' => empty($data->wajib) ? 0 : 1,
        'paidat' => $data->status === 'lunas' ? ((int) $data->paidat ?: time()) : 0,
        'duedate' => (int) $data->duedate,
        'timemodified' => time(),
    ];
    if ((int) $data->id > 0) {
        $record->id = (int) $data->id;
        $DB->update_record('local_siakad_tagihan', $record);
    } else {
        $record->sourceid = null;
        $DB->insert_record('local_siakad_tagihan', $record);
    }
    redirect(new moodle_url('/local/siakadbridge/index.php'));
}
if ($id) {
    $form->set_data($DB->get_record('local_siakad_tagihan', ['id' => $id], '*', MUST_EXIST));
} else {
    $form->set_data((object) [
        'tahunajaran' => get_config('local_siakadbridge', 'currentyear'),
        'semester' => get_config('local_siakadbridge', 'currentsemester'),
        'jenis' => 'UKT',
        'status' => 'belum_lunas',
        'wajib' => 1,
    ]);
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();

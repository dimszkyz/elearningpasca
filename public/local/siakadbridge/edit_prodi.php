<?php
// This file is part of Moodle - http://moodle.org/

require(__DIR__ . '/../../config.php');

$id = optional_param('id', 0, PARAM_INT);
require_login();
require_capability('local/siakadbridge:manage', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/siakadbridge/edit_prodi.php', ['id' => $id]));
$PAGE->set_title(get_string($id ? 'editprodi' : 'addprodi', 'local_siakadbridge'));
$PAGE->set_heading(get_string($id ? 'editprodi' : 'addprodi', 'local_siakadbridge'));

$form = new \local_siakadbridge\form\prodi_form();
if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/siakadbridge/prodi.php'));
}
if ($data = $form->get_data()) {
    $record = (object) [
        'kode' => strtoupper(trim($data->kode)),
        'nama' => trim($data->nama),
        'aktif' => empty($data->aktif) ? 0 : 1,
        'categoryid' => (int) $data->categoryid,
        'timemodified' => time(),
    ];
    if ((int) $data->id > 0) {
        $record->id = (int) $data->id;
        $DB->update_record('local_siakad_prodi', $record);
    } else {
        $record->sourceid = null;
        $DB->insert_record('local_siakad_prodi', $record);
    }
    redirect(new moodle_url('/local/siakadbridge/prodi.php'));
}
if ($id) {
    $form->set_data($DB->get_record('local_siakad_prodi', ['id' => $id], '*', MUST_EXIST));
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();

<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakadbridge\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/** Billing edit form. */
final class tagihan_form extends \moodleform {
    protected function definition(): void {
        global $DB;

        $mform = $this->_form;
        $students = $DB->get_records_sql(
            'SELECT m.id, m.nim, m.nama, p.kode
               FROM {local_siakad_mahasiswa} m
               JOIN {local_siakad_prodi} p ON p.id = m.prodiid
           ORDER BY m.nama ASC'
        );
        $studentoptions = [];
        foreach ($students as $student) {
            $studentoptions[$student->id] = $student->nim . ' - ' . $student->nama . ' (' . $student->kode . ')';
        }

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('select', 'mahasiswaid', get_string('student', 'local_siakadbridge'), $studentoptions);
        $mform->addRule('mahasiswaid', null, 'required', null, 'client');
        $mform->addElement('text', 'kodetagihan', get_string('code', 'local_siakadbridge'));
        $mform->setType('kodetagihan', PARAM_TEXT);
        $mform->addRule('kodetagihan', null, 'required', null, 'client');
        $mform->addElement('text', 'tahunajaran', get_string('academic_year', 'local_siakadbridge'));
        $mform->setType('tahunajaran', PARAM_TEXT);
        $mform->addRule('tahunajaran', null, 'required', null, 'client');
        $mform->addElement('select', 'semester', get_string('semester', 'local_siakadbridge'), [
            'ganjil' => get_string('semester_ganjil', 'local_siakadbridge'),
            'genap' => get_string('semester_genap', 'local_siakadbridge'),
        ]);
        $mform->addElement('text', 'jenis', get_string('bill_type', 'local_siakadbridge'));
        $mform->setType('jenis', PARAM_TEXT);
        $mform->addRule('jenis', null, 'required', null, 'client');
        $mform->addElement('text', 'nominal', get_string('amount', 'local_siakadbridge'));
        $mform->setType('nominal', PARAM_INT);
        $mform->addRule('nominal', null, 'numeric', null, 'client');
        $mform->addElement('select', 'status', get_string('status', 'local_siakadbridge'), [
            'belum_lunas' => get_string('status_belum_lunas', 'local_siakadbridge'),
            'lunas' => get_string('status_lunas', 'local_siakadbridge'),
            'dibatalkan' => get_string('status_dibatalkan', 'local_siakadbridge'),
        ]);
        $mform->addElement('advcheckbox', 'wajib', get_string('required', 'local_siakadbridge'));
        $mform->setDefault('wajib', 1);
        $mform->addElement('date_time_selector', 'paidat', get_string('paidat', 'local_siakadbridge'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'duedate', get_string('duedate', 'local_siakadbridge'), ['optional' => true]);

        $this->add_action_buttons(true, get_string('savechanges', 'local_siakadbridge'));
    }

    public function validation($data, $files): array {
        global $DB;

        $errors = parent::validation($data, $files);
        if ((int) ($data['nominal'] ?? 0) < 0) {
            $errors['nominal'] = get_string('invaliddata', 'error');
        }
        $existing = $DB->get_record('local_siakad_tagihan', ['kodetagihan' => trim((string) ($data['kodetagihan'] ?? ''))]);
        if ($existing && (int) $existing->id !== (int) ($data['id'] ?? 0)) {
            $errors['kodetagihan'] = get_string('invaliddata', 'error');
        }
        return $errors;
    }
}

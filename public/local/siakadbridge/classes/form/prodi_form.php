<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakadbridge\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/** Study-program mapping form. */
final class prodi_form extends \moodleform {
    protected function definition(): void {
        global $DB;

        $mform = $this->_form;
        $categories = [0 => get_string('notmapped', 'local_siakadbridge')];
        $records = $DB->get_records('course_categories', null, 'name ASC', 'id,name');
        foreach ($records as $category) {
            $categories[$category->id] = $category->name;
        }

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('text', 'kode', get_string('prodi_code', 'local_siakadbridge'));
        $mform->setType('kode', PARAM_ALPHANUMEXT);
        $mform->addRule('kode', null, 'required', null, 'client');
        $mform->addElement('text', 'nama', get_string('prodi_name', 'local_siakadbridge'));
        $mform->setType('nama', PARAM_TEXT);
        $mform->addRule('nama', null, 'required', null, 'client');
        $mform->addElement('advcheckbox', 'aktif', get_string('active', 'local_siakadbridge'));
        $mform->setDefault('aktif', 1);
        $mform->addElement('select', 'categoryid', get_string('moodle_category', 'local_siakadbridge'), $categories);

        $this->add_action_buttons(true, get_string('savechanges', 'local_siakadbridge'));
    }

    public function validation($data, $files): array {
        global $DB;

        $errors = parent::validation($data, $files);
        $existing = $DB->get_record('local_siakad_prodi', ['kode' => trim((string) ($data['kode'] ?? ''))]);
        if ($existing && (int) $existing->id !== (int) ($data['id'] ?? 0)) {
            $errors['kode'] = get_string('invaliddata', 'error');
        }
        return $errors;
    }
}

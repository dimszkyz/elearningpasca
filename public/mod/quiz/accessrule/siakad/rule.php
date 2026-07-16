<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

use local_siakaddummy\service;
use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_settings;

/**
 * Restrict quiz attempts using SIAKAD programme and billing data.
 *
 * @package    quizaccess_siakad
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quizaccess_siakad extends access_rule_base {
    /**
     * Create the rule only when it is enabled for the quiz.
     *
     * @param quiz_settings $quizobj Quiz settings object.
     * @param int $timenow Current timestamp.
     * @param bool $canignoretimelimits Whether time limits may be ignored.
     * @return self|null
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        if (empty($quizobj->get_quiz()->siakadenabled)) {
            return null;
        }

        return new self($quizobj, $timenow);
    }

    /**
     * Explain the configured restriction on the quiz view page.
     *
     * @return string[]
     */
    public function description() {
        $messages = [];
        $prodiids = $this->quiz->siakadprodiids ?? [];
        $names = service::get_prodi_names(is_array($prodiids) ? $prodiids : []);
        if ($names) {
            $messages[] = get_string('descriptionprogrammes', 'quizaccess_siakad', implode(', ', $names));
        }

        if (!empty($this->quiz->siakadrequirepayment)) {
            $messages[] = get_string('descriptionpayment', 'quizaccess_siakad', (object) [
                'academicyear' => $this->quiz->siakadacademicyear ?: get_string('anyperiod', 'quizaccess_siakad'),
                'semester' => !empty($this->quiz->siakadsemester)
                    ? $this->quiz->siakadsemester
                    : get_string('anysemester', 'quizaccess_siakad'),
            ]);
        }

        return $messages;
    }

    /**
     * Prevent a new attempt when the current student is not eligible.
     * Existing attempts are not interrupted if billing data changes later.
     *
     * @param int $numprevattempts Previous attempt count.
     * @param stdClass|null $lastattempt Last attempt record.
     * @return string|false
     */
    public function prevent_new_attempt($numprevattempts, $lastattempt) {
        global $USER;

        $context = $this->quizobj->get_context();
        if (has_capability('mod/quiz:manage', $context) || has_capability('mod/quiz:preview', $context)) {
            return false;
        }

        $result = service::evaluate_quiz_access(
            (int) $USER->id,
            is_array($this->quiz->siakadprodiids ?? null) ? $this->quiz->siakadprodiids : [],
            !empty($this->quiz->siakadrequirepayment),
            trim((string) ($this->quiz->siakadacademicyear ?? '')),
            (int) ($this->quiz->siakadsemester ?? 0),
            trim((string) ($this->quiz->siakadbilltype ?? '')),
        );

        return $result['allowed'] ? false : $result['reason'];
    }

    /**
     * Add SIAKAD fields to the standard Moodle Quiz settings form.
     *
     * @param mod_quiz_mod_form $quizform Quiz form.
     * @param MoodleQuickForm $mform Wrapped form.
     */
    public static function add_settings_form_fields(
        mod_quiz_mod_form $quizform,
        MoodleQuickForm $mform,
    ) {
        $mform->addElement('header', 'siakadheader', get_string('settingsheader', 'quizaccess_siakad'));

        $mform->addElement(
            'advcheckbox',
            'siakadenabled',
            get_string('enabled', 'quizaccess_siakad'),
            get_string('enabled_desc', 'quizaccess_siakad'),
        );
        $mform->setDefault('siakadenabled', 0);

        $mform->addElement(
            'autocomplete',
            'siakadprodiids',
            get_string('allowedprogrammes', 'quizaccess_siakad'),
            service::get_prodi_options(),
            ['multiple' => true],
        );
        $mform->disabledIf('siakadprodiids', 'siakadenabled', 'notchecked');

        $mform->addElement(
            'advcheckbox',
            'siakadrequirepayment',
            get_string('requirepayment', 'quizaccess_siakad'),
            get_string('requirepayment_desc', 'quizaccess_siakad'),
        );
        $mform->setDefault('siakadrequirepayment', 1);
        $mform->disabledIf('siakadrequirepayment', 'siakadenabled', 'notchecked');

        $mform->addElement(
            'text',
            'siakadacademicyear',
            get_string('academicyear', 'quizaccess_siakad'),
            ['size' => 20, 'placeholder' => '2026/2027'],
        );
        $mform->setType('siakadacademicyear', PARAM_TEXT);
        $mform->disabledIf('siakadacademicyear', 'siakadenabled', 'notchecked');
        $mform->disabledIf('siakadacademicyear', 'siakadrequirepayment', 'notchecked');

        $semesteroptions = [0 => get_string('anysemester', 'quizaccess_siakad')];
        for ($semester = 1; $semester <= 14; $semester++) {
            $semesteroptions[$semester] = (string) $semester;
        }
        $mform->addElement(
            'select',
            'siakadsemester',
            get_string('semester', 'quizaccess_siakad'),
            $semesteroptions,
        );
        $mform->disabledIf('siakadsemester', 'siakadenabled', 'notchecked');
        $mform->disabledIf('siakadsemester', 'siakadrequirepayment', 'notchecked');

        $mform->addElement(
            'text',
            'siakadbilltype',
            get_string('billtype', 'quizaccess_siakad'),
            ['size' => 30, 'placeholder' => 'ujian_semester'],
        );
        $mform->setType('siakadbilltype', PARAM_TEXT);
        $mform->setDefault('siakadbilltype', 'ujian_semester');
        $mform->disabledIf('siakadbilltype', 'siakadenabled', 'notchecked');
        $mform->disabledIf('siakadbilltype', 'siakadrequirepayment', 'notchecked');
    }

    /**
     * Validate SIAKAD quiz settings.
     *
     * @param array $errors Existing errors.
     * @param array $data Submitted data.
     * @param array $files Uploaded files.
     * @param mod_quiz_mod_form $quizform Quiz form.
     * @return array
     */
    public static function validate_settings_form_fields(
        array $errors,
        array $data,
        $files,
        mod_quiz_mod_form $quizform,
    ) {
        if (!empty($data['siakadenabled']) && empty($data['siakadprodiids'])) {
            $errors['siakadprodiids'] = get_string('programmerequired', 'quizaccess_siakad');
        }

        return $errors;
    }

    /**
     * Save SIAKAD quiz settings and selected programme mappings.
     *
     * @param stdClass $quiz Submitted quiz data.
     */
    public static function save_settings($quiz) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $now = time();
        $existing = $DB->get_record('quizaccess_siakad', ['quizid' => $quiz->id]);
        $record = (object) [
            'quizid' => $quiz->id,
            'enabled' => empty($quiz->siakadenabled) ? 0 : 1,
            'requirepayment' => empty($quiz->siakadrequirepayment) ? 0 : 1,
            'academicyear' => clean_param((string) ($quiz->siakadacademicyear ?? ''), PARAM_TEXT),
            'semester' => max(0, min(14, (int) ($quiz->siakadsemester ?? 0))),
            'billtype' => clean_param((string) ($quiz->siakadbilltype ?? ''), PARAM_TEXT),
            'timemodified' => $now,
        ];

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('quizaccess_siakad', $record);
        } else {
            $record->timecreated = $now;
            $DB->insert_record('quizaccess_siakad', $record);
        }

        $DB->delete_records('quizaccess_siakad_prodi', ['quizid' => $quiz->id]);
        $prodiids = is_array($quiz->siakadprodiids ?? null) ? $quiz->siakadprodiids : [];
        $prodiids = array_values(array_unique(array_filter(array_map('intval', $prodiids))));
        foreach ($prodiids as $prodiid) {
            if ($DB->record_exists('local_siad_prodi', ['id' => $prodiid, 'active' => 1])) {
                $DB->insert_record('quizaccess_siakad_prodi', (object) [
                    'quizid' => $quiz->id,
                    'prodiid' => $prodiid,
                ]);
            }
        }

        $transaction->allow_commit();
    }

    /**
     * Delete settings when the quiz is removed.
     *
     * @param stdClass $quiz Quiz record.
     */
    public static function delete_settings($quiz) {
        global $DB;

        $DB->delete_records('quizaccess_siakad_prodi', ['quizid' => $quiz->id]);
        $DB->delete_records('quizaccess_siakad', ['quizid' => $quiz->id]);
    }

    /**
     * Load scalar settings together with the quiz record.
     *
     * @param int $quizid Quiz id.
     * @return array
     */
    public static function get_settings_sql($quizid) {
        return [
            'siakadcfg.enabled AS siakadenabled,
             siakadcfg.requirepayment AS siakadrequirepayment,
             siakadcfg.academicyear AS siakadacademicyear,
             siakadcfg.semester AS siakadsemester,
             siakadcfg.billtype AS siakadbilltype',
            'LEFT JOIN {quizaccess_siakad} siakadcfg ON siakadcfg.quizid = quiz.id',
            [],
        ];
    }

    /**
     * Load programme mappings that cannot be represented by one SQL field.
     *
     * @param int $quizid Quiz id.
     * @return array
     */
    public static function get_extra_settings($quizid) {
        global $DB;

        $records = $DB->get_records('quizaccess_siakad_prodi', ['quizid' => $quizid], 'prodiid ASC');
        return [
            'siakadprodiids' => array_values(array_map(
                static fn($record): int => (int) $record->prodiid,
                $records,
            )),
        ];
    }
}

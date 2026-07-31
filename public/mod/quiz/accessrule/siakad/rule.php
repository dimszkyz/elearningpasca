<?php
// This file is part of Moodle - http://moodle.org/

use mod_quiz\local\access_rule_base;
use mod_quiz\quiz_settings;

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz access rule backed by dummy SIAKAD data.
 */
class quizaccess_siakad extends access_rule_base {
    /**
     * Create the rule only when this quiz has at least one SIAKAD programme mapping.
     */
    public static function make(quiz_settings $quizobj, $timenow, $canignoretimelimits) {
        global $DB;

        if (!$DB->record_exists('local_siakad_quizprodi', ['quizid' => $quizobj->get_quiz()->id])) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    /**
     * Prevent students from opening the quiz when programme/payment requirements fail.
     */
    public function prevent_access() {
        global $USER;

        if (has_capability('mod/quiz:manage', $this->quizobj->get_context())) {
            return false;
        }

        $result = \local_siakad\payment_gate::can_access_quiz(
            (int) $USER->id,
            (int) $this->quizobj->get_quiz()->id
        );

        return $result['allowed'] ? false : $result['reason'];
    }

    /**
     * Re-check immediately before an attempt starts.
     */
    public function prevent_new_attempt($numprevattempts, $lastattempt) {
        return $this->prevent_access();
    }

    /**
     * Add programme/payment fields to the quiz settings form.
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform) {
        global $USER;

        $options = [];
        foreach (self::get_selectable_prodi((int) $USER->id) as $prodi) {
            $options[(int) $prodi->id] = format_string($prodi->name) . ' (' . s($prodi->code) . ')';
        }

        $mform->addElement('header', 'siakadgateheader', get_string('siakadgate', 'quizaccess_siakad'));
        $mform->addElement('autocomplete', 'siakadprodiids', get_string('targetprogrammes', 'quizaccess_siakad'), $options, [
            'multiple' => true,
        ]);
        $mform->setType('siakadprodiids', PARAM_INT);
        $mform->addHelpButton('siakadprodiids', 'targetprogrammes', 'quizaccess_siakad');

        $mform->addElement('text', 'siakadperiod', get_string('billingperiod', 'quizaccess_siakad'));
        $mform->setType('siakadperiod', PARAM_ALPHANUMEXT);
        $mform->setDefault('siakadperiod', '2026-GANJIL');

        $mform->addElement('advcheckbox', 'siakadrequirepaid', get_string('requirepaid', 'quizaccess_siakad'));
        $mform->setDefault('siakadrequirepaid', 1);
    }

    /**
     * Programmes a user may target when building an exam.
     *
     * A lecturer linked to SIAKAD is limited to their own programme. Managers and
     * other users holding moodle/site:config see every active programme, which keeps
     * administrative setup and multi-programme exams possible.
     *
     * @return stdClass[]
     */
    protected static function get_selectable_prodi(int $moodleuserid): array {
        $all = \local_siakad\prodi_sync::get_active_prodi();

        if (has_capability('moodle/site:config', context_system::instance(), $moodleuserid)) {
            return $all;
        }

        $lecturer = \local_siakad\payment_gate::get_lecturer($moodleuserid);
        if (!$lecturer) {
            return $all;
        }

        return array_filter($all, static function ($prodi) use ($lecturer) {
            return (int) $prodi->id === (int) $lecturer->prodiid;
        });
    }

    /**
     * Load saved settings into the quiz form.
     */
    public static function get_extra_settings($quizid) {
        global $DB;

        $records = $DB->get_records('local_siakad_quizprodi', ['quizid' => $quizid]);
        if (!$records) {
            return [];
        }

        $first = reset($records);
        $prodiids = [];
        foreach ($records as $record) {
            $prodiids[] = (int) $record->prodiid;
        }

        return [
            'siakadprodiids' => $prodiids,
            'siakadperiod' => $first->period,
            'siakadrequirepaid' => (int) $first->requirepaid,
        ];
    }

    /**
     * Save one mapping per selected programme.
     */
    public static function save_settings($quiz) {
        global $DB, $USER;

        // The form only offers programmes this user may target, so mappings for other
        // programmes must survive the rewrite instead of being silently dropped.
        $selectable = array_map('intval', array_keys(self::get_selectable_prodi((int) $USER->id)));
        $existing = $DB->get_records('local_siakad_quizprodi', ['quizid' => $quiz->id]);
        $preserved = [];
        foreach ($existing as $record) {
            if (!in_array((int) $record->prodiid, $selectable, true)) {
                $preserved[] = (int) $record->prodiid;
            }
        }

        $DB->delete_records('local_siakad_quizprodi', ['quizid' => $quiz->id]);
        $period = trim((string) ($quiz->siakadperiod ?? ''));
        $requirepaid = empty($quiz->siakadrequirepaid) ? 0 : 1;

        $submitted = array_map('intval', (array) ($quiz->siakadprodiids ?? []));
        $prodiids = array_unique(array_merge(array_intersect($submitted, $selectable), $preserved));

        foreach ($prodiids as $prodiid) {
            if ($prodiid <= 0
                    || !\local_siakad\external_db::get()->record_exists('local_siakad_prodi', ['id' => $prodiid, 'active' => 1])) {
                continue;
            }
            $DB->insert_record('local_siakad_quizprodi', (object) [
                'quizid' => $quiz->id,
                'prodiid' => $prodiid,
                'period' => $period,
                'requirepaid' => $requirepaid,
            ]);
        }
    }

    /**
     * Remove settings when a quiz is deleted.
     */
    public static function delete_settings($quiz) {
        global $DB;
        $DB->delete_records('local_siakad_quizprodi', ['quizid' => $quiz->id]);
    }
}

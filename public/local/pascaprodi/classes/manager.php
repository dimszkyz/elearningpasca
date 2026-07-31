<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascaprodi;

use stdClass;

/**
 * Study programme service.
 *
 * Programmes live in {local_pascaprodi_prodi}, not in course categories. Each
 * programme owns one generated student cohort, and a course is linked to a
 * programme by attaching that cohort as a Cohort sync enrolment method. Course
 * categories are therefore free to model whatever content structure the site
 * wants.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manager {
    /** Plugin component name. */
    public const COMPONENT = 'local_pascaprodi';

    /** Programme table. */
    public const TABLE_PRODI = 'local_pascaprodi_prodi';

    /** Student cohort type. */
    public const TYPE_STUDENT = 'student';

    /** Stable idnumber prefix linking generated student cohorts to programmes. */
    public const PRODI_IDNUMBER_PREFIX = 'pasca:prodi:';

    /**
     * Cohort idnumber prefix used while programmes were course categories.
     *
     * Kept so cli/migrate_prodi_categories.php can find the old cohorts and
     * re-key them. Nothing on the runtime path should use it.
     */
    public const LEGACY_CATEGORY_IDNUMBER_PREFIX = 'pasca:prodi-category:';

    /** Old teacher cohort prefix from version 1.1.0. Kept only for cleanup. */
    private const OLD_TEACHER_IDNUMBER_PREFIX = 'pasca:prodi-category-teacher:';

    /** Default API URL for UNW study programs. */
    public const DEFAULT_API_URL = 'https://panel-web.unw.ac.id/api/unw-program-studi';

    /**
     * Check whether automation is enabled.
     */
    public static function is_enabled(): bool {
        return (bool) get_config(self::COMPONENT, 'enabled');
    }

    /**
     * Check whether generated cohort names should follow programme name changes.
     */
    public static function should_update_names(): bool {
        return (bool) get_config(self::COMPONENT, 'updatenames');
    }

    /**
     * Check whether generated cohorts should be archived when a programme is dropped.
     */
    public static function should_archive_deleted(): bool {
        return (bool) get_config(self::COMPONENT, 'archiveondeleted');
    }

    /**
     * Return configured API URL for the programme sync.
     */
    public static function get_sync_api_url(): string {
        $url = get_config(self::COMPONENT, 'syncapiurl');
        $url = $url === false ? self::DEFAULT_API_URL : trim((string) $url);
        return $url !== '' ? $url : self::DEFAULT_API_URL;
    }

    /**
     * Return API timeout in seconds.
     */
    public static function get_sync_api_timeout(): int {
        $timeout = (int) get_config(self::COMPONENT, 'syncapitimeout');
        return $timeout > 0 ? $timeout : 30;
    }

    /**
     * Build the stable generated student cohort idnumber for a programme.
     */
    public static function cohort_idnumber(int $prodiid): string {
        return self::PRODI_IDNUMBER_PREFIX . $prodiid;
    }

    /**
     * Create or update the student cohort linked to a programme.
     */
    public static function ensure_prodi_cohort(int $prodiid): ?int {
        global $CFG, $DB;

        if ($prodiid <= 0) {
            return null;
        }

        $prodi = $DB->get_record(self::TABLE_PRODI, ['id' => $prodiid]);
        if (!$prodi) {
            return null;
        }

        require_once($CFG->dirroot . '/cohort/lib.php');

        $idnumber = self::cohort_idnumber($prodiid);
        $name = self::cohort_name((string) $prodi->name);
        $description = self::cohort_description((string) $prodi->name);
        $systemcontext = \context_system::instance();
        $now = time();

        $cohort = $DB->get_record('cohort', ['idnumber' => $idnumber]);
        if ($cohort) {
            $cohort->contextid = $systemcontext->id;
            if (self::should_update_names()) {
                $cohort->name = $name;
                $cohort->description = $description;
                $cohort->descriptionformat = FORMAT_HTML;
            }
            $cohort->visible = 1;
            // Empty component keeps the cohort editable/assignable from Moodle UI.
            $cohort->component = '';
            $cohort->timemodified = $now;
            cohort_update_cohort($cohort);
            $cohortid = (int) $cohort->id;
        } else {
            $cohort = (object) [
                'contextid' => $systemcontext->id,
                'name' => $name,
                'idnumber' => $idnumber,
                'description' => $description,
                'descriptionformat' => FORMAT_HTML,
                'visible' => 1,
                // Empty component keeps the cohort editable/assignable from Moodle UI.
                'component' => '',
            ];
            $cohortid = (int) cohort_add_cohort($cohort);
        }

        if ((int) $prodi->cohortid !== $cohortid) {
            $DB->set_field(self::TABLE_PRODI, 'cohortid', $cohortid, ['id' => $prodiid]);
        }

        return $cohortid;
    }

    /**
     * Archive the generated student cohort of a programme.
     *
     * The cohort is hidden and renamed rather than deleted, so its members and any
     * historical enrolments keep resolving.
     */
    public static function archive_prodi_cohort(int $prodiid, string $prodiname = ''): void {
        global $CFG, $DB;

        if ($prodiid <= 0) {
            return;
        }

        $cohort = $DB->get_record('cohort', ['idnumber' => self::cohort_idnumber($prodiid)]);
        if (!$cohort) {
            return;
        }

        require_once($CFG->dirroot . '/cohort/lib.php');

        $prefix = self::archive_prefix();
        if (strpos((string) $cohort->name, $prefix) === 0) {
            // Already archived by an earlier sync run.
            return;
        }

        $basename = $prodiname !== ''
            ? $prodiname
            : preg_replace('/^' . preg_quote(self::cohort_name_prefix(), '/') . '/', '', $cohort->name);
        $cohort->name = $prefix . self::cohort_name((string) $basename);
        $cohort->visible = 0;
        $cohort->description = get_string('cohortarchiveddescription', self::COMPONENT, (object) [
            'prodiid' => $prodiid,
            'prodiname' => $prodiname !== '' ? $prodiname : $basename,
        ]);
        $cohort->descriptionformat = FORMAT_HTML;
        $cohort->timemodified = time();
        cohort_update_cohort($cohort);
    }

    /**
     * Fetch the UNW Program Studi API and sync every record into the programme table.
     *
     * @return array{created:int,updated:int,unchanged:int,skipped:int,failed:int,deactivated:int,cohortcreated:int,cohortupdated:int,items:array<int,array<string,string|int>>}
     */
    public static function sync_remote_prodi(): array {
        global $CFG, $DB;

        require_once($CFG->libdir . '/filelib.php');

        $result = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'failed' => 0,
            'deactivated' => 0,
            'cohortcreated' => 0,
            'cohortupdated' => 0,
            'items' => [],
        ];

        $payload = self::fetch_remote_program_studi_payload();
        $items = $payload->data ?? [];
        if (!is_array($items)) {
            throw new \moodle_exception('apisyncinvaliddata', self::COMPONENT);
        }

        $seen = [];
        $sortorder = 0;

        foreach ($items as $item) {
            $normalised = self::normalise_program_studi_item($item);
            if (!$normalised) {
                $result['skipped']++;
                continue;
            }

            $normalised['sortorder'] = $sortorder++;

            try {
                $synced = self::sync_one_prodi($normalised);
                $result[$synced['status']]++;
                $seen[$synced['prodiid']] = true;

                if (!empty($synced['cohortcreated'])) {
                    $result['cohortcreated']++;
                } else if (!empty($synced['cohortid'])) {
                    $result['cohortupdated']++;
                }

                $result['items'][] = [
                    'status' => $synced['status'],
                    'name' => $normalised['name'],
                    'code' => $normalised['code'],
                    'prodiid' => $synced['prodiid'],
                    'cohortid' => $synced['cohortid'],
                ];
            } catch (\Throwable $exception) {
                $result['failed']++;
                $result['items'][] = [
                    'status' => 'failed',
                    'name' => $normalised['name'],
                    'code' => $normalised['code'],
                    'prodiid' => 0,
                    'cohortid' => 0,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        // A programme that disappeared from the API is deactivated, never deleted,
        // so students, bills and quiz mappings pointing at it keep resolving.
        foreach ($DB->get_records(self::TABLE_PRODI, ['active' => 1], '', 'id,name') as $prodi) {
            if (isset($seen[(int) $prodi->id])) {
                continue;
            }

            $DB->update_record(self::TABLE_PRODI, (object) [
                'id' => $prodi->id,
                'active' => 0,
                'timemodified' => time(),
            ]);
            $result['deactivated']++;

            if (self::should_archive_deleted()) {
                self::archive_prodi_cohort((int) $prodi->id, (string) $prodi->name);
            }
        }

        return $result;
    }

    /**
     * Fetch and decode API payload.
     */
    private static function fetch_remote_program_studi_payload(): stdClass {
        $url = self::get_sync_api_url();
        $timeout = self::get_sync_api_timeout();
        $response = download_file_content($url, null, null, false, $timeout, 20);

        if ($response === false || trim((string) $response) === '') {
            throw new \moodle_exception('apisyncfailed', self::COMPONENT, '', $url);
        }

        $payload = json_decode((string) $response);
        if (!is_object($payload) || json_last_error() !== JSON_ERROR_NONE) {
            throw new \moodle_exception('apisyncinvalidjson', self::COMPONENT, '', json_last_error_msg());
        }

        return $payload;
    }

    /**
     * Normalise one API item into programme fields.
     *
     * @param mixed $item
     * @return array{name:string,code:string,jenjang:string,facultyname:string,facultycode:string}|null
     */
    private static function normalise_program_studi_item($item): ?array {
        if (!is_object($item)) {
            return null;
        }

        $jenjang = trim((string) ($item->jenjang ?? ''));
        $nama = trim((string) ($item->nama ?? ''));
        $slug = trim((string) ($item->slug ?? ''));
        if ($nama === '' || $slug === '') {
            return null;
        }

        $name = self::trim_field(trim($jenjang . ' ' . $nama), 255);
        $code = self::trim_field(clean_param($slug, PARAM_TEXT), 100);

        if ($name === '' || $code === '') {
            return null;
        }

        return [
            'name' => $name,
            'code' => $code,
            'jenjang' => self::trim_field($jenjang, 50),
            'facultyname' => self::trim_field(trim((string) ($item->unwFakultas->nama ?? '')), 255),
            'facultycode' => self::trim_field(trim((string) ($item->unwFakultas->page_slug ?? '')), 100),
        ];
    }

    /**
     * Create or update one programme row from the remote API.
     *
     * @param array<string,string|int> $data Normalised programme fields.
     * @return array{status:string,prodiid:int,cohortid:int,cohortcreated:bool}
     */
    private static function sync_one_prodi(array $data): array {
        global $DB;

        $now = time();
        $status = 'unchanged';
        $existing = $DB->get_record(self::TABLE_PRODI, ['code' => $data['code']]);

        if ($existing) {
            $changed = false;
            foreach (['name', 'jenjang', 'facultyname', 'facultycode', 'sortorder'] as $field) {
                if ((string) ($existing->$field ?? '') !== (string) $data[$field]) {
                    $changed = true;
                    break;
                }
            }
            $changed = $changed || (int) $existing->active !== 1;

            if ($changed) {
                $DB->update_record(self::TABLE_PRODI, (object) array_merge($data, [
                    'id' => $existing->id,
                    'active' => 1,
                    'timemodified' => $now,
                ]));
                $status = 'updated';
            }

            $prodiid = (int) $existing->id;
        } else {
            $prodiid = (int) $DB->insert_record(self::TABLE_PRODI, (object) array_merge($data, [
                'active' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ]));
            $status = 'created';
        }

        $cohortexisted = $DB->record_exists('cohort', ['idnumber' => self::cohort_idnumber($prodiid)]);
        $cohortid = self::ensure_prodi_cohort($prodiid);

        return [
            'status' => $status,
            'prodiid' => $prodiid,
            'cohortid' => (int) $cohortid,
            'cohortcreated' => !$cohortexisted && !empty($cohortid),
        ];
    }

    /**
     * Return the programme IDs currently linked to a course.
     *
     * A course is linked to a programme through a Cohort sync enrolment instance
     * pointing at that programme's generated student cohort, so the enrolment table
     * is the source of truth.
     *
     * @return int[] Programme IDs, sorted ascending.
     */
    public static function get_course_prodi(int $courseid): array {
        global $DB;

        if ($courseid <= 0) {
            return [];
        }

        $like = $DB->sql_like('c.idnumber', ':prefix');
        $sql = "SELECT DISTINCT c.idnumber
                  FROM {enrol} e
                  JOIN {cohort} c ON c.id = e.customint1
                 WHERE e.courseid = :courseid
                   AND e.enrol = 'cohort'
                   AND {$like}";
        $records = $DB->get_fieldset_sql($sql, [
            'courseid' => $courseid,
            'prefix' => $DB->sql_like_escape(self::PRODI_IDNUMBER_PREFIX) . '%',
        ]);

        $prodiids = [];
        foreach ($records as $idnumber) {
            $prodiid = (int) substr((string) $idnumber, strlen(self::PRODI_IDNUMBER_PREFIX));
            if ($prodiid > 0) {
                $prodiids[] = $prodiid;
            }
        }

        $prodiids = array_values(array_unique($prodiids));
        sort($prodiids);

        return $prodiids;
    }

    /**
     * Make the programme Cohort sync instances of a course match the given list.
     *
     * Instances for newly selected programmes are added, and instances for
     * deselected programmes are removed. Only generated student cohorts are
     * touched, so Cohort sync methods added by hand survive untouched.
     *
     * @param int[] $prodiids Programme IDs that should remain linked.
     * @return array{added:int,removed:int,skipped:int}
     */
    public static function set_course_prodi(int $courseid, array $prodiids): array {
        global $CFG, $DB;

        $result = ['added' => 0, 'removed' => 0, 'skipped' => 0];

        if ($courseid <= 0) {
            return $result;
        }

        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course) {
            return $result;
        }

        require_once($CFG->dirroot . '/enrol/cohort/lib.php');

        $cohortplugin = enrol_get_plugin('cohort');
        if (!$cohortplugin) {
            return $result;
        }

        $wanted = array_values(array_unique(array_filter(array_map('intval', $prodiids))));
        $current = self::get_course_prodi($courseid);

        foreach (array_diff($wanted, $current) as $prodiid) {
            $cohortid = self::ensure_prodi_cohort((int) $prodiid);
            if (!$cohortid) {
                $result['skipped']++;
                continue;
            }

            if (self::add_cohort_enrolment($course, (int) $cohortid, 'student')) {
                $result['added']++;
            } else {
                $result['skipped']++;
            }
        }

        foreach (array_diff($current, $wanted) as $prodiid) {
            $cohort = $DB->get_record('cohort', ['idnumber' => self::cohort_idnumber((int) $prodiid)]);
            if (!$cohort) {
                $result['skipped']++;
                continue;
            }

            $instances = $DB->get_records('enrol', [
                'courseid' => $courseid,
                'enrol' => 'cohort',
                'customint1' => (int) $cohort->id,
            ]);

            foreach ($instances as $instance) {
                $cohortplugin->delete_instance($instance);
                $result['removed']++;
            }
        }

        return $result;
    }

    /**
     * Build the programme options offered by the course and user forms.
     *
     * @return array<int,string> Programme ID => display label.
     */
    public static function get_prodi_options(): array {
        global $DB;

        $options = [];
        $records = $DB->get_records(self::TABLE_PRODI, ['active' => 1], 'sortorder ASC, name ASC', 'id,name');

        foreach ($records as $record) {
            $options[(int) $record->id] = format_string($record->name);
        }

        return $options;
    }

    /**
     * Return one programme record, or null.
     */
    public static function get_prodi(int $prodiid): ?stdClass {
        global $DB;

        if ($prodiid <= 0) {
            return null;
        }

        $record = $DB->get_record(self::TABLE_PRODI, ['id' => $prodiid]);

        return $record ?: null;
    }

    /**
     * Add one Cohort sync enrolment instance if it does not already exist.
     */
    private static function add_cohort_enrolment(stdClass $course, int $cohortid, string $roleshortname): bool {
        global $DB;

        if ($DB->record_exists('enrol', [
            'courseid' => $course->id,
            'enrol' => 'cohort',
            'customint1' => $cohortid,
        ])) {
            return false;
        }

        $cohortplugin = enrol_get_plugin('cohort');
        if (!$cohortplugin) {
            return false;
        }

        $role = $DB->get_record('role', ['shortname' => $roleshortname]);
        if (!$role) {
            return false;
        }

        $cohort = $DB->get_record('cohort', ['id' => $cohortid]);
        if (!$cohort) {
            return false;
        }

        $cohortplugin->add_instance($course, [
            'status' => ENROL_INSTANCE_ENABLED,
            'name' => get_string('cohortenrolname', self::COMPONENT, $cohort->name),
            'roleid' => (int) $role->id,
            'customint1' => $cohortid,
        ]);

        return true;
    }

    /**
     * Remove old teacher cohort artefacts introduced in version 1.1.0.
     *
     * Existing users and system role assignments are not touched. Teacher cohorts are
     * hidden instead of deleted when they already contain members.
     *
     * @return array{enrolments:int,deleted:int,archived:int}
     */
    public static function cleanup_old_teacher_cohorts(): array {
        global $CFG, $DB;

        $result = [
            'enrolments' => 0,
            'deleted' => 0,
            'archived' => 0,
        ];

        $select = $DB->sql_like('idnumber', ':prefix', false);
        $cohorts = $DB->get_records_select('cohort', $select, ['prefix' => self::OLD_TEACHER_IDNUMBER_PREFIX . '%']);
        if (!$cohorts) {
            return $result;
        }

        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/enrol/cohort/lib.php');

        $cohortplugin = enrol_get_plugin('cohort');
        [$insql, $params] = $DB->get_in_or_equal(array_keys($cohorts), SQL_PARAMS_NAMED);

        if ($cohortplugin) {
            $instances = $DB->get_records_select('enrol', "enrol = :enrol AND customint1 {$insql}", ['enrol' => 'cohort'] + $params);
            foreach ($instances as $instance) {
                $cohortplugin->delete_instance($instance);
                $result['enrolments']++;
            }
        }

        foreach ($cohorts as $cohort) {
            if (!$DB->record_exists('cohort_members', ['cohortid' => $cohort->id])) {
                cohort_delete_cohort($cohort);
                $result['deleted']++;
                continue;
            }

            $cohort->visible = 0;
            if (strpos($cohort->name, self::archive_prefix()) !== 0) {
                $cohort->name = self::archive_prefix() . $cohort->name;
            }
            $cohort->description = get_string('teachercohortarchiveddescription', self::COMPONENT);
            $cohort->descriptionformat = FORMAT_HTML;
            $cohort->timemodified = time();
            cohort_update_cohort($cohort);
            $result['archived']++;
        }

        return $result;
    }

    /**
     * Build the generated cohort display name.
     */
    private static function cohort_name(string $prodiname): string {
        return self::cohort_name_prefix() . trim($prodiname);
    }

    /**
     * Return configured cohort name prefix.
     */
    private static function cohort_name_prefix(): string {
        $prefix = get_config(self::COMPONENT, 'nameprefix');
        return $prefix === false ? get_string('defaultnameprefix', self::COMPONENT) : (string) $prefix;
    }

    /**
     * Return configured archive prefix.
     */
    private static function archive_prefix(): string {
        $prefix = get_config(self::COMPONENT, 'archiveprefix');
        return $prefix === false ? get_string('defaultarchiveprefix', self::COMPONENT) : (string) $prefix;
    }

    /**
     * Build generated cohort description.
     */
    private static function cohort_description(string $prodiname): string {
        return get_string('cohortdescription', self::COMPONENT, $prodiname);
    }

    /**
     * Cut a value to the column width without breaking multibyte characters.
     */
    private static function trim_field(string $value, int $length): string {
        $value = trim($value);

        return \core_text::strlen($value) > $length ? \core_text::substr($value, 0, $length) : $value;
    }
}

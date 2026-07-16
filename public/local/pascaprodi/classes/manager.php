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
 * Prodi category cohort automation service.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manager {
    /** Plugin component name. */
    public const COMPONENT = 'local_pascaprodi';

    /** Student cohort type. */
    public const TYPE_STUDENT = 'student';

    /** Stable idnumber prefix linking generated student cohorts to course categories. */
    public const STUDENT_IDNUMBER_PREFIX = 'pasca:prodi-category:';

    /** Old teacher cohort prefix from version 1.1.0. Kept only for cleanup. */
    private const OLD_TEACHER_IDNUMBER_PREFIX = 'pasca:prodi-category-teacher:';

    /** Default API URL for UNW study programs. */
    public const DEFAULT_API_URL = 'https://panel-web.unw.ac.id/api/unw-program-studi';

    /** Only this jenjang is synced into Moodle course categories. */
    private const SYNC_JENJANG = 'magister';

    /**
     * Check whether automation is enabled.
     */
    public static function is_enabled(): bool {
        return (bool) get_config(self::COMPONENT, 'enabled');
    }

    /**
     * Check whether generated cohort names should follow category name changes.
     */
    public static function should_update_names(): bool {
        return (bool) get_config(self::COMPONENT, 'updatenames');
    }

    /**
     * Check whether generated cohorts should be archived on category deletion.
     */
    public static function should_archive_deleted(): bool {
        return (bool) get_config(self::COMPONENT, 'archiveondeleted');
    }

    /**
     * Check whether student cohorts should be automatically linked to new courses.
     */
    public static function should_autoenrol_students(): bool {
        return (bool) get_config(self::COMPONENT, 'autoenrolstudents');
    }

    /**
     * Return configured API URL for category sync.
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
     * Determine whether a role should be assigned at Prodi/category context.
     *
     * Student-like roles are handled through the generated student cohort instead.
     * Teacher, lecturer, course creator, manager, and custom globalteacher roles can
     * be assigned to one or more selected Prodi categories.
     */
    public static function is_category_role(stdClass $role): bool {
        $shortname = strtolower((string) ($role->shortname ?? ''));
        $name = strtolower((string) ($role->name ?? ''));
        $archetype = strtolower((string) ($role->archetype ?? ''));
        $text = $shortname . ' ' . $name . ' ' . $archetype;

        $keywords = [
            'teacher',
            'globalteacher',
            'grandteacher',
            'editingteacher',
            'lecturer',
            'dosen',
            'coursecreator',
            'creator',
            'manager',
        ];

        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build the stable generated student cohort idnumber for a category.
     */
    public static function cohort_idnumber(int $categoryid): string {
        return self::STUDENT_IDNUMBER_PREFIX . $categoryid;
    }

    /**
     * Create or update the student cohort linked to a course category.
     */
    public static function ensure_category_cohort(int $categoryid): ?int {
        global $CFG, $DB;

        if ($categoryid <= 0) {
            return null;
        }

        $category = $DB->get_record('course_categories', ['id' => $categoryid]);
        if (!$category) {
            return null;
        }

        require_once($CFG->dirroot . '/cohort/lib.php');

        $idnumber = self::cohort_idnumber($categoryid);
        $name = self::cohort_name((string) $category->name);
        $description = self::cohort_description((string) $category->name);
        $systemcontext = \context_system::instance();
        $now = time();

        $cohort = $DB->get_record('cohort', ['idnumber' => $idnumber]);
        if ($cohort) {
            $cohort->contextid = $systemcontext->id;
            $cohort->name = $name;
            $cohort->description = $description;
            $cohort->descriptionformat = FORMAT_HTML;
            $cohort->visible = 1;
            // Empty component keeps the cohort editable/assignable from Moodle UI.
            $cohort->component = '';
            $cohort->timemodified = $now;
            cohort_update_cohort($cohort);
            return (int) $cohort->id;
        }

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

        return (int) cohort_add_cohort($cohort);
    }

    /**
     * Compatibility wrapper for code that expects a plural method.
     *
     * @return array{student:int|null}
     */
    public static function ensure_category_cohorts(int $categoryid): array {
        return [
            self::TYPE_STUDENT => self::ensure_category_cohort($categoryid),
        ];
    }

    /**
     * Archive generated student cohort when a category is deleted.
     */
    public static function archive_category_cohort(int $categoryid, string $categoryname = ''): void {
        global $CFG, $DB;

        if ($categoryid <= 0) {
            return;
        }

        $cohort = $DB->get_record('cohort', ['idnumber' => self::cohort_idnumber($categoryid)]);
        if (!$cohort) {
            return;
        }

        require_once($CFG->dirroot . '/cohort/lib.php');

        $basename = $categoryname !== ''
            ? $categoryname
            : preg_replace('/^' . preg_quote(self::cohort_name_prefix(), '/') . '/', '', $cohort->name);
        $cohort->name = self::archive_prefix() . self::cohort_name((string) $basename);
        $cohort->visible = 0;
        $cohort->description = get_string('cohortarchiveddescription', self::COMPONENT, (object) [
            'categoryid' => $categoryid,
            'categoryname' => $categoryname !== '' ? $categoryname : $cohort->name,
        ]);
        $cohort->descriptionformat = FORMAT_HTML;
        $cohort->timemodified = time();
        cohort_update_cohort($cohort);
    }

    /**
     * Synchronise generated student cohorts for all existing course categories.
     *
     * @return array{created:int,updated:int,skipped:int}
     */
    public static function sync_all_categories(): array {
        global $DB;

        $result = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];

        $categories = $DB->get_records('course_categories', null, 'sortorder ASC', 'id,name');
        foreach ($categories as $category) {
            $idnumber = self::cohort_idnumber((int) $category->id);
            $exists = $DB->record_exists('cohort', ['idnumber' => $idnumber]);
            $cohortid = self::ensure_category_cohort((int) $category->id);

            if (!$cohortid) {
                $result['skipped']++;
            } else if ($exists) {
                $result['updated']++;
            } else {
                $result['created']++;
            }
        }

        return $result;
    }

    /**
     * Fetch UNW Program Studi API and sync Magister records as root Moodle categories.
     *
     * @return array{created:int,updated:int,unchanged:int,skipped:int,failed:int,cohortcreated:int,cohortupdated:int,items:array<int,array<string,string|int>>}
     */
    public static function sync_remote_magister_categories(): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->libdir . '/filelib.php');

        $result = [
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped' => 0,
            'failed' => 0,
            'cohortcreated' => 0,
            'cohortupdated' => 0,
            'items' => [],
        ];

        $payload = self::fetch_remote_program_studi_payload();
        $items = $payload->data ?? [];
        if (!is_array($items)) {
            throw new \moodle_exception('apisyncinvaliddata', self::COMPONENT);
        }

        foreach ($items as $item) {
            $normalised = self::normalise_program_studi_item($item);
            if (!$normalised) {
                $result['skipped']++;
                continue;
            }

            try {
                $synced = self::sync_one_magister_category($normalised['name'], $normalised['idnumber']);
                $result[$synced['status']]++;
                if (!empty($synced['cohortcreated'])) {
                    $result['cohortcreated']++;
                } else if (!empty($synced['cohortid'])) {
                    $result['cohortupdated']++;
                }
                $result['items'][] = [
                    'status' => $synced['status'],
                    'name' => $normalised['name'],
                    'idnumber' => $normalised['idnumber'],
                    'categoryid' => $synced['categoryid'],
                    'cohortid' => $synced['cohortid'],
                ];
            } catch (\Throwable $exception) {
                $result['failed']++;
                $result['items'][] = [
                    'status' => 'failed',
                    'name' => $normalised['name'],
                    'idnumber' => $normalised['idnumber'],
                    'categoryid' => 0,
                    'cohortid' => 0,
                    'message' => $exception->getMessage(),
                ];
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
     * Normalise one API item. Only Magister records are returned.
     *
     * @param mixed $item
     * @return array{name:string,idnumber:string}|null
     */
    private static function normalise_program_studi_item($item): ?array {
        if (!is_object($item)) {
            return null;
        }

        $jenjang = trim((string) ($item->jenjang ?? ''));
        if (strtolower($jenjang) !== self::SYNC_JENJANG) {
            return null;
        }

        $nama = trim((string) ($item->nama ?? ''));
        $slug = trim((string) ($item->slug ?? ''));
        if ($nama === '' || $slug === '') {
            return null;
        }

        $categoryname = trim($jenjang . ' ' . $nama);
        $idnumber = clean_param($slug, PARAM_TEXT);

        if (\core_text::strlen($categoryname) > 255) {
            $categoryname = \core_text::substr($categoryname, 0, 255);
        }
        if (\core_text::strlen($idnumber) > 100) {
            $idnumber = \core_text::substr($idnumber, 0, 100);
        }

        if ($categoryname === '' || $idnumber === '') {
            return null;
        }

        return [
            'name' => $categoryname,
            'idnumber' => $idnumber,
        ];
    }

    /**
     * Create or update one root category from the remote API.
     *
     * @return array{status:string,categoryid:int,cohortid:int,cohortcreated:bool}
     */
    private static function sync_one_magister_category(string $name, string $idnumber): array {
        global $DB;

        $status = 'unchanged';
        $existing = $DB->get_records('course_categories', ['idnumber' => $idnumber], 'id ASC', '*', 0, 1);
        $record = $existing ? reset($existing) : false;

        if ($record) {
            $category = \core_course_category::get((int) $record->id, MUST_EXIST, true);
            $changes = [];
            if ((string) $record->name !== $name) {
                $changes['name'] = $name;
            }
            if ((string) $record->idnumber !== $idnumber) {
                $changes['idnumber'] = $idnumber;
            }
            if ((int) $record->parent !== 0) {
                $changes['parent'] = 0;
            }

            if ($changes) {
                $category->update((object) $changes);
                $status = 'updated';
            }
            $categoryid = (int) $category->id;
        } else {
            $category = \core_course_category::create((object) [
                'name' => $name,
                'idnumber' => $idnumber,
                'parent' => 0,
                'description' => '',
                'descriptionformat' => FORMAT_HTML,
                'visible' => 1,
            ]);
            $categoryid = (int) $category->id;
            $status = 'created';
        }

        $cohortidnumber = self::cohort_idnumber($categoryid);
        $cohortexisted = $DB->record_exists('cohort', ['idnumber' => $cohortidnumber]);
        $cohortid = self::ensure_category_cohort($categoryid);

        return [
            'status' => $status,
            'categoryid' => $categoryid,
            'cohortid' => (int) $cohortid,
            'cohortcreated' => !$cohortexisted && !empty($cohortid),
        ];
    }

    /**
     * Automatically attach generated student cohort to a Moodle course.
     *
     * The course still belongs to one primary Moodle category. For a course shared by
     * multiple Prodi categories, pass extra category IDs from a custom flow or add
     * additional Cohort sync instances manually.
     *
     * @param int $courseid Moodle course ID.
     * @param int[] $extracategoryids Extra Prodi category IDs to link.
     * @return array{student:int,skipped:int}
     */
    public static function enrol_course_category_cohorts(int $courseid, array $extracategoryids = []): array {
        global $CFG, $DB;

        $result = [
            self::TYPE_STUDENT => 0,
            'skipped' => 0,
        ];

        if ($courseid <= 0 || !self::should_autoenrol_students()) {
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

        $categoryids = array_values(array_unique(array_filter(array_map('intval', array_merge(
            [(int) $course->category],
            $extracategoryids
        )))));

        foreach ($categoryids as $categoryid) {
            $cohortid = self::ensure_category_cohort($categoryid);
            if (!$cohortid) {
                $result['skipped']++;
                continue;
            }

            if (self::add_cohort_enrolment($course, (int) $cohortid, 'student')) {
                $result[self::TYPE_STUDENT]++;
            } else {
                $result['skipped']++;
            }
        }

        return $result;
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
    private static function cohort_name(string $categoryname): string {
        return self::cohort_name_prefix() . trim($categoryname);
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
    private static function cohort_description(string $categoryname): string {
        return get_string('cohortdescription', self::COMPONENT, $categoryname);
    }
}

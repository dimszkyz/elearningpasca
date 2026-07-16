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

    /** Teacher cohort type. */
    public const TYPE_TEACHER = 'teacher';

    /** Stable idnumber prefix linking generated student cohorts to course categories. */
    public const STUDENT_IDNUMBER_PREFIX = 'pasca:prodi-category:';

    /** Stable idnumber prefix linking generated teacher cohorts to course categories. */
    public const TEACHER_IDNUMBER_PREFIX = 'pasca:prodi-category-teacher:';

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
     * Check whether teacher cohorts should be automatically linked to new courses.
     */
    public static function should_autoenrol_teachers(): bool {
        return (bool) get_config(self::COMPONENT, 'autoenrolteachers');
    }

    /**
     * Build the stable generated cohort idnumber for a category and type.
     */
    public static function cohort_idnumber(int $categoryid, string $type = self::TYPE_STUDENT): string {
        $prefix = $type === self::TYPE_TEACHER ? self::TEACHER_IDNUMBER_PREFIX : self::STUDENT_IDNUMBER_PREFIX;
        return $prefix . $categoryid;
    }

    /**
     * Create or update the student cohort linked to a course category.
     *
     * Kept as a compatibility wrapper for earlier plugin versions.
     */
    public static function ensure_category_cohort(int $categoryid): ?int {
        return self::ensure_category_type_cohort($categoryid, self::TYPE_STUDENT);
    }

    /**
     * Create or update both student and teacher cohorts linked to a course category.
     *
     * @return array{student:int|null,teacher:int|null}
     */
    public static function ensure_category_cohorts(int $categoryid): array {
        return [
            self::TYPE_STUDENT => self::ensure_category_type_cohort($categoryid, self::TYPE_STUDENT),
            self::TYPE_TEACHER => self::ensure_category_type_cohort($categoryid, self::TYPE_TEACHER),
        ];
    }

    /**
     * Create or update a generated cohort of the requested type.
     */
    private static function ensure_category_type_cohort(int $categoryid, string $type): ?int {
        global $CFG, $DB;

        if ($categoryid <= 0) {
            return null;
        }

        $category = $DB->get_record('course_categories', ['id' => $categoryid]);
        if (!$category) {
            return null;
        }

        require_once($CFG->dirroot . '/cohort/lib.php');

        $idnumber = self::cohort_idnumber($categoryid, $type);
        $name = self::cohort_name((string) $category->name, $type);
        $description = self::cohort_description((string) $category->name, $type);
        $systemcontext = \context_system::instance();
        $now = time();

        $cohort = $DB->get_record('cohort', ['idnumber' => $idnumber]);
        if ($cohort) {
            $cohort->contextid = $systemcontext->id;
            $cohort->name = $name;
            $cohort->description = $description;
            $cohort->descriptionformat = FORMAT_HTML;
            $cohort->visible = 1;
            // Keep generated cohorts manually manageable in Moodle's Cohorts UI.
            // A non-empty component makes Moodle treat the cohort as plugin-owned,
            // which hides actions such as Assign members from administrators.
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
     * Archive generated cohorts when a category is deleted.
     */
    public static function archive_category_cohort(int $categoryid, string $categoryname = ''): void {
        self::archive_category_type_cohort($categoryid, $categoryname, self::TYPE_STUDENT);
        self::archive_category_type_cohort($categoryid, $categoryname, self::TYPE_TEACHER);
    }

    /**
     * Archive one generated cohort type when a category is deleted.
     */
    private static function archive_category_type_cohort(int $categoryid, string $categoryname, string $type): void {
        global $CFG, $DB;

        if ($categoryid <= 0) {
            return;
        }

        $cohort = $DB->get_record('cohort', ['idnumber' => self::cohort_idnumber($categoryid, $type)]);
        if (!$cohort) {
            return;
        }

        require_once($CFG->dirroot . '/cohort/lib.php');

        $basename = $categoryname !== ''
            ? $categoryname
            : preg_replace('/^' . preg_quote(self::cohort_name_prefix($type), '/') . '/', '', $cohort->name);
        $cohort->name = self::archive_prefix() . self::cohort_name((string) $basename, $type);
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
     * Synchronise generated cohorts for all existing course categories.
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
            foreach ([self::TYPE_STUDENT, self::TYPE_TEACHER] as $type) {
                $idnumber = self::cohort_idnumber((int) $category->id, $type);
                $exists = $DB->record_exists('cohort', ['idnumber' => $idnumber]);
                $cohortid = self::ensure_category_type_cohort((int) $category->id, $type);

                if (!$cohortid) {
                    $result['skipped']++;
                } else if ($exists) {
                    $result['updated']++;
                } else {
                    $result['created']++;
                }
            }
        }

        return $result;
    }

    /**
     * Automatically attach generated category cohorts to a Moodle course.
     *
     * The course still belongs to one primary Moodle category. For a course shared by
     * multiple Prodi categories, pass extra category IDs from a custom flow or add
     * additional Cohort sync instances manually.
     *
     * @param int $courseid Moodle course ID.
     * @param int[] $extracategoryids Extra Prodi category IDs to link.
     * @return array{student:int,teacher:int,skipped:int}
     */
    public static function enrol_course_category_cohorts(int $courseid, array $extracategoryids = []): array {
        global $CFG, $DB;

        $result = [
            self::TYPE_STUDENT => 0,
            self::TYPE_TEACHER => 0,
            'skipped' => 0,
        ];

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

        $categoryids = array_values(array_unique(array_filter(array_map('intval', array_merge(
            [(int) $course->category],
            $extracategoryids
        )))));

        foreach ($categoryids as $categoryid) {
            $cohorts = self::ensure_category_cohorts($categoryid);

            if (self::should_autoenrol_students() && !empty($cohorts[self::TYPE_STUDENT])) {
                if (self::add_cohort_enrolment($course, (int) $cohorts[self::TYPE_STUDENT], 'student')) {
                    $result[self::TYPE_STUDENT]++;
                } else {
                    $result['skipped']++;
                }
            }

            if (self::should_autoenrol_teachers() && !empty($cohorts[self::TYPE_TEACHER])) {
                if (self::add_cohort_enrolment($course, (int) $cohorts[self::TYPE_TEACHER], 'editingteacher')) {
                    $result[self::TYPE_TEACHER]++;
                } else {
                    $result['skipped']++;
                }
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
     * Build the generated cohort display name.
     */
    private static function cohort_name(string $categoryname, string $type): string {
        return self::cohort_name_prefix($type) . trim($categoryname);
    }

    /**
     * Return configured cohort name prefix by type.
     */
    private static function cohort_name_prefix(string $type): string {
        $configkey = $type === self::TYPE_TEACHER ? 'teachernameprefix' : 'nameprefix';
        $defaultkey = $type === self::TYPE_TEACHER ? 'defaultteachernameprefix' : 'defaultnameprefix';
        $prefix = get_config(self::COMPONENT, $configkey);
        return $prefix === false ? get_string($defaultkey, self::COMPONENT) : (string) $prefix;
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
    private static function cohort_description(string $categoryname, string $type): string {
        $stringkey = $type === self::TYPE_TEACHER ? 'teachercohortdescription' : 'cohortdescription';
        return get_string($stringkey, self::COMPONENT, $categoryname);
    }
}

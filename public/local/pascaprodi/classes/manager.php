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

    /** Stable idnumber prefix linking generated cohorts to course categories. */
    public const IDNUMBER_PREFIX = 'pasca:prodi-category:';

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
     * Build the stable generated cohort idnumber for a category.
     */
    public static function cohort_idnumber(int $categoryid): string {
        return self::IDNUMBER_PREFIX . $categoryid;
    }

    /**
     * Create or update the cohort linked to a course category.
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
            $cohort->component = self::COMPONENT;
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
            'component' => self::COMPONENT,
        ];

        return (int) cohort_add_cohort($cohort);
    }

    /**
     * Archive the generated cohort when a category is deleted.
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

        $basename = $categoryname !== '' ? $categoryname : preg_replace('/^' . preg_quote(self::cohort_name_prefix(), '/') . '/', '', $cohort->name);
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
     * Synchronise cohorts for all existing course categories.
     *
     * @return array{created:int, updated:int, skipped:int}
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

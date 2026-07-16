<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascaprodi;

/**
 * Event observer for Prodi category cohort automation.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class observer {
    /**
     * Create a cohort when a course category is created.
     */
    public static function course_category_created(\core\event\course_category_created $event): void {
        if (!manager::is_enabled()) {
            return;
        }

        manager::ensure_category_cohort((int) $event->objectid);
    }

    /**
     * Keep the generated cohort name aligned with the category name.
     */
    public static function course_category_updated(\core\event\course_category_updated $event): void {
        if (!manager::is_enabled() || !manager::should_update_names()) {
            return;
        }

        manager::ensure_category_cohort((int) $event->objectid);
    }

    /**
     * Archive the generated cohort when its category is deleted.
     */
    public static function course_category_deleted(\core\event\course_category_deleted $event): void {
        if (!manager::is_enabled() || !manager::should_archive_deleted()) {
            return;
        }

        $name = $event->other['name'] ?? '';
        manager::archive_category_cohort((int) $event->objectid, (string) $name);
    }
}

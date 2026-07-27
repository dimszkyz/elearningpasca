<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascaprodi\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled synchronization of all UNW study-program categories.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sync_remote_categories extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_sync_remote_categories', 'local_pascaprodi');
    }

    public function execute(): void {
        $result = \local_pascaprodi\category_sync_service::run();

        mtrace(sprintf(
            'Study Program categories: %d created, %d updated, %d unchanged, %d skipped, %d failed.',
            $result['created'],
            $result['updated'],
            $result['unchanged'],
            $result['skipped'],
            $result['failed']
        ));
        mtrace(sprintf(
            'Study Program cohorts: %d created, %d updated.',
            $result['cohortcreated'],
            $result['cohortupdated']
        ));

        if ($result['failed'] > 0) {
            throw new \moodle_exception(
                'synccategoriesfailed',
                'local_pascaprodi',
                '',
                $result['failed'] . ' categories failed to synchronise.'
            );
        }
    }
}

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_siakad\task;

/**
 * Scheduled mirror of local_pascaprodi programmes into the SIAKAD programme table.
 *
 * @package    local_siakad
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_prodi_task extends \core\task\scheduled_task {
    /**
     * Task name shown in the scheduled tasks admin page.
     */
    public function get_name(): string {
        return get_string('task_syncprodi', 'local_siakad');
    }

    /**
     * Run the mirror.
     */
    public function execute(): void {
        $result = \local_siakad\prodi_sync::sync_from_pascaprodi();
        mtrace(sprintf(
            'local_siakad prodi sync: created %d, updated %d, unchanged %d, deactivated %d, skipped %d.',
            $result['created'],
            $result['updated'],
            $result['unchanged'],
            $result['deactivated'],
            $result['skipped']
        ));
    }
}

<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakadbridge\task;

defined('MOODLE_INTERNAL') || die();

/** Scheduled combined SIAKAD synchronisation. */
final class sync_siakad extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_sync', 'local_siakadbridge');
    }

    public function execute(): void {
        if ((string) get_config('local_siakadbridge', 'sourcemode') !== 'rest') {
            mtrace('SIAKAD bridge is in manual mode; nothing to synchronise.');
            return;
        }
        if (trim((string) get_config('local_siakadbridge', 'apiurl')) === '') {
            mtrace('No combined SIAKAD endpoint is configured; only dedicated endpoint tasks will run.');
            return;
        }
        try {
            $result = \local_siakadbridge\sync\service::run();
            mtrace(sprintf('SIAKAD sync complete: %d inserted, %d updated, %d failed.',
                $result->inserted, $result->updated, $result->failed));
        } catch (\Throwable $exception) {
            mtrace('SIAKAD sync failed: ' . $exception->getMessage());
            throw $exception;
        }
    }
}

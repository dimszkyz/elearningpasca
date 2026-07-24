<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakadbridge\task;

defined('MOODLE_INTERNAL') || die();

/** Scheduled dedicated study-program synchronisation. */
final class sync_program_studies extends \core\task\scheduled_task {
    public function get_name(): string {
        return get_string('task_sync_programs', 'local_siakadbridge');
    }

    public function execute(): void {
        if ((string) get_config('local_siakadbridge', 'sourcemode') !== 'rest') {
            mtrace('SIAKAD bridge is in manual mode; study programs were not synchronised.');
            return;
        }
        if (trim((string) get_config('local_siakadbridge', 'programapiurl')) === '') {
            mtrace('No dedicated study-program endpoint is configured.');
            return;
        }

        try {
            $result = \local_siakadbridge\sync\program_study_service::run();
            mtrace(sprintf(
                'Study-program sync complete: %d inserted, %d updated, %d failed.',
                $result->inserted,
                $result->updated,
                $result->failed
            ));
        } catch (\Throwable $exception) {
            mtrace('Study-program sync failed: ' . $exception->getMessage());
            throw $exception;
        }
    }
}

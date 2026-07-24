<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'help' => false,
], [
    'h' => 'help',
]);
if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    cli_writeln(<<<'HELP'
Synchronise all campus study programs from the configured dedicated API endpoint.

Configuration:
Site administration -> Plugins -> Local plugins -> SIAKAD bridge settings

Options:
--help, -h  Show this help.
HELP
    );
    exit(0);
}

try {
    $result = \local_siakadbridge\sync\program_study_service::run();
    cli_writeln(sprintf(
        'Study-program sync complete: %d inserted, %d updated, %d failed.',
        $result->inserted,
        $result->updated,
        $result->failed
    ));
    if ((string) $result->message !== '') {
        cli_writeln((string) $result->message);
    }
} catch (\Throwable $exception) {
    cli_error('Study-program sync failed: ' . $exception->getMessage());
}

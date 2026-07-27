<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

try {
    $result = \local_siakadbridge\sync\service::run();
    cli_writeln(sprintf(
        'SIAKAD sync complete: %d inserted, %d updated, %d failed.',
        $result->inserted,
        $result->updated,
        $result->failed
    ));
} catch (Throwable $exception) {
    cli_error('SIAKAD sync failed: ' . $exception->getMessage());
}

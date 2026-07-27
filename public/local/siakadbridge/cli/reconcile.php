<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

$result = \local_siakadbridge\manager::reconcile_moodle_access();
cli_writeln(sprintf(
    'Reconciliation complete: %d linked, %d students added to cohorts, %d lecturer roles assigned.',
    $result->linked,
    $result->students,
    $result->lecturers
));

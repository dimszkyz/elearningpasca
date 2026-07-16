<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Backfill generated cohorts for existing course categories.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknownoption', 'admin', $unrecognized));
}

if (!empty($options['help'])) {
    echo "Synchronise Pasca Prodi cohorts for all existing Moodle course categories.\n\n";
    echo "Options:\n";
    echo "-h, --help     Print this help.\n\n";
    exit(0);
}

$result = \local_pascaprodi\manager::sync_all_categories();

mtrace('Pasca Prodi category cohort sync completed.');
mtrace('Created: ' . $result['created']);
mtrace('Updated: ' . $result['updated']);
mtrace('Skipped: ' . $result['skipped']);

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Synchronise all UNW study programs into Moodle root categories.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params(
    [
        'help' => false,
    ],
    [
        'h' => 'help',
    ]
);

if ($unrecognised) {
    $unrecognised = implode("\n  ", $unrecognised);
    cli_error("Unknown options:\n  {$unrecognised}");
}

if ($options['help']) {
    echo "Synchronise every study program returned by the configured UNW API.\n\n";
    echo "Usage:\n";
    echo "  php local/pascaprodi/cli/sync_remote_categories.php\n";
    echo "  php local/pascaprodi/cli/sync_remote_categories.php --help\n";
    exit(0);
}

try {
    $result = \local_pascaprodi\category_sync_service::run();
    mtrace(sprintf(
        'Study Program category sync complete: %d created, %d updated, %d unchanged, %d skipped, %d failed.',
        $result['created'],
        $result['updated'],
        $result['unchanged'],
        $result['skipped'],
        $result['failed']
    ));
    mtrace(sprintf(
        'Cohorts: %d created, %d updated.',
        $result['cohortcreated'],
        $result['cohortupdated']
    ));

    foreach ($result['items'] as $item) {
        mtrace(sprintf(
            '[%s] %s | idnumber=%s | category=%s | cohort=%s%s',
            strtoupper((string) ($item['status'] ?? '')),
            (string) ($item['name'] ?? ''),
            (string) ($item['idnumber'] ?? ''),
            (string) ($item['categoryid'] ?? ''),
            (string) ($item['cohortid'] ?? ''),
            !empty($item['message']) ? ' | ' . $item['message'] : ''
        ));
    }

    exit($result['failed'] > 0 ? 1 : 0);
} catch (Throwable $exception) {
    cli_error('Study Program category sync failed: ' . $exception->getMessage());
}

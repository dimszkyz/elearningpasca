<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Mirror local_pascaprodi programmes into the dummy SIAKAD programme table.
 *
 * @package    local_siakad
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'period' => '',
    ],
    [
        'h' => 'help',
        'p' => 'period',
    ]
);

if ($unrecognized) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognized)));
}

if ($options['help']) {
    cli_writeln(<<<EOT
Mirror local_pascaprodi_prodi into local_siakad_prodi.

Options:
  -h, --help            Print this help.
  -p, --period=PERIOD   Also refresh exam access for this billing period.

Example:
  php local/siakad/cli/sync_prodi.php --period=2026-GANJIL
EOT);
    exit(0);
}

$result = \local_siakad\prodi_sync::sync_from_pascaprodi();
cli_writeln(sprintf(
    'Prodi sync: created %d, updated %d, unchanged %d, deactivated %d, skipped %d.',
    $result['created'],
    $result['updated'],
    $result['unchanged'],
    $result['deactivated'],
    $result['skipped']
));

$period = trim((string) $options['period']);
if ($period !== '') {
    $access = \local_siakad\billing::refresh_all($period);
    cli_writeln(sprintf(
        'Exam access (%s): granted %d, revoked %d, unchanged %d.',
        $period,
        $access['granted'],
        $access['revoked'],
        $access['skipped']
    ));
}

exit(0);

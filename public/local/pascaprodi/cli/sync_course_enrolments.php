<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Synchronise generated Prodi student cohorts into existing courses by course category.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognized] = cli_get_params([
    'help' => false,
    'categoryid' => 0,
], [
    'h' => 'help',
]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Unknown option(s):\n  {$unrecognized}");
}

if (!empty($options['help'])) {
    cli_writeln('Synchronise generated Prodi student cohorts into existing courses.');
    cli_writeln('');
    cli_writeln('Options:');
    cli_writeln('  --categoryid=ID   Optional course category ID to limit the sync.');
    cli_writeln('  -h, --help        Show this help.');
    exit(0);
}

$categoryid = (int) $options['categoryid'];
$params = [];
$where = 'id <> :siteid';
$params['siteid'] = SITEID;

if ($categoryid > 0) {
    $where .= ' AND category = :categoryid';
    $params['categoryid'] = $categoryid;
}

$courses = $DB->get_records_select('course', $where, $params, 'category ASC, shortname ASC', 'id,category,fullname,shortname');

$total = 0;
$student = 0;
$skipped = 0;

foreach ($courses as $course) {
    $result = \local_pascaprodi\manager::enrol_course_category_cohorts((int) $course->id);
    $total++;
    $student += (int) $result[\local_pascaprodi\manager::TYPE_STUDENT];
    $skipped += (int) $result['skipped'];

    cli_writeln("Course {$course->id} ({$course->shortname}): student instances +{$result[\local_pascaprodi\manager::TYPE_STUDENT]}, skipped {$result['skipped']}");
}

cli_writeln('Done.');
cli_writeln("Courses checked: {$total}");
cli_writeln("Student cohort sync instances created: {$student}");
cli_writeln("Skipped existing/missing instances: {$skipped}");

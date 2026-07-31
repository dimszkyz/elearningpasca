<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Convert legacy Prodi course categories into local_pascaprodi_prodi rows.
 *
 * Study programmes used to be root course categories, each owning a generated
 * student cohort idnumbered pasca:prodi-category:{categoryid}. Programmes now
 * live in their own table and the cohort is keyed pasca:prodi:{prodiid}.
 *
 * This script converts an existing site:
 *
 *   1. Find every course category that owns a legacy cohort.
 *   2. Upsert it as a local_pascaprodi_prodi row.
 *   3. Re-key its cohort. The cohort row itself is kept, so cohort_members and
 *      the enrol instances pointing at cohort.id survive untouched and no
 *      student loses access at any point.
 *   4. Relink local_siakad_prodi to the new programme, when that plugin exists.
 *   5. Move any courses out of the category into --target-category.
 *   6. Delete the now-empty category.
 *
 * Runs as a dry run unless --execute is given.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/enrol/cohort/lib.php');

use local_pascaprodi\manager;

[$options, $unrecognized] = cli_get_params(
    [
        'help' => false,
        'execute' => false,
        'target-category' => '',
    ],
    [
        'h' => 'help',
        'e' => 'execute',
        't' => 'target-category',
    ]
);

if ($unrecognized) {
    cli_error(get_string('cliunknowoption', 'admin', implode("\n  ", $unrecognized)));
}

// Deleting a category goes through core capability checks, which need a user.
\core\session\manager::set_user(get_admin());

if ($options['help']) {
    cli_writeln(<<<EOT
Convert legacy Prodi course categories into local_pascaprodi programmes.

Programmes move out of course categories and into local_pascaprodi_prodi. The
generated student cohort is re-keyed in place, so cohort members and Cohort sync
enrolment methods are preserved. Courses found inside a Prodi category are moved
to --target-category before the category is deleted.

Options:
  -h, --help                   Print this help.
  -e, --execute                Apply the changes. Without it the script only reports.
  -t, --target-category=ID     Course category to move existing courses into.
                               Required when any Prodi category still holds courses.

Examples:
  php local/pascaprodi/cli/migrate_prodi_categories.php
  php local/pascaprodi/cli/migrate_prodi_categories.php --target-category=1 --execute

Back up your database before running with --execute.
EOT);
    exit(0);
}

$execute = (bool) $options['execute'];
$targetcategoryid = (int) $options['target-category'];

if (!$execute) {
    cli_writeln('DRY RUN. Re-run with --execute to apply. Nothing is written below.');
    cli_writeln('');
}

// Legacy Prodi categories are exactly those that own a pasca:prodi-category: cohort.
$prefix = manager::LEGACY_CATEGORY_IDNUMBER_PREFIX;
$like = $DB->sql_like('idnumber', ':prefix');
$legacycohorts = $DB->get_records_select('cohort', $like, [
    'prefix' => $DB->sql_like_escape($prefix) . '%',
], 'idnumber ASC');

if (!$legacycohorts) {
    cli_writeln('No legacy Prodi cohorts found. Nothing to migrate.');
    exit(0);
}

// Resolve the categories behind those cohorts.
$plan = [];
foreach ($legacycohorts as $cohort) {
    $categoryid = (int) substr((string) $cohort->idnumber, strlen($prefix));
    if ($categoryid <= 0) {
        continue;
    }

    $category = $DB->get_record('course_categories', ['id' => $categoryid], 'id,name,idnumber');

    $plan[$categoryid] = (object) [
        'categoryid' => $categoryid,
        'category' => $category,
        'cohort' => $cohort,
        'courseids' => [],
    ];
}

// A previous run may have re-keyed the cohorts but failed to delete the
// categories, for example without the capability to do so. Those categories no
// longer have a legacy cohort to find them by, so match them against the
// programme codes instead and let the run finish the job.
$leftovers = $DB->get_records_sql(
    'SELECT cc.id, cc.name, cc.idnumber, p.id AS prodiid
       FROM {course_categories} cc
       JOIN {' . manager::TABLE_PRODI . '} p ON p.code = cc.idnumber
   ORDER BY cc.id ASC'
);
foreach ($leftovers as $leftover) {
    if (isset($plan[(int) $leftover->id])) {
        continue;
    }

    $plan[(int) $leftover->id] = (object) [
        'categoryid' => (int) $leftover->id,
        'category' => $leftover,
        'cohort' => $DB->get_record('cohort', ['idnumber' => manager::cohort_idnumber((int) $leftover->prodiid)]),
        'courseids' => [],
    ];
}

if (!$plan) {
    cli_writeln('No legacy Prodi categories resolved. Nothing to migrate.');
    exit(0);
}

ksort($plan);

foreach ($plan as $entry) {
    if (!$entry->category) {
        continue;
    }
    $entry->courseids = array_map('intval', $DB->get_fieldset_select(
        'course',
        'id',
        'category = :categoryid',
        ['categoryid' => $entry->categoryid]
    ));
}

// Validate the move target before touching anything.
$totalcourses = 0;
foreach ($plan as $entry) {
    $totalcourses += count($entry->courseids);
}

if ($totalcourses > 0) {
    if ($targetcategoryid <= 0) {
        cli_error(
            "{$totalcourses} course(s) still live inside Prodi categories. "
            . 'Pass --target-category=ID to say where they should move.'
        );
    }
    if (isset($plan[$targetcategoryid])) {
        cli_error("Target category {$targetcategoryid} is itself a Prodi category being removed.");
    }
    if (!$DB->record_exists('course_categories', ['id' => $targetcategoryid])) {
        cli_error("Target category {$targetcategoryid} does not exist.");
    }
}

$result = [
    'prodicreated' => 0,
    'prodilinked' => 0,
    'cohortrekeyed' => 0,
    'siakadlinked' => 0,
    'coursesmoved' => 0,
    'categoriesdeleted' => 0,
    'skipped' => 0,
];

// The SIAKAD projection may live in its own database, so resolve its connection
// through the plugin rather than assuming it sits alongside the Moodle tables.
$siakaddb = class_exists('\\local_siakad\\external_db') ? \local_siakad\external_db::get() : $DB;
$siakadavailable = $siakaddb->get_manager()->table_exists('local_siakad_prodi')
    && $siakaddb->get_manager()->field_exists('local_siakad_prodi', 'pascaprodiid');

foreach ($plan as $entry) {
    $categoryid = $entry->categoryid;
    $cohort = $entry->cohort;

    if (!$entry->category) {
        cli_writeln("- category {$categoryid}: missing, cohort '{$cohort->name}' left as is (skipped)");
        $result['skipped']++;
        continue;
    }

    $name = (string) $entry->category->name;
    $code = trim((string) $entry->category->idnumber);
    if ($code === '') {
        $code = 'category-' . $categoryid;
    }
    $code = \core_text::strlen($code) > 100 ? \core_text::substr($code, 0, 100) : $code;

    cli_writeln("Category {$categoryid}: {$name}");

    // 1. Upsert the programme.
    $existing = $DB->get_record(manager::TABLE_PRODI, ['code' => $code]);
    if ($existing) {
        $prodiid = (int) $existing->id;
        cli_writeln("  - programme exists as id {$prodiid} (code {$code})");
        $result['prodilinked']++;
    } else if ($execute) {
        $now = time();
        $prodiid = (int) $DB->insert_record(manager::TABLE_PRODI, (object) [
            'code' => $code,
            'name' => $name,
            'jenjang' => '',
            'facultyname' => '',
            'facultycode' => '',
            'cohortid' => $cohort ? (int) $cohort->id : null,
            'sortorder' => 0,
            'active' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        cli_writeln("  - programme created as id {$prodiid} (code {$code})");
        $result['prodicreated']++;
    } else {
        $prodiid = 0;
        cli_writeln("  - would create programme (code {$code})");
        $result['prodicreated']++;
    }

    // 2. Re-key the cohort in place so members and enrolments survive.
    $newidnumber = $prodiid > 0 ? manager::cohort_idnumber($prodiid) : manager::PRODI_IDNUMBER_PREFIX . '<new>';
    $members = $cohort ? $DB->count_records('cohort_members', ['cohortid' => $cohort->id]) : 0;
    $enrolments = $cohort ? $DB->count_records('enrol', ['enrol' => 'cohort', 'customint1' => $cohort->id]) : 0;

    // A programme sync may already have created the target cohort, in which case
    // the idnumber is taken and the legacy cohort has to be merged into it rather
    // than renamed onto a collision.
    $target = $prodiid > 0 ? $DB->get_record('cohort', ['idnumber' => $newidnumber]) : false;

    if (!$cohort) {
        cli_writeln("  - cohort already keyed as {$newidnumber}");
    } else if ($target && (int) $target->id !== (int) $cohort->id) {
        cli_writeln(
            "  - cohort {$cohort->id} merged into existing cohort {$target->id} ({$newidnumber}):"
            . " {$members} member(s), {$enrolments} enrolment method(s)"
        );

        if ($execute) {
            foreach ($DB->get_records('cohort_members', ['cohortid' => $cohort->id]) as $member) {
                if (!$DB->record_exists('cohort_members', ['cohortid' => $target->id, 'userid' => $member->userid])) {
                    $DB->insert_record('cohort_members', (object) [
                        'cohortid' => $target->id,
                        'userid' => $member->userid,
                        'timeadded' => $member->timeadded,
                    ]);
                }
            }
            $DB->delete_records('cohort_members', ['cohortid' => $cohort->id]);

            foreach ($DB->get_records('enrol', ['enrol' => 'cohort', 'customint1' => $cohort->id]) as $instance) {
                $duplicate = $DB->record_exists_select(
                    'enrol',
                    'courseid = :courseid AND enrol = :enrol AND customint1 = :cohortid AND id <> :id',
                    [
                        'courseid' => $instance->courseid,
                        'enrol' => 'cohort',
                        'cohortid' => $target->id,
                        'id' => $instance->id,
                    ]
                );

                if ($duplicate) {
                    // The target cohort already syncs this course; drop the spare
                    // instance rather than leaving two identical methods behind.
                    enrol_get_plugin('cohort')->delete_instance($instance);
                } else {
                    $DB->set_field('enrol', 'customint1', $target->id, ['id' => $instance->id]);
                }
            }

            // The emptied legacy cohort keeps a unique idnumber of its own so it
            // never collides again, and stays hidden for auditing.
            $DB->update_record('cohort', (object) [
                'id' => $cohort->id,
                'idnumber' => $cohort->idnumber . ':merged',
                'visible' => 0,
                'timemodified' => time(),
            ]);
            $DB->set_field(manager::TABLE_PRODI, 'cohortid', (int) $target->id, ['id' => $prodiid]);
        }
    } else {
        cli_writeln(
            "  - cohort {$cohort->id}: {$cohort->idnumber} -> {$newidnumber}"
            . " ({$members} member(s), {$enrolments} enrolment method(s) preserved)"
        );

        if ($execute) {
            $DB->update_record('cohort', (object) [
                'id' => $cohort->id,
                'idnumber' => $newidnumber,
                'timemodified' => time(),
            ]);
            $DB->set_field(manager::TABLE_PRODI, 'cohortid', (int) $cohort->id, ['id' => $prodiid]);
        }
    }
    if ($cohort) {
        $result['cohortrekeyed']++;
    }

    // 3. Relink the SIAKAD projection by code, which used the same category idnumber.
    if ($siakadavailable) {
        $siakad = $siakaddb->get_record('local_siakad_prodi', ['code' => $code], 'id,name');
        if (!$siakad) {
            $records = $siakaddb->get_records_select(
                'local_siakad_prodi',
                $siakaddb->sql_equal('name', ':name', false),
                ['name' => $name],
                'id ASC',
                'id,name',
                0,
                1
            );
            $siakad = $records ? reset($records) : null;
        }

        if ($siakad) {
            cli_writeln("  - local_siakad_prodi {$siakad->id} linked to programme");
            if ($execute && $prodiid > 0) {
                $siakaddb->update_record('local_siakad_prodi', (object) [
                    'id' => $siakad->id,
                    'pascaprodiid' => $prodiid,
                    'active' => 1,
                    'timemodified' => time(),
                ]);
            }
            $result['siakadlinked']++;
        }
    }

    // 4. Move courses out before the category can be deleted.
    if ($entry->courseids) {
        $count = count($entry->courseids);
        cli_writeln("  - moving {$count} course(s) to category {$targetcategoryid}");
        if ($execute) {
            move_courses($entry->courseids, $targetcategoryid);
        }
        $result['coursesmoved'] += $count;
    }

    // 5. Delete the category, only once it is provably empty.
    if ($execute) {
        $remaining = $DB->count_records('course', ['category' => $categoryid]);
        if ($remaining > 0) {
            cli_writeln("  - category still holds {$remaining} course(s), NOT deleted");
            $result['skipped']++;
            continue;
        }

        $coursecat = core_course_category::get($categoryid, IGNORE_MISSING, true);
        if (!$coursecat) {
            cli_writeln('  - category already gone');
            continue;
        }
        if (!$coursecat->can_delete_full()) {
            cli_writeln('  - no permission to delete this category, NOT deleted');
            $result['skipped']++;
            continue;
        }

        $coursecat->delete_full(false);
        cli_writeln('  - category deleted');
    } else {
        cli_writeln('  - would delete category');
    }
    $result['categoriesdeleted']++;
}

cli_writeln('');
cli_writeln(sprintf(
    'Programmes created %d, already present %d. Cohorts re-keyed %d. SIAKAD rows linked %d. '
    . 'Courses moved %d. Categories deleted %d. Skipped %d.',
    $result['prodicreated'],
    $result['prodilinked'],
    $result['cohortrekeyed'],
    $result['siakadlinked'],
    $result['coursesmoved'],
    $result['categoriesdeleted'],
    $result['skipped']
));

if (!$execute) {
    cli_writeln('');
    cli_writeln('DRY RUN finished. Nothing was written.');
}

exit(0);

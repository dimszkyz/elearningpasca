<?php
// This file is part of Moodle - http://moodle.org/

/**
 * English strings for local_pascaprodi.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Pasca Study Program automation';
$string['defaultnameprefix'] = 'Students - ';
$string['defaultarchiveprefix'] = '[ARCHIVED] ';
$string['cohortdescription'] = 'Automatically generated student cohort for Study Program category: {$a}.';
$string['cohortarchiveddescription'] = 'This cohort was archived automatically because the linked Study Program category was deleted. Old category ID: {$a->categoryid}. Old category name: {$a->categoryname}.';
$string['setting_enabled'] = 'Enable Study Program cohort automation';
$string['setting_enabled_desc'] = 'When enabled, Moodle automatically creates a cohort whenever a course category is created.';
$string['setting_nameprefix'] = 'Cohort name prefix';
$string['setting_nameprefix_desc'] = 'Prefix added to the category name to build the generated cohort name. Example: Students - Master of Management.';
$string['setting_updatenames'] = 'Follow category name changes';
$string['setting_updatenames_desc'] = 'When enabled, generated cohort names are updated when course category names change.';
$string['setting_archiveondeleted'] = 'Archive cohort when category is deleted';
$string['setting_archiveondeleted_desc'] = 'When enabled, generated cohorts are hidden and prefixed as archived when the linked category is deleted. Cohort members are not deleted.';
$string['setting_archiveprefix'] = 'Archive prefix';
$string['setting_archiveprefix_desc'] = 'Prefix added to the cohort name when the linked category is deleted.';
$string['privacy:metadata'] = 'The Pasca Study Program automation plugin does not store personal data. It only creates and updates Moodle cohorts based on course categories.';

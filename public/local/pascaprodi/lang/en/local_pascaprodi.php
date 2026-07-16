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
$string['defaultteachernameprefix'] = 'Teachers - ';
$string['defaultarchiveprefix'] = '[ARCHIVED] ';
$string['cohortdescription'] = 'Automatically generated student cohort for Study Program category: {$a}.';
$string['teachercohortdescription'] = 'Automatically generated teacher cohort for Study Program category: {$a}.';
$string['cohortarchiveddescription'] = 'This cohort was archived automatically because the linked Study Program category was deleted. Old category ID: {$a->categoryid}. Old category name: {$a->categoryname}.';
$string['cohortenrolname'] = 'Study Program automation: {$a}';
$string['setting_enabled'] = 'Enable Study Program cohort automation';
$string['setting_enabled_desc'] = 'When enabled, Moodle automatically creates a cohort whenever a course category is created.';
$string['setting_cohortheading'] = 'Generated cohorts';
$string['setting_cohortheading_desc'] = 'Configure student and teacher cohort names generated from Study Program categories.';
$string['setting_nameprefix'] = 'Student cohort name prefix';
$string['setting_nameprefix_desc'] = 'Prefix added to the category name to build the generated student cohort name. Example: Students - Master of Management.';
$string['setting_teachernameprefix'] = 'Teacher cohort name prefix';
$string['setting_teachernameprefix_desc'] = 'Prefix added to the category name to build the generated teacher cohort name. Example: Teachers - Master of Management.';
$string['setting_updatenames'] = 'Follow category name changes';
$string['setting_updatenames_desc'] = 'When enabled, generated cohort names are updated when course category names change.';
$string['setting_archiveondeleted'] = 'Archive cohort when category is deleted';
$string['setting_archiveondeleted_desc'] = 'When enabled, generated cohorts are hidden and prefixed as archived when the linked category is deleted. Cohort members are not deleted.';
$string['setting_archiveprefix'] = 'Archive prefix';
$string['setting_archiveprefix_desc'] = 'Prefix added to the cohort name when the linked category is deleted.';
$string['setting_enrolheading'] = 'Automatic Cohort sync when a Course is created';
$string['setting_enrolheading_desc'] = 'When a new Course is created inside a Study Program category, the plugin can automatically add Cohort sync enrolment methods for that category\'s student and teacher cohorts.';
$string['setting_autoenrolstudents'] = 'Automatically enrol student cohort as Student';
$string['setting_autoenrolstudents_desc'] = 'When enabled, a new Course automatically gets a Cohort sync enrolment from the Course category\'s student cohort using the Student role.';
$string['setting_autoenrolteachers'] = 'Automatically enrol teacher cohort as Teacher';
$string['setting_autoenrolteachers_desc'] = 'When enabled, a new Course automatically gets a Cohort sync enrolment from the Course category\'s teacher cohort using the Teacher role.';
$string['privacy:metadata'] = 'The Pasca Study Program automation plugin does not store personal data. It only creates and updates Moodle cohorts based on course categories and adds Cohort sync enrolment methods to courses.';

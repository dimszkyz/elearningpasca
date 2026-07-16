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
$string['teachercohortarchiveddescription'] = 'Old teacher cohorts from Study Program automation have been disabled. Use global roles or category roles for lecturers.';
$string['cohortenrolname'] = 'Study Program automation: {$a}';
$string['setting_enabled'] = 'Enable Study Program cohort automation';
$string['setting_enabled_desc'] = 'When enabled, Moodle automatically creates a student cohort whenever a course category is created.';
$string['setting_cohortheading'] = 'Generated student cohorts';
$string['setting_cohortheading_desc'] = 'Configure student cohort names generated from Study Program categories.';
$string['setting_nameprefix'] = 'Student cohort name prefix';
$string['setting_nameprefix_desc'] = 'Prefix added to the category name to build the generated student cohort name. Example: Students - Master of Management.';
$string['setting_updatenames'] = 'Follow category name changes';
$string['setting_updatenames_desc'] = 'When enabled, generated student cohort names are updated when course category names change.';
$string['setting_archiveondeleted'] = 'Archive cohort when category is deleted';
$string['setting_archiveondeleted_desc'] = 'When enabled, generated student cohorts are hidden and prefixed as archived when the linked category is deleted. Cohort members are not deleted.';
$string['setting_archiveprefix'] = 'Archive prefix';
$string['setting_archiveprefix_desc'] = 'Prefix added to the cohort name when the linked category is deleted.';
$string['setting_enrolheading'] = 'Automatic Cohort sync when a Course is created';
$string['setting_enrolheading_desc'] = 'When a new Course is created inside a Study Program category, the plugin can automatically add a Cohort sync enrolment method for that category\'s student cohort.';
$string['setting_autoenrolstudents'] = 'Automatically enrol student cohort as Student';
$string['setting_autoenrolstudents_desc'] = 'When enabled, a new Course automatically gets a Cohort sync enrolment from the Course category\'s student cohort using the Student role.';
$string['userrolepage'] = 'Assign User, Role, and Cohort';
$string['userrolepage_desc'] = 'Use this page so administrators can select a user, assign a system role such as globalteacher/Course creator, and add the user to a Study Program cohort without moving between menus.';
$string['field_user'] = 'User';
$string['field_systemrole'] = 'System role';
$string['field_systemrole_help'] = 'This role is assigned at System level. Use it for global roles such as globalteacher or Course creator. Choose No role change if you only want to add the user to a cohort.';
$string['field_cohort'] = 'Study Program cohort';
$string['field_cohort_help'] = 'Choose the Study Program student cohort for students. Lecturers can receive a system role without being added to a student cohort.';
$string['norolechange'] = 'No role change';
$string['nocohortchange'] = 'Do not add to cohort';
$string['roleassigned'] = 'System role assigned: {$a}';
$string['rolealreadyassigned'] = 'User already has system role: {$a}';
$string['cohortassigned'] = 'User added to cohort: {$a}';
$string['cohortalreadyassigned'] = 'User is already a member of cohort: {$a}';
$string['nochangesmade'] = 'No changes were selected.';
$string['userroleassigned'] = 'User setup processed for {$a}.';
$string['privacy:metadata'] = 'The Pasca Study Program automation plugin does not store personal data. It only creates and updates Moodle cohorts based on course categories and adds student Cohort sync enrolment methods to courses.';

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
$string['setting_apisyncheading'] = 'Study Program category sync from UNW API';
$string['setting_apisyncheading_desc'] = 'Configure the remote data source used to create root Study Program categories from UNW Program Studi API.';
$string['setting_syncapiurl'] = 'Program Studi API URL';
$string['setting_syncapiurl_desc'] = 'API containing Study Program data. Only records with jenjang Magister are created as root Moodle categories.';
$string['setting_syncapitimeout'] = 'API timeout';
$string['setting_syncapitimeout_desc'] = 'API connection timeout in seconds.';
$string['adduserprodiheading'] = 'Access role and Study Program';
$string['adduserprodiheading_desc'] = 'This section is required when creating a new user. Choose an access role and Study Program/category. Students are added to the Study Program cohort. Teachers/globalteacher/Course creator/Manager are assigned at the selected category context.';
$string['field_user'] = 'User';
$string['field_accessrole'] = 'Access role';
$string['field_accessrole_help'] = 'You must choose an existing Moodle role. Student roles work together with the Study Program cohort. Teacher/globalteacher/Course creator/Manager roles are assigned at the selected Study Program category, so they can be registered for multiple Study Programs.';
$string['field_systemrole'] = 'System role';
$string['field_systemrole_help'] = 'Legacy field. Use Access role.';
$string['field_categories'] = 'Study Program / Category';
$string['field_categories_help'] = 'You must choose a Study Program category. For students, choose one Study Program so the user is added to that student cohort. For teachers/globalteacher/Course creator/Manager, choose one or more Study Programs and the role will be assigned at those categories.';
$string['field_cohort'] = 'Study Program cohort';
$string['field_cohort_help'] = 'Legacy field. Use Study Program / Category.';
$string['chooseaccessrole'] = 'Choose access role';
$string['norolechange'] = 'No role change';
$string['nocohortchange'] = 'Do not add to cohort';
$string['roleassigned'] = 'System role assigned: {$a}';
$string['rolealreadyassigned'] = 'User already has system role: {$a}';
$string['roleassignedcategory'] = 'Role {$a->role} assigned to Study Program/Category: {$a->category}';
$string['rolealreadyassignedcategory'] = 'User already has role {$a->role} in Study Program/Category: {$a->category}';
$string['cohortassigned'] = 'User added to cohort: {$a}';
$string['cohortalreadyassigned'] = 'User is already a member of cohort: {$a}';
$string['cohortassignedcategory'] = 'User added to cohort {$a->cohort} for Study Program/Category: {$a->category}';
$string['cohortalreadyassignedcategory'] = 'User is already a member of cohort {$a->cohort} for Study Program/Category: {$a->category}';
$string['cohortnotcreated'] = 'Automatic cohort for Study Program/Category {$a} could not be created.';
$string['teachercohortskipped'] = 'Teacher/globalteacher role was assigned at the Study Program category. The user was not added to the student cohort to avoid enrolling them as Student.';
$string['synccategoriesbutton'] = 'Sync categories';
$string['synccategoriespage'] = 'Sync Study Program Categories';
$string['synccategoriespage_desc'] = 'This sync fetches data from {$a->url}, only uses jenjang Magister, creates category names from jenjang + space + nama, uses slug as category ID number, and creates categories at root level.';
$string['synccategoriessuccess'] = 'Sync completed. Created: {$a->created}. Updated: {$a->updated}. Unchanged: {$a->unchanged}. Skipped: {$a->skipped}. Failed: {$a->failed}. New cohorts: {$a->cohortcreated}. Updated cohorts: {$a->cohortupdated}.';
$string['synccategoriesfailed'] = 'Sync failed: {$a}';
$string['apisyncfailed'] = 'Unable to fetch API data: {$a}';
$string['apisyncinvalidjson'] = 'API response is not valid JSON: {$a}';
$string['apisyncinvaliddata'] = 'API response does not contain a valid data structure.';
$string['categoryid'] = 'Category ID';
$string['cohortid'] = 'Cohort ID';
$string['syncmessage'] = 'Message';
$string['error_select_role_or_category'] = 'Select at least an access role or a Study Program/Category.';
$string['error_accessrole_required'] = 'Access role is required.';
$string['error_categories_required'] = 'Study Program/Category is required.';
$string['error_multiple_categories_teacher_role'] = 'More than one Study Program is only allowed for teacher/globalteacher/Course creator/Manager roles. Students should use one Study Program.';
$string['nochangesmade'] = 'No changes were selected.';
$string['userroleassigned'] = 'User setup processed for {$a}.';
$string['privacy:metadata'] = 'The Pasca Study Program automation plugin does not store personal data. It only creates and updates Moodle cohorts based on course categories and adds student Cohort sync enrolment methods to courses.';

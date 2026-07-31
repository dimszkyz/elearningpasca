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
$string['cohortdescription'] = 'Automatically generated student cohort for Study Program: {$a}.';
$string['cohortarchiveddescription'] = 'This cohort was archived automatically because the linked Study Program is no longer active. Study Program ID: {$a->prodiid}. Study Program name: {$a->prodiname}.';
$string['teachercohortarchiveddescription'] = 'Old teacher cohorts from Study Program automation have been disabled. Use global roles for lecturers.';
$string['cohortenrolname'] = 'Study Program automation: {$a}';
$string['setting_enabled'] = 'Enable Study Program cohort automation';
$string['setting_enabled_desc'] = 'When enabled, each Study Program owns a generated student cohort and courses can be linked to Study Programs from the course settings form.';
$string['setting_cohortheading'] = 'Generated student cohorts';
$string['setting_cohortheading_desc'] = 'Configure student cohort names generated from Study Programs.';
$string['setting_nameprefix'] = 'Student cohort name prefix';
$string['setting_nameprefix_desc'] = 'Prefix added to the Study Program name to build the generated student cohort name. Example: Students - Master of Management.';
$string['setting_updatenames'] = 'Follow Study Program name changes';
$string['setting_updatenames_desc'] = 'When enabled, generated student cohort names are updated when a sync changes the Study Program name.';
$string['setting_archiveondeleted'] = 'Archive cohort when a Study Program is dropped';
$string['setting_archiveondeleted_desc'] = 'When enabled, the generated student cohort is hidden and prefixed as archived once its Study Program disappears from the API. Cohort members are not deleted.';
$string['setting_archiveprefix'] = 'Archive prefix';
$string['setting_archiveprefix_desc'] = 'Prefix added to the cohort name when its Study Program is archived.';
$string['setting_apisyncheading'] = 'Study Program sync from UNW API';
$string['setting_apisyncheading_desc'] = 'Configure the remote data source used to populate the Study Program table from the UNW Program Studi API.';
$string['setting_syncapiurl'] = 'Program Studi API URL';
$string['setting_syncapiurl_desc'] = 'API containing Study Program data. Every record returned by the API is stored as a Study Program, regardless of jenjang.';
$string['setting_syncapitimeout'] = 'API timeout';
$string['setting_syncapitimeout_desc'] = 'API connection timeout in seconds.';
$string['adduserprodiheading'] = 'Access role and Study Program';
$string['adduserprodiheading_desc'] = 'This section is required when creating a new user. Choose an access role and one or more Study Programs. The role is assigned at system level, and the user is added to the student cohort of every selected Study Program.';
$string['field_user'] = 'User';
$string['field_accessrole'] = 'Access role';
$string['field_accessrole_help'] = 'You must choose an existing Moodle role. Roles other than Student are assigned at system level. Every user is added to the student cohort of each selected Study Program, which is what grants access to that programme\'s courses.';
$string['field_prodi'] = 'Study Program';
$string['field_prodi_help'] = 'Choose one or more Study Programs. The user is added to the generated student cohort of every selected Study Program, so they are automatically enrolled into all courses linked to those programmes.';
$string['chooseaccessrole'] = 'Choose access role';
$string['norolechange'] = 'No role change';
$string['nocohortchange'] = 'Do not add to cohort';
$string['roleassigned'] = 'System role assigned: {$a}';
$string['rolealreadyassigned'] = 'User already has system role: {$a}';
$string['cohortassigned'] = 'User added to cohort: {$a}';
$string['cohortalreadyassigned'] = 'User is already a member of cohort: {$a}';
$string['cohortnotcreated'] = 'Automatic cohort for Study Program {$a} could not be created.';
$string['prodi'] = 'Study Program';
$string['prodi_help'] = 'Select the Study Programs this course belongs to. Each selected Study Program gets a Cohort sync enrolment method, so its students are enrolled automatically. Clearing a selection removes that enrolment method again. Leave it empty for a course that is not tied to any Study Program. The Course category above only controls where the course sits in the site structure.';
$string['prodicode'] = 'Code';
$string['prodiid'] = 'Study Program ID';
$string['cohortid'] = 'Cohort ID';
$string['syncmessage'] = 'Message';
$string['syncprodibutton'] = 'Sync Study Programs';
$string['syncprodipage'] = 'Sync All Study Programs';
$string['syncprodipage_desc'] = 'This sync fetches data from {$a->url}, stores every record as a Study Program using jenjang + space + nama as the name and slug as the code, and creates one student cohort per Study Program. Course categories are not touched.';
$string['syncprodisuccess'] = 'Sync completed. Created: {$a->created}. Updated: {$a->updated}. Unchanged: {$a->unchanged}. Skipped: {$a->skipped}. Failed: {$a->failed}. Deactivated: {$a->deactivated}. New cohorts: {$a->cohortcreated}. Updated cohorts: {$a->cohortupdated}.';
$string['syncprodifailed'] = 'Sync failed: {$a}';
$string['apisyncfailed'] = 'Unable to fetch API data: {$a}';
$string['apisyncinvalidjson'] = 'API response is not valid JSON: {$a}';
$string['apisyncinvaliddata'] = 'API response does not contain a valid data structure.';
$string['error_accessrole_required'] = 'Access role is required.';
$string['error_prodi_required'] = 'Study Program is required.';
$string['nochangesmade'] = 'No changes were selected.';
$string['userroleassigned'] = 'User setup processed for {$a}.';
$string['privacy:metadata'] = 'The Pasca Study Program automation plugin does not store personal data. It only stores Study Programs, creates Moodle cohorts for them, and adds student Cohort sync enrolment methods to courses.';

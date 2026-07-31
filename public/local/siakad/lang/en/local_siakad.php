<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Dummy SIAKAD integration';
$string['notlinkedstudent'] = 'Your Moodle account is not linked to a SIAKAD student record.';
$string['wrongprogramme'] = 'This exam is not assigned to your study programme.';
$string['unpaidbill'] = 'This exam is unavailable because the bill for period {$a} is not fully paid.';
$string['task_syncprodi'] = 'Synchronise study programmes from local_pascaprodi';
$string['dbheading'] = 'SIAKAD database connection';
$string['dbheading_desc'] = 'The SIAKAD master data (identities, programmes, students, lecturers, bills) lives in its own database. Leave the type empty to keep those tables in the Moodle database. After filling this in, run <code>php local/siakad/cli/setup_external_db.php --install</code> to create the schema, then <code>--migrate</code> to copy existing rows across.';
$string['dbtype'] = 'Database type';
$string['dbtype_desc'] = 'Driver used for the SIAKAD database.';
$string['dbtype_none'] = 'Use the Moodle database';
$string['dbhost'] = 'Host';
$string['dbhost_desc'] = 'Server hosting the SIAKAD database.';
$string['dbname'] = 'Database name';
$string['dbname_desc'] = 'Name of the SIAKAD database, for example siakad_dummy.';
$string['dbuser'] = 'User';
$string['dbuser_desc'] = 'Database user with read and write access.';
$string['dbpass'] = 'Password';
$string['dbpass_desc'] = 'Password for that user.';
$string['dbprefix'] = 'Table prefix';
$string['dbprefix_desc'] = 'Prefix for the SIAKAD tables. Usually empty, so tables are named local_siakad_mahasiswa and so on.';
$string['dbport'] = 'Port';
$string['dbport_desc'] = 'Leave empty for the driver default.';
$string['dbsocket'] = 'Socket';
$string['dbsocket_desc'] = 'Unix socket path, when the server is not reached over TCP.';

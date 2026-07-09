<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = '127.0.0.1';
$CFG->dbname    = 'elearningpasca_new';
$CFG->dbuser    = 'elearningpasca_user';
$CFG->dbpass    = 'password';
$CFG->prefix    = 'mdl_';

$CFG->dboptions = array (
    'dbpersist' => 0,
    'dbport' => 3306,
    'dbsocket' => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
);

// Pakai 127.0.0.1, jangan localhost dulu.
$CFG->wwwroot   = 'http://127.0.0.1:8080';

// Pakai moodledata baru, jangan campur dengan install lama.
$CFG->dataroot  = 'D:\\xamppnew\\moodledata-pasca-new';

$CFG->admin     = 'admin';
$CFG->directorypermissions = 0777;

// Fix session supaya tidak redirect loop.
$CFG->session_handler_class = '\core\session\file';
$CFG->session_file_save_path = $CFG->dataroot . '/sessions';

$CFG->sessioncookie = 'MoodlePasca1278080';
$CFG->cookiesecure = false;
$CFG->cookiehttponly = true;

@error_reporting(0);
@ini_set('display_errors', '0');
@ini_set('display_startup_errors', '0');

$CFG->debug = 0;
$CFG->debugdisplay = 0;
$CFG->debugdeveloper = false;

// Karena config.php ada di root project dan Moodle ada di folder public.
require_once(__DIR__ . '/public/lib/setup.php');
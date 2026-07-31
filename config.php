<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = '127.0.0.1';
$CFG->dbname    = 'elearningpasca_new';
$CFG->dbuser    = 'root'; // Diubah: Default user Laragon
$CFG->dbpass    = '';     // Diubah: Default password Laragon (kosong)
$CFG->prefix    = 'mdl_';

$CFG->dboptions = array (
    'dbpersist' => 0,
    'dbport' => 3306,
    'dbsocket' => '',
    'dbcollation' => 'utf8mb4_unicode_ci',
);

// Diubah: Menggunakan virtual host otomatis Laragon
// Asumsi folder project Anda bernama "elearningpasca" di D:\laragon\www\
$CFG->wwwroot   = 'http://elearningpasca.test';

// Diubah: Arahkan ke folder data khusus di dalam Laragon
$CFG->dataroot  = 'D:\\laragon\\data\\moodledata-pasca-new';

$CFG->admin     = 'admin';
$CFG->directorypermissions = 0777;

// Fix session supaya tidak redirect loop.
$CFG->session_handler_class = '\core\session\file';
$CFG->session_file_save_path = $CFG->dataroot . '/sessions';

// Diubah: Sesuaikan nama session cookie agar tidak bentrok
$CFG->sessioncookie = 'MoodlePascaLaragon';
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
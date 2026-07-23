<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'reset' => false,
    'create-moodle-users' => false,
    'password' => 'Dummy#2026!',
], [
    'h' => 'help',
    'r' => 'reset',
    'u' => 'create-moodle-users',
    'p' => 'password',
]);
if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    cli_writeln(<<<'HELP'
Seed local dummy SIAKAD data for exam-access testing.

Options:
--create-moodle-users, -u  Create missing Moodle accounts for all dummy users.
--password, -p              Password for newly created Moodle accounts.
--reset, -r                 Clear bridge data before seeding. This does not delete Moodle accounts.
--help, -h                  Show this help.

Recommended local test command:
php public/local/siakadbridge/cli/seed_dummy.php --create-moodle-users

Use --reset only when existing bridge data may be safely deleted.
HELP
    );
    exit(0);
}

if ($options['reset']) {
    foreach (['local_siakad_tagihan', 'local_siakad_dosen', 'local_siakad_mahasiswa', 'local_siakad_prodi', 'local_siakad_user'] as $table) {
        $DB->delete_records($table);
    }
    cli_writeln('Existing bridge data cleared. Moodle user accounts were not deleted.');
}

$payload = json_decode(<<<'JSON'
{
  "prodi": [
    {"id":"prodi-ti","kode":"TI","nama":"Teknologi Informasi","aktif":true},
    {"id":"prodi-si","kode":"SI","nama":"Sistem Informasi","aktif":true},
    {"id":"prodi-mnj","kode":"MNJ","nama":"Manajemen","aktif":true}
  ],
  "users": [
    {"id":"user-mhs001","username":"mhs001","fullname":"Mahasiswa Lunas TI","email":"mhs001@example.test","role":"mahasiswa"},
    {"id":"user-mhs002","username":"mhs002","fullname":"Mahasiswa Belum Lunas TI","email":"mhs002@example.test","role":"mahasiswa"},
    {"id":"user-mhs003","username":"mhs003","fullname":"Mahasiswa Lunas SI","email":"mhs003@example.test","role":"mahasiswa"},
    {"id":"user-mhs004","username":"mhs004","fullname":"Mahasiswa Tagihan Ganda TI","email":"mhs004@example.test","role":"mahasiswa"},
    {"id":"user-mhs005","username":"mhs005","fullname":"Mahasiswa Tagihan Opsional TI","email":"mhs005@example.test","role":"mahasiswa"},
    {"id":"user-mhs006","username":"mhs006","fullname":"Mahasiswa Cuti TI","email":"mhs006@example.test","role":"mahasiswa"},
    {"id":"user-mhs007","username":"mhs007","fullname":"Mahasiswa Periode Berbeda TI","email":"mhs007@example.test","role":"mahasiswa"},
    {"id":"user-mhs008","username":"mhs008","fullname":"Mahasiswa Tagihan Dibatalkan TI","email":"mhs008@example.test","role":"mahasiswa"},
    {"id":"user-mhs009","username":"mhs009","fullname":"Mahasiswa Lunas Manajemen","email":"mhs009@example.test","role":"mahasiswa"},
    {"id":"user-mhs010","username":"mhs010","fullname":"Mahasiswa Tanpa Tagihan TI","email":"mhs010@example.test","role":"mahasiswa"},
    {"id":"user-dsn001","username":"dsn001","fullname":"Dosen Teknologi Informasi","email":"dsn001@example.test","role":"dosen"}
  ],
  "mahasiswa": [
    {"id":"student-240001","username":"mhs001","nim":"240001","nama":"Mahasiswa Lunas TI","email":"mhs001@example.test","prodi":"TI","status":"aktif"},
    {"id":"student-240002","username":"mhs002","nim":"240002","nama":"Mahasiswa Belum Lunas TI","email":"mhs002@example.test","prodi":"TI","status":"aktif"},
    {"id":"student-240003","username":"mhs003","nim":"240003","nama":"Mahasiswa Lunas SI","email":"mhs003@example.test","prodi":"SI","status":"aktif"},
    {"id":"student-240004","username":"mhs004","nim":"240004","nama":"Mahasiswa Tagihan Ganda TI","email":"mhs004@example.test","prodi":"TI","status":"aktif"},
    {"id":"student-240005","username":"mhs005","nim":"240005","nama":"Mahasiswa Tagihan Opsional TI","email":"mhs005@example.test","prodi":"TI","status":"aktif"},
    {"id":"student-240006","username":"mhs006","nim":"240006","nama":"Mahasiswa Cuti TI","email":"mhs006@example.test","prodi":"TI","status":"cuti"},
    {"id":"student-240007","username":"mhs007","nim":"240007","nama":"Mahasiswa Periode Berbeda TI","email":"mhs007@example.test","prodi":"TI","status":"aktif"},
    {"id":"student-240008","username":"mhs008","nim":"240008","nama":"Mahasiswa Tagihan Dibatalkan TI","email":"mhs008@example.test","prodi":"TI","status":"aktif"},
    {"id":"student-240009","username":"mhs009","nim":"240009","nama":"Mahasiswa Lunas Manajemen","email":"mhs009@example.test","prodi":"MNJ","status":"aktif"},
    {"id":"student-240010","username":"mhs010","nim":"240010","nama":"Mahasiswa Tanpa Tagihan TI","email":"mhs010@example.test","prodi":"TI","status":"aktif"}
  ],
  "dosen": [
    {"id":"lecturer-001001","username":"dsn001","nidn":"001001","nama":"Dosen Teknologi Informasi","email":"dsn001@example.test","prodi":"TI","status":"aktif"}
  ],
  "tagihan": [
    {"id":"bill-240001-ukt","nim":"240001","kodetagihan":"UKT-2026-GENAP-240001","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"lunas","wajib":true},
    {"id":"bill-240002-ukt","nim":"240002","kodetagihan":"UKT-2026-GENAP-240002","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"belum_lunas","wajib":true},
    {"id":"bill-240003-ukt","nim":"240003","kodetagihan":"UKT-2026-GENAP-240003","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"lunas","wajib":true},
    {"id":"bill-240004-ukt","nim":"240004","kodetagihan":"UKT-2026-GENAP-240004","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"lunas","wajib":true},
    {"id":"bill-240004-praktikum","nim":"240004","kodetagihan":"PRAKTIKUM-2026-GENAP-240004","tahunajaran":"2026/2027","semester":"genap","jenis":"PRAKTIKUM","nominal":500000,"status":"belum_lunas","wajib":true},
    {"id":"bill-240005-ukt","nim":"240005","kodetagihan":"UKT-2026-GENAP-240005","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"lunas","wajib":true},
    {"id":"bill-240005-alumni","nim":"240005","kodetagihan":"ALUMNI-2026-GENAP-240005","tahunajaran":"2026/2027","semester":"genap","jenis":"ALUMNI","nominal":100000,"status":"belum_lunas","wajib":false},
    {"id":"bill-240006-ukt","nim":"240006","kodetagihan":"UKT-2026-GENAP-240006","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"lunas","wajib":true},
    {"id":"bill-240007-ukt","nim":"240007","kodetagihan":"UKT-2026-GANJIL-240007","tahunajaran":"2026/2027","semester":"ganjil","jenis":"UKT","nominal":2500000,"status":"lunas","wajib":true},
    {"id":"bill-240008-ukt","nim":"240008","kodetagihan":"UKT-2026-GENAP-240008","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"dibatalkan","wajib":true},
    {"id":"bill-240009-ukt","nim":"240009","kodetagihan":"UKT-2026-GENAP-240009","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"lunas","wajib":true}
  ]
}
JSON
);

if (!is_object($payload)) {
    cli_error('Unable to decode the embedded dummy payload.');
}

if ($options['create-moodle-users']) {
    require_once($CFG->dirroot . '/user/lib.php');
    $created = 0;
    $existing = 0;
    foreach ($payload->users as $dummyuser) {
        $moodleuser = $DB->get_record('user', [
            'username' => $dummyuser->username,
            'mnethostid' => $CFG->mnet_localhost_id,
            'deleted' => 0,
        ], 'id,username', IGNORE_MISSING);
        if ($moodleuser) {
            $existing++;
            continue;
        }

        $nameparts = preg_split('/\s+/', trim($dummyuser->fullname), 2);
        $user = (object) [
            'auth' => 'manual',
            'confirmed' => 1,
            'mnethostid' => $CFG->mnet_localhost_id,
            'username' => $dummyuser->username,
            'password' => (string) $options['password'],
            'firstname' => $nameparts[0] ?? 'Dummy',
            'lastname' => $nameparts[1] ?? 'SIAKAD',
            'email' => $dummyuser->email,
            'idnumber' => 'dummy-siakad:' . $dummyuser->id,
            'city' => 'Medan',
            'country' => 'ID',
        ];
        user_create_user($user, true, false);
        $created++;
    }
    cli_writeln(sprintf('Moodle dummy accounts: %d created, %d already existed.', $created, $existing));
    if ($created > 0) {
        cli_writeln('Password for newly created accounts: ' . (string) $options['password']);
    }
}

$result = \local_siakadbridge\sync\service::import_payload($payload, 'dummy');
set_config('currentyear', '2026/2027', 'local_siakadbridge');
set_config('currentsemester', 'genap', 'local_siakadbridge');
set_config('gatemode', 'all_required_lunas', 'local_siakadbridge');
$reconciled = \local_siakadbridge\manager::reconcile_moodle_access();

cli_writeln(sprintf('Dummy SIAKAD data seeded: %d inserted, %d updated.', $result->inserted, $result->updated));
cli_writeln(sprintf(
    'Moodle reconciliation: %d identities linked, %d student cohort memberships added, %d lecturer roles assigned.',
    $reconciled->linked,
    $reconciled->students,
    $reconciled->lecturers
));
cli_writeln('Run public/local/siakadbridge/cli/verify_dummy.php to verify all exam-access scenarios.');

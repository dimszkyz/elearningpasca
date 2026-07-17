<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'reset' => false,
], [
    'h' => 'help',
    'r' => 'reset',
]);
if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    cli_writeln("Seed local dummy SIAKAD data.\n\n--reset, -r  Clear bridge data before seeding.\n");
    exit(0);
}

if ($options['reset']) {
    foreach (['local_siakad_tagihan', 'local_siakad_dosen', 'local_siakad_mahasiswa', 'local_siakad_prodi', 'local_siakad_user'] as $table) {
        $DB->delete_records($table);
    }
    cli_writeln('Existing bridge data cleared.');
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
    {"id":"user-dsn001","username":"dsn001","fullname":"Dosen TI","email":"dsn001@example.test","role":"dosen"}
  ],
  "mahasiswa": [
    {"id":"student-240001","username":"mhs001","nim":"240001","nama":"Mahasiswa Lunas TI","email":"mhs001@example.test","prodi":"TI","status":"aktif"},
    {"id":"student-240002","username":"mhs002","nim":"240002","nama":"Mahasiswa Belum Lunas TI","email":"mhs002@example.test","prodi":"TI","status":"aktif"},
    {"id":"student-240003","username":"mhs003","nim":"240003","nama":"Mahasiswa Lunas SI","email":"mhs003@example.test","prodi":"SI","status":"aktif"}
  ],
  "dosen": [
    {"id":"lecturer-001001","username":"dsn001","nidn":"001001","nama":"Dosen TI","email":"dsn001@example.test","prodi":"TI","status":"aktif"}
  ],
  "tagihan": [
    {"id":"bill-240001","nim":"240001","kodetagihan":"UKT-2026-GENAP-240001","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"lunas","wajib":true},
    {"id":"bill-240002","nim":"240002","kodetagihan":"UKT-2026-GENAP-240002","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"belum_lunas","wajib":true},
    {"id":"bill-240003","nim":"240003","kodetagihan":"UKT-2026-GENAP-240003","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"lunas","wajib":true}
  ]
}
JSON
);

$result = \local_siakadbridge\sync\service::import_payload($payload, 'dummy');
set_config('currentyear', '2026/2027', 'local_siakadbridge');
set_config('currentsemester', 'genap', 'local_siakadbridge');
set_config('gatemode', 'all_required_lunas', 'local_siakadbridge');
cli_writeln(sprintf('Dummy data seeded: %d inserted, %d updated.', $result->inserted, $result->updated));
cli_writeln('Create Moodle users mhs001, mhs002, mhs003 and dsn001, then run cli/reconcile.php.');

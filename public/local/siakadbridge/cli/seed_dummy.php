<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Seeds dummy SIAKAD data for local integration testing.
 *
 * Run from the Moodle project root, for example on Laragon:
 * D:\laragon\bin\php\php-8.4.23-Win32-vs17-x64\php.exe public\local\siakadbridge\cli\seed_dummy.php --reset
 *
 * @package    local_siakadbridge
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
    $unrecognised = implode("\n  ", $unrecognised);
    cli_error("Unknown options:\n  " . $unrecognised);
}

if ($options['help']) {
    $help = "Seed dummy SIAKAD data.\n\n" .
        "Options:\n" .
        "  --reset, -r    Clear local_siakad_* tables before seeding.\n" .
        "  --help, -h     Show this help.\n\n" .
        "Example:\n" .
        "  php public/local/siakadbridge/cli/seed_dummy.php --reset\n";
    echo $help;
    exit(0);
}

$tables = [
    'local_siakad_tagihan',
    'local_siakad_dosen',
    'local_siakad_mahasiswa',
    'local_siakad_prodi',
    'local_siakad_user',
];

if ($options['reset']) {
    foreach ($tables as $table) {
        $DB->delete_records($table);
    }
    cli_writeln('Existing dummy SIAKAD data cleared.');
}

$now = time();

$tiid = local_siakadbridge_upsert('local_siakad_prodi', ['kode' => 'TI'], [
    'kode' => 'TI',
    'nama' => 'Teknologi Informasi',
    'aktif' => 1,
    'timemodified' => $now,
]);

$siid = local_siakadbridge_upsert('local_siakad_prodi', ['kode' => 'SI'], [
    'kode' => 'SI',
    'nama' => 'Sistem Informasi',
    'aktif' => 1,
    'timemodified' => $now,
]);

$mnjid = local_siakadbridge_upsert('local_siakad_prodi', ['kode' => 'MNJ'], [
    'kode' => 'MNJ',
    'nama' => 'Manajemen',
    'aktif' => 1,
    'timemodified' => $now,
]);

$mhs001userid = local_siakadbridge_upsert('local_siakad_user', ['username' => 'mhs001'], [
    'username' => 'mhs001',
    'fullname' => 'Mahasiswa Lunas TI',
    'email' => 'mhs001@example.test',
    'role' => 'mahasiswa',
    'moodleuserid' => 0,
    'timemodified' => $now,
]);

$mhs002userid = local_siakadbridge_upsert('local_siakad_user', ['username' => 'mhs002'], [
    'username' => 'mhs002',
    'fullname' => 'Mahasiswa Belum Lunas TI',
    'email' => 'mhs002@example.test',
    'role' => 'mahasiswa',
    'moodleuserid' => 0,
    'timemodified' => $now,
]);

$mhs003userid = local_siakadbridge_upsert('local_siakad_user', ['username' => 'mhs003'], [
    'username' => 'mhs003',
    'fullname' => 'Mahasiswa Lunas SI',
    'email' => 'mhs003@example.test',
    'role' => 'mahasiswa',
    'moodleuserid' => 0,
    'timemodified' => $now,
]);

$dsn001userid = local_siakadbridge_upsert('local_siakad_user', ['username' => 'dsn001'], [
    'username' => 'dsn001',
    'fullname' => 'Dosen TI',
    'email' => 'dsn001@example.test',
    'role' => 'dosen',
    'moodleuserid' => 0,
    'timemodified' => $now,
]);

$mhs001id = local_siakadbridge_upsert('local_siakad_mahasiswa', ['nim' => '240001'], [
    'userid' => $mhs001userid,
    'moodleuserid' => 0,
    'nim' => '240001',
    'nama' => 'Mahasiswa Lunas TI',
    'email' => 'mhs001@example.test',
    'prodiid' => $tiid,
    'status' => 'aktif',
    'timemodified' => $now,
]);

$mhs002id = local_siakadbridge_upsert('local_siakad_mahasiswa', ['nim' => '240002'], [
    'userid' => $mhs002userid,
    'moodleuserid' => 0,
    'nim' => '240002',
    'nama' => 'Mahasiswa Belum Lunas TI',
    'email' => 'mhs002@example.test',
    'prodiid' => $tiid,
    'status' => 'aktif',
    'timemodified' => $now,
]);

$mhs003id = local_siakadbridge_upsert('local_siakad_mahasiswa', ['nim' => '240003'], [
    'userid' => $mhs003userid,
    'moodleuserid' => 0,
    'nim' => '240003',
    'nama' => 'Mahasiswa Lunas SI',
    'email' => 'mhs003@example.test',
    'prodiid' => $siid,
    'status' => 'aktif',
    'timemodified' => $now,
]);

local_siakadbridge_upsert('local_siakad_dosen', ['nidn' => '001001'], [
    'userid' => $dsn001userid,
    'moodleuserid' => 0,
    'nidn' => '001001',
    'nama' => 'Dosen TI',
    'email' => 'dsn001@example.test',
    'prodiid' => $tiid,
    'status' => 'aktif',
    'timemodified' => $now,
]);

local_siakadbridge_upsert('local_siakad_tagihan', ['kodetagihan' => 'UKT-2026-GENAP-240001'], [
    'mahasiswaid' => $mhs001id,
    'kodetagihan' => 'UKT-2026-GENAP-240001',
    'tahunajaran' => '2026/2027',
    'semester' => 'genap',
    'nominal' => 2500000,
    'status' => 'lunas',
    'paidat' => $now,
    'timemodified' => $now,
]);

local_siakadbridge_upsert('local_siakad_tagihan', ['kodetagihan' => 'UKT-2026-GENAP-240002'], [
    'mahasiswaid' => $mhs002id,
    'kodetagihan' => 'UKT-2026-GENAP-240002',
    'tahunajaran' => '2026/2027',
    'semester' => 'genap',
    'nominal' => 2500000,
    'status' => 'belum_lunas',
    'paidat' => 0,
    'timemodified' => $now,
]);

local_siakadbridge_upsert('local_siakad_tagihan', ['kodetagihan' => 'UKT-2026-GENAP-240003'], [
    'mahasiswaid' => $mhs003id,
    'kodetagihan' => 'UKT-2026-GENAP-240003',
    'tahunajaran' => '2026/2027',
    'semester' => 'genap',
    'nominal' => 2500000,
    'status' => 'lunas',
    'paidat' => $now,
    'timemodified' => $now,
]);

cli_writeln('Dummy SIAKAD data seeded.');
cli_writeln('Create Moodle users with usernames mhs001, mhs002, and mhs003 to test the availability rule.');

/**
 * Inserts or updates a record by unique fields.
 *
 * @param string $table Moodle table name without prefix.
 * @param array $unique Unique field criteria.
 * @param array $data Record data.
 * @return int Record id.
 */
function local_siakadbridge_upsert(string $table, array $unique, array $data): int {
    global $DB;

    $existing = $DB->get_record($table, $unique, 'id', IGNORE_MISSING);
    $record = (object) $data;

    if ($existing) {
        $record->id = $existing->id;
        $DB->update_record($table, $record);
        return (int) $existing->id;
    }

    return (int) $DB->insert_record($table, $record);
}

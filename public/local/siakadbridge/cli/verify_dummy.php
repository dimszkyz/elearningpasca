<?php
// This file is part of Moodle - http://moodle.org/

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

[$options, $unrecognised] = cli_get_params([
    'help' => false,
    'json' => false,
], [
    'h' => 'help',
    'j' => 'json',
]);
if ($unrecognised) {
    cli_error('Unknown options: ' . implode(', ', $unrecognised));
}
if ($options['help']) {
    cli_writeln(<<<'HELP'
Verify dummy SIAKAD exam-access scenarios.

Options:
--json, -j  Print machine-readable JSON.
--help, -h  Show this help.

Seed data first with:
php public/local/siakadbridge/cli/seed_dummy.php --create-moodle-users
HELP
    );
    exit(0);
}

$reasonlabels = [
    'all_required_bills_paid' => 'seluruh tagihan wajib sudah lunas',
    'student_not_found' => 'mahasiswa aktif tidak ditemukan atau akun belum terhubung',
    'wrong_study_program' => 'program studi mahasiswa berbeda',
    'bill_not_found' => 'tagihan pada periode yang dipilih tidak ditemukan',
    'required_bill_not_found' => 'tidak ada tagihan wajib yang harus dibayar',
    'required_bill_unpaid' => 'masih ada tagihan wajib yang belum lunas',
    'paid_bill_found' => 'ditemukan tagihan yang sudah lunas',
    'no_paid_bill' => 'tidak ada tagihan yang sudah lunas',
    'moodle_user_not_found' => 'akun Moodle dummy belum dibuat',
];

$scenarios = [
    [
        'name' => 'TI aktif dan lunas',
        'username' => 'mhs001',
        'prodi' => 'TI',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => true,
        'expectedreason' => 'all_required_bills_paid',
    ],
    [
        'name' => 'TI belum lunas',
        'username' => 'mhs002',
        'prodi' => 'TI',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'required_bill_unpaid',
    ],
    [
        'name' => 'SI mencoba ujian TI',
        'username' => 'mhs003',
        'prodi' => 'TI',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'wrong_study_program',
    ],
    [
        'name' => 'SI mengikuti ujian SI',
        'username' => 'mhs003',
        'prodi' => 'SI',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => true,
        'expectedreason' => 'all_required_bills_paid',
    ],
    [
        'name' => 'Satu dari dua tagihan wajib belum lunas',
        'username' => 'mhs004',
        'prodi' => 'TI',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => '',
        'expectedallowed' => false,
        'expectedreason' => 'required_bill_unpaid',
    ],
    [
        'name' => 'Tagihan opsional belum lunas',
        'username' => 'mhs005',
        'prodi' => 'TI',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => '',
        'expectedallowed' => true,
        'expectedreason' => 'all_required_bills_paid',
    ],
    [
        'name' => 'Mahasiswa berstatus cuti',
        'username' => 'mhs006',
        'prodi' => 'TI',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'student_not_found',
    ],
    [
        'name' => 'Tagihan hanya tersedia pada semester ganjil',
        'username' => 'mhs007',
        'prodi' => 'TI',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'bill_not_found',
    ],
    [
        'name' => 'Seluruh tagihan wajib dibatalkan',
        'username' => 'mhs008',
        'prodi' => 'TI',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'required_bill_not_found',
    ],
    [
        'name' => 'Mahasiswa Manajemen mengikuti ujian Manajemen',
        'username' => 'mhs009',
        'prodi' => 'MNJ',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => true,
        'expectedreason' => 'all_required_bills_paid',
    ],
    [
        'name' => 'Mahasiswa aktif tanpa tagihan',
        'username' => 'mhs010',
        'prodi' => 'TI',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'bill_not_found',
    ],
    [
        'name' => 'Tahun ajaran berbeda',
        'username' => 'mhs001',
        'prodi' => 'TI',
        'tahunajaran' => '2025/2026',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'bill_not_found',
    ],
];

$results = [];
$passed = 0;
$failed = 0;
foreach ($scenarios as $scenario) {
    $user = $DB->get_record('user', [
        'username' => $scenario['username'],
        'mnethostid' => $CFG->mnet_localhost_id,
        'deleted' => 0,
    ], 'id,username', IGNORE_MISSING);

    if (!$user) {
        $actualallowed = false;
        $actualreason = 'moodle_user_not_found';
    } else {
        $decision = \local_siakadbridge\manager::get_exam_access_decision(
            (int) $user->id,
            $scenario['prodi'],
            $scenario['tahunajaran'],
            $scenario['semester'],
            $scenario['jenis']
        );
        $actualallowed = (bool) $decision->allowed;
        $actualreason = (string) $decision->reason;
    }

    $scenarioispassed = $actualallowed === $scenario['expectedallowed']
        && $actualreason === $scenario['expectedreason'];
    if ($scenarioispassed) {
        $passed++;
    } else {
        $failed++;
    }

    $results[] = [
        'name' => $scenario['name'],
        'username' => $scenario['username'],
        'target' => [
            'prodi' => $scenario['prodi'],
            'tahunajaran' => $scenario['tahunajaran'],
            'semester' => $scenario['semester'],
            'jenis' => $scenario['jenis'] !== '' ? $scenario['jenis'] : 'semua',
        ],
        'expected' => [
            'allowed' => $scenario['expectedallowed'],
            'reason' => $scenario['expectedreason'],
            'description' => $reasonlabels[$scenario['expectedreason']] ?? $scenario['expectedreason'],
        ],
        'actual' => [
            'allowed' => $actualallowed,
            'reason' => $actualreason,
            'description' => $reasonlabels[$actualreason] ?? $actualreason,
        ],
        'passed' => $scenarioispassed,
    ];
}

if ($options['json']) {
    cli_writeln(json_encode([
        'gatemode' => (string) get_config('local_siakadbridge', 'gatemode'),
        'passed' => $passed,
        'failed' => $failed,
        'results' => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
} else {
    cli_writeln('SIAKAD dummy exam-access verification');
    cli_writeln('Gate mode: ' . (string) get_config('local_siakadbridge', 'gatemode'));
    cli_writeln(str_repeat('-', 110));
    foreach ($results as $result) {
        $status = $result['passed'] ? 'PASS' : 'FAIL';
        $expectedaccess = $result['expected']['allowed'] ? 'DIIZINKAN' : 'DITOLAK';
        $actualaccess = $result['actual']['allowed'] ? 'DIIZINKAN' : 'DITOLAK';
        cli_writeln(sprintf(
            '[%s] %s (%s) | target %s %s %s | harapan: %s, %s | aktual: %s, %s',
            $status,
            $result['name'],
            $result['username'],
            $result['target']['prodi'],
            $result['target']['tahunajaran'],
            $result['target']['semester'],
            $expectedaccess,
            $result['expected']['description'],
            $actualaccess,
            $result['actual']['description']
        ));
    }
    cli_writeln(str_repeat('-', 110));
    cli_writeln(sprintf('Summary: %d passed, %d failed.', $passed, $failed));
    if ($failed === 0) {
        cli_writeln('All dummy exam-access scenarios behave as expected.');
    } else {
        cli_writeln('One or more scenarios failed. Review the FAIL rows before using the gate in production.');
    }
}

exit($failed === 0 ? 0 : 1);

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
Verify dummy SIAKAD exam-access scenarios for these programs:
- MKEP: Magister Keperawatan
- KESMAS: Kesehatan Masyarakat
- MP: Manajemen Pendidikan
- HUKUM: Hukum

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
    'wrong_study_program' => 'program studi mahasiswa berbeda dengan program studi ujian',
    'bill_not_found' => 'tagihan pada periode yang dipilih tidak ditemukan',
    'required_bill_not_found' => 'tidak ada tagihan wajib aktif yang harus dibayar',
    'required_bill_unpaid' => 'masih ada tagihan wajib yang belum lunas',
    'paid_bill_found' => 'ditemukan tagihan yang sudah lunas',
    'no_paid_bill' => 'tidak ada tagihan yang sudah lunas',
    'moodle_user_not_found' => 'akun Moodle dummy belum dibuat',
];

$prodilabels = [
    'MKEP' => 'Magister Keperawatan',
    'KESMAS' => 'Kesehatan Masyarakat',
    'MP' => 'Manajemen Pendidikan',
    'HUKUM' => 'Hukum',
];

$scenarios = [
    [
        'name' => 'Mahasiswa Magister Keperawatan aktif dan lunas',
        'username' => 'mhs001',
        'prodi' => 'MKEP',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => true,
        'expectedreason' => 'all_required_bills_paid',
    ],
    [
        'name' => 'Mahasiswa Magister Keperawatan belum lunas',
        'username' => 'mhs002',
        'prodi' => 'MKEP',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'required_bill_unpaid',
    ],
    [
        'name' => 'Mahasiswa Kesehatan Masyarakat mencoba ujian Magister Keperawatan',
        'username' => 'mhs003',
        'prodi' => 'MKEP',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'wrong_study_program',
    ],
    [
        'name' => 'Mahasiswa Kesehatan Masyarakat mengikuti ujian jurusannya sendiri',
        'username' => 'mhs003',
        'prodi' => 'KESMAS',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => true,
        'expectedreason' => 'all_required_bills_paid',
    ],
    [
        'name' => 'Mahasiswa Manajemen Pendidikan mencoba ujian Hukum',
        'username' => 'mhs009',
        'prodi' => 'HUKUM',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'wrong_study_program',
    ],
    [
        'name' => 'Mahasiswa Manajemen Pendidikan mengikuti ujian jurusannya sendiri',
        'username' => 'mhs009',
        'prodi' => 'MP',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => true,
        'expectedreason' => 'all_required_bills_paid',
    ],
    [
        'name' => 'Mahasiswa Hukum mencoba ujian Kesehatan Masyarakat',
        'username' => 'mhs011',
        'prodi' => 'KESMAS',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'wrong_study_program',
    ],
    [
        'name' => 'Mahasiswa Hukum mengikuti ujian jurusannya sendiri',
        'username' => 'mhs011',
        'prodi' => 'HUKUM',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => true,
        'expectedreason' => 'all_required_bills_paid',
    ],
    [
        'name' => 'Satu dari dua tagihan wajib belum lunas',
        'username' => 'mhs004',
        'prodi' => 'MKEP',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => '',
        'expectedallowed' => false,
        'expectedreason' => 'required_bill_unpaid',
    ],
    [
        'name' => 'Tagihan opsional belum lunas tidak memblokir ujian',
        'username' => 'mhs005',
        'prodi' => 'MKEP',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => '',
        'expectedallowed' => true,
        'expectedreason' => 'all_required_bills_paid',
    ],
    [
        'name' => 'Mahasiswa berstatus cuti',
        'username' => 'mhs006',
        'prodi' => 'MKEP',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'student_not_found',
    ],
    [
        'name' => 'Tagihan hanya tersedia pada semester ganjil',
        'username' => 'mhs007',
        'prodi' => 'MKEP',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'bill_not_found',
    ],
    [
        'name' => 'Seluruh tagihan wajib dibatalkan',
        'username' => 'mhs008',
        'prodi' => 'MKEP',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'required_bill_not_found',
    ],
    [
        'name' => 'Mahasiswa aktif tanpa data tagihan',
        'username' => 'mhs010',
        'prodi' => 'MKEP',
        'tahunajaran' => '2026/2027',
        'semester' => 'genap',
        'jenis' => 'UKT',
        'expectedallowed' => false,
        'expectedreason' => 'bill_not_found',
    ],
    [
        'name' => 'Tahun ajaran ujian berbeda dari tagihan mahasiswa',
        'username' => 'mhs001',
        'prodi' => 'MKEP',
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

    $studentprogram = 'tidak ditemukan';
    $studentstatus = 'tidak ditemukan';
    if (!$user) {
        $actualallowed = false;
        $actualreason = 'moodle_user_not_found';
    } else {
        $studentrecord = $DB->get_record_sql(
            'SELECT p.kode, p.nama, m.status
               FROM {local_siakad_user} u
               JOIN {local_siakad_mahasiswa} m ON m.userid = u.id
               JOIN {local_siakad_prodi} p ON p.id = m.prodiid
              WHERE LOWER(u.username) = :username',
            ['username' => \core_text::strtolower($scenario['username'])],
            IGNORE_MISSING
        );
        if ($studentrecord) {
            $studentprogram = $studentrecord->kode . ' (' . $studentrecord->nama . ')';
            $studentstatus = (string) $studentrecord->status;
        }

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

    $targetlabel = $prodilabels[$scenario['prodi']] ?? $scenario['prodi'];
    $results[] = [
        'name' => $scenario['name'],
        'username' => $scenario['username'],
        'student' => [
            'program' => $studentprogram,
            'status' => $studentstatus,
        ],
        'target' => [
            'prodi' => $scenario['prodi'],
            'prodiname' => $targetlabel,
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
        'programs' => $prodilabels,
        'gatemode' => (string) get_config('local_siakadbridge', 'gatemode'),
        'passed' => $passed,
        'failed' => $failed,
        'results' => $results,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
} else {
    cli_writeln('SIAKAD dummy exam-access verification');
    cli_writeln('Programs: MKEP, KESMAS, MP, HUKUM');
    cli_writeln('Gate mode: ' . (string) get_config('local_siakadbridge', 'gatemode'));
    cli_writeln(str_repeat('-', 160));
    foreach ($results as $result) {
        $status = $result['passed'] ? 'PASS' : 'FAIL';
        $expectedaccess = $result['expected']['allowed'] ? 'DIIZINKAN' : 'DITOLAK';
        $actualaccess = $result['actual']['allowed'] ? 'DIIZINKAN' : 'DITOLAK';
        cli_writeln(sprintf(
            '[%s] %s (%s) | mahasiswa: %s, status %s | target: %s (%s), %s %s | harapan: %s, %s | aktual: %s, %s',
            $status,
            $result['name'],
            $result['username'],
            $result['student']['program'],
            $result['student']['status'],
            $result['target']['prodi'],
            $result['target']['prodiname'],
            $result['target']['tahunajaran'],
            $result['target']['semester'],
            $expectedaccess,
            $result['expected']['description'],
            $actualaccess,
            $result['actual']['description']
        ));
    }
    cli_writeln(str_repeat('-', 160));
    cli_writeln(sprintf('Summary: %d passed, %d failed.', $passed, $failed));
    if ($failed === 0) {
        cli_writeln('All dummy exam-access scenarios behave as expected.');
    } else {
        cli_writeln('One or more scenarios failed. Review the FAIL rows before using the gate in production.');
    }
}

exit($failed === 0 ? 0 : 1);

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Dummy SIAKAD administration dashboard.
 *
 * @package    local_siakaddummy
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_siakaddummy\service;

admin_externalpage_setup('local_siakaddummy');
require_capability('moodle/site:config', context_system::instance());

$returnurl = new moodle_url('/local/siakaddummy/index.php');
$action = optional_param('action', '', PARAM_ALPHA);
$billid = optional_param('billid', 0, PARAM_INT);

if ($action !== '' && confirm_sesskey()) {
    if ($action === 'seed') {
        service::seed_demo_data();
        \core\notification::success(get_string('seedcomplete', 'local_siakaddummy'));
    } else if ($action === 'link') {
        $linked = service::link_moodle_users_by_email();
        \core\notification::success(get_string('linkcomplete', 'local_siakaddummy', $linked));
    } else if ($action === 'billpaid' && $billid > 0) {
        service::set_bill_status($billid, 'lunas');
        \core\notification::success(get_string('billupdated', 'local_siakaddummy'));
    } else if ($action === 'billunpaid' && $billid > 0) {
        service::set_bill_status($billid, 'belum_bayar');
        \core\notification::success(get_string('billupdated', 'local_siakaddummy'));
    }
    redirect($returnurl);
}

$PAGE->set_title(get_string('heading', 'local_siakaddummy'));
$PAGE->set_heading(get_string('heading', 'local_siakaddummy'));

$counts = service::get_counts();

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('heading', 'local_siakaddummy'));
echo html_writer::tag('p', get_string('description', 'local_siakaddummy'));

$buttons = html_writer::start_div('d-flex flex-wrap gap-2 mb-4');
$buttons .= $OUTPUT->render(new single_button(
    new moodle_url($returnurl, ['action' => 'seed', 'sesskey' => sesskey()]),
    get_string('seeddata', 'local_siakaddummy'),
    'post',
    single_button::BUTTON_PRIMARY,
));
$buttons .= $OUTPUT->render(new single_button(
    new moodle_url($returnurl, ['action' => 'link', 'sesskey' => sesskey()]),
    get_string('linkusers', 'local_siakaddummy'),
    'post',
));
$buttons .= html_writer::end_div();
echo $buttons;

echo $OUTPUT->heading(get_string('summary', 'local_siakaddummy'), 3);
$summary = new html_table();
$summary->head = [
    get_string('users', 'local_siakaddummy'),
    get_string('students', 'local_siakaddummy'),
    get_string('programmes', 'local_siakaddummy'),
    get_string('bills', 'local_siakaddummy'),
    get_string('lecturers', 'local_siakaddummy'),
];
$summary->data = [[
    $counts['users'],
    $counts['students'],
    $counts['programmes'],
    $counts['bills'],
    $counts['lecturers'],
]];
echo html_writer::table($summary);

$users = $DB->get_records('local_siad_user', null, 'role ASC, name ASC');
if ($users) {
    echo $OUTPUT->heading(get_string('users', 'local_siakaddummy'), 3);
    $table = new html_table();
    $table->head = [
        get_string('sourceid', 'local_siakaddummy'),
        get_string('fullname'),
        get_string('email'),
        get_string('role', 'local_siakaddummy'),
        get_string('moodlelink', 'local_siakaddummy'),
        get_string('status', 'local_siakaddummy'),
    ];
    foreach ($users as $user) {
        $rolekey = 'role_' . $user->role;
        $table->data[] = [
            $user->sourceid,
            s($user->name),
            s($user->email),
            get_string($rolekey, 'local_siakaddummy'),
            $user->moodleuserid
                ? get_string('linked', 'local_siakaddummy', $user->moodleuserid)
                : get_string('notlinked', 'local_siakaddummy'),
            $user->active
                ? get_string('active', 'local_siakaddummy')
                : get_string('inactive', 'local_siakaddummy'),
        ];
    }
    echo html_writer::table($table);
}

$programmes = $DB->get_records('local_siad_prodi', null, 'name ASC');
if ($programmes) {
    echo $OUTPUT->heading(get_string('programmes', 'local_siakaddummy'), 3);
    $table = new html_table();
    $table->head = [
        get_string('code', 'local_siakaddummy'),
        get_string('name'),
        get_string('status', 'local_siakaddummy'),
    ];
    foreach ($programmes as $programme) {
        $table->data[] = [
            s($programme->code),
            s($programme->name),
            $programme->active
                ? get_string('active', 'local_siakaddummy')
                : get_string('inactive', 'local_siakaddummy'),
        ];
    }
    echo html_writer::table($table);
}

$studentsql = "SELECT m.id, m.nim, m.currentsemester, m.status,
                      u.name, u.email, p.code AS prodicode, p.name AS prodiname
                 FROM {local_siad_mahasiswa} m
                 JOIN {local_siad_user} u ON u.id = m.userid
                 JOIN {local_siad_prodi} p ON p.id = m.prodiid
             ORDER BY p.name, u.name";
$students = $DB->get_records_sql($studentsql);
if ($students) {
    echo $OUTPUT->heading(get_string('students', 'local_siakaddummy'), 3);
    $table = new html_table();
    $table->head = [
        get_string('nim', 'local_siakaddummy'),
        get_string('fullname'),
        get_string('prodi', 'local_siakaddummy'),
        get_string('semester', 'local_siakaddummy'),
        get_string('status', 'local_siakaddummy'),
    ];
    foreach ($students as $student) {
        $table->data[] = [
            s($student->nim),
            s($student->name),
            s($student->prodicode . ' - ' . $student->prodiname),
            (int) $student->currentsemester,
            s($student->status),
        ];
    }
    echo html_writer::table($table);
}

$lecturersql = "SELECT d.id, d.nidn, d.status, u.name, u.email,
                       p.code AS prodicode, p.name AS prodiname
                  FROM {local_siad_dosen} d
                  JOIN {local_siad_user} u ON u.id = d.userid
             LEFT JOIN {local_siad_prodi} p ON p.id = d.prodiid
              ORDER BY u.name";
$lecturers = $DB->get_records_sql($lecturersql);
if ($lecturers) {
    echo $OUTPUT->heading(get_string('lecturers', 'local_siakaddummy'), 3);
    $table = new html_table();
    $table->head = [
        get_string('nidn', 'local_siakaddummy'),
        get_string('fullname'),
        get_string('prodi', 'local_siakaddummy'),
        get_string('status', 'local_siakaddummy'),
    ];
    foreach ($lecturers as $lecturer) {
        $table->data[] = [
            s($lecturer->nidn),
            s($lecturer->name),
            $lecturer->prodicode
                ? s($lecturer->prodicode . ' - ' . $lecturer->prodiname)
                : '-',
            s($lecturer->status),
        ];
    }
    echo html_writer::table($table);
}

$billsql = "SELECT t.*, m.nim, u.name
              FROM {local_siad_tagihan} t
              JOIN {local_siad_mahasiswa} m ON m.id = t.mahasiswaid
              JOIN {local_siad_user} u ON u.id = m.userid
          ORDER BY t.academicyear DESC, t.semester DESC, u.name";
$bills = $DB->get_records_sql($billsql);
if ($bills) {
    echo $OUTPUT->heading(get_string('bills', 'local_siakaddummy'), 3);
    $table = new html_table();
    $table->head = [
        get_string('invoice', 'local_siakaddummy'),
        get_string('students', 'local_siakaddummy'),
        get_string('billtype', 'local_siakaddummy'),
        get_string('academicyear', 'local_siakaddummy'),
        get_string('semester', 'local_siakaddummy'),
        get_string('amount', 'local_siakaddummy'),
        get_string('status', 'local_siakaddummy'),
        get_string('examrequirement', 'local_siakaddummy'),
        get_string('actions'),
    ];
    foreach ($bills as $bill) {
        $actionbutton = $bill->status === 'lunas'
            ? new single_button(
                new moodle_url($returnurl, [
                    'action' => 'billunpaid',
                    'billid' => $bill->id,
                    'sesskey' => sesskey(),
                ]),
                get_string('markunpaid', 'local_siakaddummy'),
                'post',
            )
            : new single_button(
                new moodle_url($returnurl, [
                    'action' => 'billpaid',
                    'billid' => $bill->id,
                    'sesskey' => sesskey(),
                ]),
                get_string('markpaid', 'local_siakaddummy'),
                'post',
                single_button::BUTTON_PRIMARY,
            );

        $table->data[] = [
            s($bill->invoice),
            s($bill->nim . ' - ' . $bill->name),
            s($bill->type),
            s($bill->academicyear),
            (int) $bill->semester,
            'Rp' . number_format((int) $bill->amount, 0, ',', '.'),
            get_string('billstatus_' . $bill->status, 'local_siakaddummy'),
            $bill->examrequirement
                ? get_string('yes', 'local_siakaddummy')
                : get_string('no', 'local_siakaddummy'),
            $OUTPUT->render($actionbutton),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();

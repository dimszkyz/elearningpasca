<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakadbridge\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/** Privacy API implementation. */
final class provider implements
        \core_privacy\local\metadata\provider,
        plugin_provider,
        core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_siakad_user', [
            'username' => 'privacy:metadata:username',
            'fullname' => 'privacy:metadata:fullname',
            'email' => 'privacy:metadata:email',
            'moodleuserid' => 'privacy:metadata:moodleuserid',
        ], 'privacy:metadata:local_siakad_user');
        $collection->add_database_table('local_siakad_mahasiswa', [
            'nim' => 'privacy:metadata:nim',
            'nama' => 'privacy:metadata:fullname',
            'email' => 'privacy:metadata:email',
            'moodleuserid' => 'privacy:metadata:moodleuserid',
        ], 'privacy:metadata:local_siakad_mahasiswa');
        $collection->add_database_table('local_siakad_dosen', [
            'nidn' => 'privacy:metadata:nidn',
            'nama' => 'privacy:metadata:fullname',
            'email' => 'privacy:metadata:email',
            'moodleuserid' => 'privacy:metadata:moodleuserid',
        ], 'privacy:metadata:local_siakad_dosen');
        $collection->add_database_table('local_siakad_tagihan', [
            'kodetagihan' => 'privacy:metadata:billing',
            'nominal' => 'privacy:metadata:billing',
            'status' => 'privacy:metadata:billing',
            'paidat' => 'privacy:metadata:billing',
        ], 'privacy:metadata:local_siakad_tagihan');
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if ($DB->record_exists_select('local_siakad_user', 'moodleuserid = :userid', ['userid' => $userid]) ||
                $DB->record_exists_select('local_siakad_mahasiswa', 'moodleuserid = :userid', ['userid' => $userid]) ||
                $DB->record_exists_select('local_siakad_dosen', 'moodleuserid = :userid', ['userid' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (!in_array(SYSCONTEXTID, $contextlist->get_contextids(), true)) {
            return;
        }
        $userid = $contextlist->get_user()->id;
        $identity = $DB->get_record('local_siakad_user', ['moodleuserid' => $userid]);
        $student = $DB->get_record('local_siakad_mahasiswa', ['moodleuserid' => $userid]);
        $lecturer = $DB->get_record('local_siakad_dosen', ['moodleuserid' => $userid]);
        $bills = [];
        if ($student) {
            $bills = array_values($DB->get_records('local_siakad_tagihan', ['mahasiswaid' => $student->id], 'id ASC'));
            foreach ($bills as $bill) {
                $bill->paidat = transform::datetime($bill->paidat);
                $bill->duedate = transform::datetime($bill->duedate);
                $bill->timemodified = transform::datetime($bill->timemodified);
            }
        }
        writer::with_context(\context_system::instance())->export_data(['siakadbridge'], (object) [
            'identity' => $identity ?: null,
            'student' => $student ?: null,
            'lecturer' => $lecturer ?: null,
            'bills' => $bills,
        ]);
    }

    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;
        if ($context->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        $DB->delete_records('local_siakad_tagihan');
        $DB->delete_records('local_siakad_mahasiswa');
        $DB->delete_records('local_siakad_dosen');
        $DB->delete_records('local_siakad_user');
    }

    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (!in_array(SYSCONTEXTID, $contextlist->get_contextids(), true)) {
            return;
        }
        self::delete_user_records((int) $contextlist->get_user()->id);
    }

    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context()->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        $sql = 'SELECT DISTINCT moodleuserid AS userid FROM {local_siakad_user} WHERE moodleuserid > 0
                UNION SELECT DISTINCT moodleuserid AS userid FROM {local_siakad_mahasiswa} WHERE moodleuserid > 0
                UNION SELECT DISTINCT moodleuserid AS userid FROM {local_siakad_dosen} WHERE moodleuserid > 0';
        $userlist->add_from_sql('userid', $sql, []);
    }

    public static function delete_data_for_users(approved_userlist $userlist): void {
        if ($userlist->get_context()->contextlevel !== CONTEXT_SYSTEM) {
            return;
        }
        foreach ($userlist->get_userids() as $userid) {
            self::delete_user_records((int) $userid);
        }
    }

    private static function delete_user_records(int $moodleuserid): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $identityids = $DB->get_records('local_siakad_user', ['moodleuserid' => $moodleuserid], '', 'id');
        $studentconditions = ['moodleuserid = :moodleuserid'];
        $params = ['moodleuserid' => $moodleuserid];
        if ($identityids) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($identityids), SQL_PARAMS_NAMED, 'identity');
            $studentconditions[] = 'userid ' . $insql;
            $params += $inparams;
        }
        $students = $DB->get_records_select(
            'local_siakad_mahasiswa',
            implode(' OR ', $studentconditions),
            $params,
            '',
            'id'
        );
        foreach ($students as $student) {
            $DB->delete_records('local_siakad_tagihan', ['mahasiswaid' => $student->id]);
            $DB->delete_records('local_siakad_mahasiswa', ['id' => $student->id]);
        }
        $DB->delete_records('local_siakad_dosen', ['moodleuserid' => $moodleuserid]);
        foreach ($identityids as $identity) {
            $DB->delete_records('local_siakad_dosen', ['userid' => $identity->id]);
            $DB->delete_records('local_siakad_user', ['id' => $identity->id]);
        }
        $transaction->allow_commit();
    }
}

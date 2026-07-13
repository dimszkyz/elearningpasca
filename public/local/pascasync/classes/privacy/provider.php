<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascasync\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for source-to-Moodle user mappings.
 *
 * @package    local_pascasync
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {
    /**
     * Describe stored personal data.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_pascasync_map', [
            'userid' => 'privacy:metadata:map:userid',
            'sourceid' => 'privacy:metadata:map:sourceid',
            'passwordfingerprint' => 'privacy:metadata:map:passwordfingerprint',
            'sourceupdatedat' => 'privacy:metadata:map:sourceupdatedat',
        ], 'privacy:metadata:map');

        return $collection;
    }

    /**
     * Return user contexts which contain plugin data.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {local_pascasync_map} map
                  JOIN {context} ctx
                    ON ctx.instanceid = map.userid AND ctx.contextlevel = :contextlevel
                 WHERE map.userid = :userid";
        $contextlist->add_from_sql($sql, ['contextlevel' => CONTEXT_USER, 'userid' => $userid]);

        return $contextlist;
    }

    /**
     * Export mapping data for the approved user context.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_user) {
                continue;
            }

            $record = $DB->get_record('local_pascasync_map', ['userid' => $context->instanceid]);
            if (!$record) {
                continue;
            }

            writer::with_context($context)->export_data([
                get_string('pluginname', 'local_pascasync'),
            ], (object) [
                'sourceid' => $record->sourceid,
                'sourceupdatedat' => $record->sourceupdatedat,
                'timecreated' => $record->timecreated,
                'timemodified' => $record->timemodified,
            ]);
        }
    }

    /**
     * Delete mapping data for all users in a user context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context instanceof \context_user) {
            $DB->delete_records('local_pascasync_map', ['userid' => $context->instanceid]);
        }
    }

    /**
     * Delete mapping data for one user.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_user) {
                $DB->delete_records('local_pascasync_map', ['userid' => $context->instanceid]);
            }
        }
    }
}

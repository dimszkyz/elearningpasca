<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascasync;

use core\hook\output\before_standard_footer_html_generation;
use html_writer;
use moodle_url;

/**
 * Output hook callbacks.
 *
 * @package    local_pascasync
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class hook_callbacks {
    /**
     * Add the Pasca sync action to the Moodle user report page.
     *
     * @param before_standard_footer_html_generation $hook Output hook.
     */
    public static function before_standard_footer_html_generation(
        before_standard_footer_html_generation $hook,
    ): void {
        global $CFG, $PAGE;

        if (!$PAGE->has_set_url()) {
            return;
        }

        $targeturl = new moodle_url("/{$CFG->admin}/user.php");
        if (!$PAGE->url->compare($targeturl, URL_MATCH_BASE)) {
            return;
        }

        $context = \context_system::instance();
        if (!has_capability('local/pascasync:sync', $context)) {
            return;
        }

        $buttonid = 'local-pascasync-button';
        $button = html_writer::link(
            new moodle_url('/local/pascasync/index.php'),
            get_string('syncbutton', 'local_pascasync'),
            [
                'id' => $buttonid,
                'class' => 'btn btn-secondary',
                'hidden' => 'hidden',
                'data-action' => 'pasca-user-sync',
            ],
        );

        $PAGE->requires->js_call_amd('local_pascasync/sync_button', 'init', [$buttonid]);
        $hook->add_html($button);
    }
}

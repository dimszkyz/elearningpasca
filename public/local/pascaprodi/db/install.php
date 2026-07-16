<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Installation defaults for local_pascaprodi.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Set safe default settings when the plugin is installed.
 */
function xmldb_local_pascaprodi_install(): void {
    set_config('enabled', 1, 'local_pascaprodi');
    set_config('updatenames', 1, 'local_pascaprodi');
    set_config('archiveondeleted', 1, 'local_pascaprodi');
    set_config('autoenrolstudents', 1, 'local_pascaprodi');
    set_config('nameprefix', get_string('defaultnameprefix', 'local_pascaprodi'), 'local_pascaprodi');
    set_config('archiveprefix', get_string('defaultarchiveprefix', 'local_pascaprodi'), 'local_pascaprodi');
    set_config('syncapiurl', \local_pascaprodi\manager::DEFAULT_API_URL, 'local_pascaprodi');
    set_config('syncapitimeout', 30, 'local_pascaprodi');
}

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_siakadbridge\privacy;

/**
 * Privacy provider for the SIAKAD bridge dummy plugin.
 *
 * @package    local_siakadbridge
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider {
    /**
     * Returns the reason why this plugin does not provide privacy exports yet.
     *
     * @return string Language string identifier.
     */
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}

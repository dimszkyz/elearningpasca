<?php
// This file is part of Moodle - http://moodle.org/

namespace availability_siakadpaid\privacy;

defined('MOODLE_INTERNAL') || die();

/** This availability condition stores configuration only. */
final class provider implements \core_privacy\local\metadata\null_provider {
    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}

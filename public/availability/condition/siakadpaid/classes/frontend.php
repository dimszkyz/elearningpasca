<?php
// This file is part of Moodle - http://moodle.org/

namespace availability_siakadpaid;

defined('MOODLE_INTERNAL') || die();

/** Availability form integration. */
final class frontend extends \core_availability\frontend {
    protected function get_javascript_strings() {
        return [
            'title',
            'label_prodi',
            'label_tahunajaran',
            'label_semester',
            'label_jenis',
            'anyprodi',
            'allbilltypes',
            'choose',
            'semester_ganjil',
            'semester_genap',
            'error_selectprodi',
        ];
    }

    protected function get_javascript_init_params($course, ?\cm_info $cm = null,
            ?\section_info $section = null) {
        return [[
            'prodis' => \local_siakadbridge\manager::get_active_prodi_options(),
            'defaults' => \local_siakadbridge\manager::get_availability_defaults(),
        ]];
    }

    protected function allow_add($course, ?\cm_info $cm = null,
            ?\section_info $section = null) {
        return class_exists('\\local_siakadbridge\\manager');
    }
}

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace availability_siakadpaid;

/**
 * SIAKAD paid billing and study program availability condition.
 *
 * This condition can be attached to a Quiz or other course module through
 * Moodle's Restrict access UI. It grants access when the current Moodle user
 * has an active dummy SIAKAD mahasiswa record, has a lunas tagihan, and belongs
 * to the selected prodi.
 *
 * @package    availability_siakadpaid
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {
    /** @var string Selected study program code, or local_siakadbridge\manager::PRODI_ANY. */
    protected $prodicode;

    /**
     * Constructor.
     *
     * @param \stdClass $structure JSON structure from the availability form.
     * @throws \coding_exception If the condition structure is invalid.
     */
    public function __construct($structure) {
        if (!property_exists($structure, 'prodi') || !is_string($structure->prodi)) {
            throw new \coding_exception('Missing or invalid ->prodi for SIAKAD paid condition');
        }

        $prodicode = trim($structure->prodi);
        if ($prodicode === '' || $prodicode === 'choose') {
            throw new \coding_exception('Missing selected prodi for SIAKAD paid condition');
        }

        $this->prodicode = $prodicode;
    }

    public function save() {
        return (object) [
            'type' => 'siakadpaid',
            'prodi' => $this->prodicode,
        ];
    }

    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        $allow = false;

        if (class_exists('\local_siakadbridge\manager')) {
            $allow = \local_siakadbridge\manager::can_user_access_exam((int) $userid, $this->prodicode);
        }

        if ($not) {
            $allow = !$allow;
        }

        return $allow;
    }

    public function get_description($full, $not, \core_availability\info $info) {
        if ($this->prodicode === \local_siakadbridge\manager::PRODI_ANY) {
            return get_string($not ? 'requires_not_lunas_anyprodi' : 'requires_lunas_anyprodi', 'availability_siakadpaid');
        }

        return get_string(
            $not ? 'requires_not_lunas_prodi' : 'requires_lunas_prodi',
            'availability_siakadpaid',
            s($this->prodicode)
        );
    }

    protected function get_debug_string() {
        return $this->prodicode;
    }

    public function is_applied_to_user_lists() {
        return true;
    }

    public function filter_user_list(array $users, $not, \core_availability\info $info,
            \core_availability\capability_checker $checker) {
        if (!$users || !class_exists('\local_siakadbridge\manager')) {
            return [];
        }

        $result = [];
        foreach ($users as $id => $user) {
            $allow = \local_siakadbridge\manager::can_user_access_exam((int) $id, $this->prodicode);
            if ($not) {
                $allow = !$allow;
            }
            if ($allow) {
                $result[$id] = $user;
            }
        }

        return $result;
    }
}

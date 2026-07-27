<?php
// This file is part of Moodle - http://moodle.org/

namespace availability_siakadpaid;

defined('MOODLE_INTERNAL') || die();

/** SIAKAD billing and study-program availability condition. */
final class condition extends \core_availability\condition {
    private string $prodicode;
    private string $tahunajaran;
    private string $semester;
    private string $jenis;

    public function __construct($structure) {
        if (!property_exists($structure, 'prodi') || !is_string($structure->prodi)) {
            throw new \coding_exception('Missing or invalid ->prodi for SIAKAD condition');
        }
        $this->prodicode = clean_param(trim($structure->prodi), PARAM_TEXT);
        if ($this->prodicode === '' || $this->prodicode === 'choose') {
            throw new \coding_exception('Missing selected study program for SIAKAD condition');
        }
        $this->tahunajaran = property_exists($structure, 'tahunajaran') && is_string($structure->tahunajaran)
            ? clean_param(trim($structure->tahunajaran), PARAM_TEXT) : '';
        $this->semester = property_exists($structure, 'semester') && is_string($structure->semester)
            ? clean_param(trim($structure->semester), PARAM_ALPHANUMEXT) : '';
        $this->jenis = property_exists($structure, 'jenis') && is_string($structure->jenis)
            ? clean_param(trim($structure->jenis), PARAM_TEXT) : '';
    }

    public function save() {
        return (object) [
            'type' => 'siakadpaid',
            'prodi' => $this->prodicode,
            'tahunajaran' => $this->tahunajaran,
            'semester' => $this->semester,
            'jenis' => $this->jenis,
        ];
    }

    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        $allow = \local_siakadbridge\manager::can_user_access_exam(
            (int) $userid,
            $this->prodicode,
            $this->tahunajaran,
            $this->semester,
            $this->jenis
        );
        return $not ? !$allow : $allow;
    }

    public function get_description($full, $not, \core_availability\info $info) {
        $details = (object) [
            'prodi' => $this->prodicode === \local_siakadbridge\manager::PRODI_ANY
                ? get_string('anyprodi', 'availability_siakadpaid') : $this->prodicode,
            'tahunajaran' => $this->tahunajaran !== '' ? $this->tahunajaran : get_string('configureddefault', 'availability_siakadpaid'),
            'semester' => $this->semester !== '' ? $this->semester : get_string('configureddefault', 'availability_siakadpaid'),
            'jenis' => $this->jenis !== '' ? $this->jenis : get_string('allbilltypes', 'availability_siakadpaid'),
        ];
        return get_string($not ? 'requires_not' : 'requires', 'availability_siakadpaid', $details);
    }

    protected function get_debug_string() {
        return implode('|', [$this->prodicode, $this->tahunajaran, $this->semester, $this->jenis]);
    }

    public function is_applied_to_user_lists() {
        return true;
    }

    public function filter_user_list(array $users, $not, \core_availability\info $info,
            \core_availability\capability_checker $checker) {
        $result = [];
        foreach ($users as $id => $user) {
            $allow = \local_siakadbridge\manager::can_user_access_exam(
                (int) $id,
                $this->prodicode,
                $this->tahunajaran,
                $this->semester,
                $this->jenis
            );
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

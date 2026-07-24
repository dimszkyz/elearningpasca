<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_pascaprodi;

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for campus-wide study-program category synchronisation.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class category_sync_service_test extends \advanced_testcase {
    public function test_normalise_payload_keeps_every_jenjang(): void {
        $payload = (object) [
            'data' => [
                (object) [
                    'jenjang' => 'Diploma Tiga',
                    'nama' => 'Keperawatan',
                    'slug' => 'd3-keperawatan',
                ],
                (object) [
                    'jenjang' => 'Sarjana',
                    'nama' => 'Informatika',
                    'slug' => 's1-informatika',
                ],
                (object) [
                    'jenjang' => 'Profesi',
                    'nama' => 'Ners',
                    'slug' => 'profesi-ners',
                ],
                (object) [
                    'jenjang' => 'Magister',
                    'nama' => 'Hukum',
                    'slug' => 's2-hukum',
                ],
            ],
        ];

        $programs = category_sync_service::normalise_payload($payload);

        $this->assertCount(4, $programs);
        $this->assertSame('Diploma Tiga Keperawatan', $programs[0]['name']);
        $this->assertSame('Sarjana Informatika', $programs[1]['name']);
        $this->assertSame('Profesi Ners', $programs[2]['name']);
        $this->assertSame('Magister Hukum', $programs[3]['name']);
    }

    public function test_normalise_payload_accepts_root_array(): void {
        $payload = [
            [
                'jenjang' => 'Sarjana',
                'nama' => 'Farmasi',
                'slug' => 's1-farmasi',
            ],
        ];

        $programs = category_sync_service::normalise_payload($payload);

        $this->assertCount(1, $programs);
        $this->assertSame('Sarjana Farmasi', $programs[0]['name']);
        $this->assertSame('s1-farmasi', $programs[0]['idnumber']);
    }

    public function test_code_is_used_when_slug_is_missing(): void {
        $program = category_sync_service::normalise_program_studi_item((object) [
            'jenjang' => 'Sarjana',
            'nama' => 'Bisnis Digital',
            'kode' => 'S1-BD',
        ]);

        $this->assertNotNull($program);
        $this->assertSame('Sarjana Bisnis Digital', $program['name']);
        $this->assertSame('S1-BD', $program['idnumber']);
    }

    public function test_item_without_name_or_identity_is_skipped(): void {
        $this->assertNull(category_sync_service::normalise_program_studi_item((object) [
            'jenjang' => 'Sarjana',
            'slug' => 'missing-name',
        ]));
        $this->assertNull(category_sync_service::normalise_program_studi_item((object) [
            'jenjang' => 'Sarjana',
            'nama' => 'Tanpa Identitas',
        ]));
    }
}

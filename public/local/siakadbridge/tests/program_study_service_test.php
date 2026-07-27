<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakadbridge;

defined('MOODLE_INTERNAL') || die();

/** Tests for the dedicated study-program API adapter. */
final class program_study_service_test extends \advanced_testcase {
    public function test_normalises_root_array_and_common_unw_fields(): void {
        $payload = json_decode(<<<'JSON'
[
  {"id_program_studi":"101","kode_program_studi":"S1-IF","nama_program_studi":"S1 Informatika","status":"aktif"},
  {"id_program_studi":"102","kode_program_studi":"S1-HK","nama_program_studi":"S1 Hukum","status":"nonaktif"}
]
JSON
        );

        $programs = \local_siakadbridge\sync\program_study_service::normalise_payload($payload);

        $this->assertCount(2, $programs);
        $this->assertSame('101', $programs[0]->id);
        $this->assertSame('S1-HK', $programs[0]->kode);
        $this->assertSame('S1 Hukum', $programs[0]->nama);
        $this->assertFalse($programs[0]->aktif);
        $this->assertSame('S1-IF', $programs[1]->kode);
        $this->assertTrue($programs[1]->aktif);
    }

    public function test_normalises_nested_data_and_uses_id_when_code_is_missing(): void {
        $payload = (object) [
            'data' => (object) [
                'items' => [
                    (object) [
                        'id' => 'S2-KES',
                        'name' => 'Magister Kesehatan',
                        'is_active' => 1,
                    ],
                ],
            ],
        ];

        $programs = \local_siakadbridge\sync\program_study_service::normalise_payload($payload);

        $this->assertCount(1, $programs);
        $this->assertSame('S2-KES', $programs[0]->id);
        $this->assertSame('S2-KES', $programs[0]->kode);
        $this->assertSame('Magister Kesehatan', $programs[0]->nama);
        $this->assertTrue($programs[0]->aktif);
    }

    public function test_rejects_item_without_name(): void {
        $payload = [(object) ['id' => '101', 'kode' => 'S1-IF']];

        $this->expectException(\invalid_parameter_exception::class);
        \local_siakadbridge\sync\program_study_service::normalise_payload($payload);
    }
}

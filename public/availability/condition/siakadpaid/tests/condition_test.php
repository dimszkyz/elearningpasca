<?php
// This file is part of Moodle - http://moodle.org/

namespace availability_siakadpaid;

defined('MOODLE_INTERNAL') || die();

/** Tests for condition serialisation and validation. */
final class condition_test extends \advanced_testcase {
    public function test_condition_round_trip(): void {
        $structure = (object) [
            'prodi' => 'TI',
            'tahunajaran' => '2026/2027',
            'semester' => 'genap',
            'jenis' => 'UKT',
        ];
        $condition = new condition($structure);
        $saved = $condition->save();
        $this->assertSame('siakadpaid', $saved->type);
        $this->assertSame('TI', $saved->prodi);
        $this->assertSame('2026/2027', $saved->tahunajaran);
        $this->assertSame('genap', $saved->semester);
        $this->assertSame('UKT', $saved->jenis);
    }

    public function test_missing_prodi_is_rejected(): void {
        $this->expectException(\coding_exception::class);
        new condition((object) ['tahunajaran' => '2026/2027']);
    }
}

<?php
// This file is part of Moodle - http://moodle.org/

namespace local_siakadbridge;

defined('MOODLE_INTERNAL') || die();

/** Tests for payload import. */
final class sync_service_test extends \advanced_testcase {
    public function test_payload_import_is_idempotent(): void {
        global $DB;

        $this->resetAfterTest();
        $payload = json_decode(<<<'JSON'
{
  "prodi":[{"id":"p-ti","kode":"TI","nama":"Teknologi Informasi","aktif":true}],
  "users":[{"id":"u-1","username":"mhs001","fullname":"Mahasiswa Satu","email":"mhs001@example.test","role":"mahasiswa"}],
  "mahasiswa":[{"id":"m-1","username":"mhs001","nim":"240001","nama":"Mahasiswa Satu","email":"mhs001@example.test","prodi":"TI","status":"aktif"}],
  "dosen":[],
  "tagihan":[{"id":"t-1","nim":"240001","kodetagihan":"UKT-1","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"lunas","wajib":true}]
}
JSON
);

        $first = \local_siakadbridge\sync\service::import_payload($payload, 'test');
        $second = \local_siakadbridge\sync\service::import_payload($payload, 'test');

        $this->assertSame(4, $first->inserted);
        $this->assertSame(0, $first->updated);
        $this->assertSame(0, $second->inserted);
        $this->assertSame(4, $second->updated);
        $this->assertEquals(1, $DB->count_records('local_siakad_tagihan'));
    }

    public function test_full_snapshot_deactivates_missing_records(): void {
        global $DB;

        $this->resetAfterTest();
        $initial = json_decode(<<<'JSON'
{
  "prodi":[{"id":"p-ti","kode":"TI","nama":"Teknologi Informasi","aktif":true}],
  "users":[{"id":"u-1","username":"mhs001","fullname":"Mahasiswa Satu","email":"mhs001@example.test","role":"mahasiswa"}],
  "mahasiswa":[{"id":"m-1","username":"mhs001","nim":"240001","nama":"Mahasiswa Satu","email":"mhs001@example.test","prodi":"TI","status":"aktif"}],
  "dosen":[],
  "tagihan":[{"id":"t-1","nim":"240001","kodetagihan":"UKT-1","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"lunas","wajib":true}]
}
JSON
);
        \local_siakadbridge\sync\service::import_payload($initial, 'test');
        $empty = (object) [
            'fullsnapshot' => true,
            'prodi' => [],
            'users' => [],
            'mahasiswa' => [],
            'dosen' => [],
            'tagihan' => [],
        ];
        \local_siakadbridge\sync\service::import_payload($empty, 'test');

        $this->assertEquals(0, $DB->get_field('local_siakad_prodi', 'aktif', ['kode' => 'TI']));
        $this->assertSame('nonaktif', $DB->get_field('local_siakad_mahasiswa', 'status', ['nim' => '240001']));
        $this->assertSame('dibatalkan', $DB->get_field('local_siakad_tagihan', 'status', ['kodetagihan' => 'UKT-1']));
        $this->assertEquals(0, $DB->get_field('local_siakad_tagihan', 'wajib', ['kodetagihan' => 'UKT-1']));
    }
}

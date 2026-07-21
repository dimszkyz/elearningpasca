<?php
// This file is part of Moodle - http://moodle.org/
$string['pluginname'] = 'Tagihan dan program studi SIAKAD';
$string['title'] = 'Tagihan dan program studi SIAKAD';
$string['label_prodi'] = 'Program studi';
$string['label_tahunajaran'] = 'Tahun ajaran';
$string['label_semester'] = 'Semester';
$string['label_jenis'] = 'Jenis tagihan';
$string['anyprodi'] = 'Semua program studi aktif';
$string['allbilltypes'] = 'Semua jenis tagihan wajib';
$string['configureddefault'] = 'mengikuti pengaturan';
$string['semester_ganjil'] = 'Ganjil';
$string['semester_genap'] = 'Genap';
$string['choose'] = 'Pilih...';
$string['error_selectprodi'] = 'Pilih program studi.';
$string['requires'] = 'Ujian hanya dapat diakses oleh mahasiswa aktif dari prodi {$a->prodi}, tahun ajaran {$a->tahunajaran}, semester {$a->semester}, dengan {$a->jenis} berstatus lunas. Akses ditolak apabila akun mahasiswa belum terhubung, prodi berbeda, periode tidak sesuai, data tagihan tidak ditemukan, atau masih ada tagihan wajib yang belum lunas.';
$string['requires_not'] = 'Ujian hanya dapat diakses apabila syarat SIAKAD berikut tidak terpenuhi: prodi {$a->prodi}, tahun ajaran {$a->tahunajaran}, semester {$a->semester}, jenis tagihan {$a->jenis}.';
$string['privacy:metadata'] = 'Kondisi akses SIAKAD hanya menyimpan konfigurasi aktivitas dan tidak menyimpan data pribadi.';

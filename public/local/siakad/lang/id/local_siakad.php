<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Integrasi SIAKAD Dummy';
$string['notlinkedstudent'] = 'Akun Moodle Anda belum terhubung dengan data mahasiswa SIAKAD.';
$string['wrongprogramme'] = 'Ujian ini tidak ditujukan untuk Program Studi Anda.';
$string['unpaidbill'] = 'Ujian belum dapat diakses karena tagihan periode {$a} belum lunas.';
$string['task_syncprodi'] = 'Sinkronkan Program Studi dari local_pascaprodi';
$string['dbheading'] = 'Koneksi basis data SIAKAD';
$string['dbheading_desc'] = 'Data induk SIAKAD (identitas, program studi, mahasiswa, dosen, tagihan) berada di basis data tersendiri. Kosongkan jenis basis data bila tabel tersebut ingin tetap berada di basis data Moodle. Setelah diisi, jalankan <code>php local/siakad/cli/setup_external_db.php --install</code> untuk membuat tabel, lalu <code>--migrate</code> untuk memindahkan data yang sudah ada.';
$string['dbtype'] = 'Jenis basis data';
$string['dbtype_desc'] = 'Driver yang dipakai untuk basis data SIAKAD.';
$string['dbtype_none'] = 'Gunakan basis data Moodle';
$string['dbhost'] = 'Host';
$string['dbhost_desc'] = 'Server tempat basis data SIAKAD berada.';
$string['dbname'] = 'Nama basis data';
$string['dbname_desc'] = 'Nama basis data SIAKAD, misalnya siakad_dummy.';
$string['dbuser'] = 'Pengguna';
$string['dbuser_desc'] = 'Pengguna basis data dengan hak baca dan tulis.';
$string['dbpass'] = 'Kata sandi';
$string['dbpass_desc'] = 'Kata sandi pengguna tersebut.';
$string['dbprefix'] = 'Awalan tabel';
$string['dbprefix_desc'] = 'Awalan nama tabel SIAKAD. Umumnya dikosongkan sehingga tabel bernama local_siakad_mahasiswa dan seterusnya.';
$string['dbport'] = 'Port';
$string['dbport_desc'] = 'Kosongkan untuk memakai port bawaan driver.';
$string['dbsocket'] = 'Socket';
$string['dbsocket_desc'] = 'Lokasi Unix socket, bila server tidak diakses lewat TCP.';

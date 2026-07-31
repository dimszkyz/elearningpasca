<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Indonesian strings for local_pascaprodi.
 *
 * @package    local_pascaprodi
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Otomasi Prodi Pascasarjana';
$string['defaultnameprefix'] = 'Mahasiswa - ';
$string['defaultarchiveprefix'] = '[ARSIP] ';
$string['cohortdescription'] = 'Cohort mahasiswa otomatis untuk Program Studi: {$a}.';
$string['cohortarchiveddescription'] = 'Cohort ini diarsipkan otomatis karena Program Studi terkait sudah tidak aktif. ID Program Studi: {$a->prodiid}. Nama Program Studi: {$a->prodiname}.';
$string['teachercohortarchiveddescription'] = 'Cohort dosen lama dari Otomasi Prodi telah dinonaktifkan. Gunakan role global untuk dosen.';
$string['cohortenrolname'] = 'Otomasi Prodi: {$a}';
$string['setting_enabled'] = 'Aktifkan otomasi cohort Program Studi';
$string['setting_enabled_desc'] = 'Jika aktif, setiap Program Studi memiliki cohort mahasiswa otomatis dan course dapat dikaitkan ke Program Studi lewat form pengaturan course.';
$string['setting_cohortheading'] = 'Cohort mahasiswa otomatis';
$string['setting_cohortheading_desc'] = 'Pengaturan nama cohort mahasiswa yang dibuat dari Program Studi.';
$string['setting_nameprefix'] = 'Awalan nama cohort mahasiswa';
$string['setting_nameprefix_desc'] = 'Awalan yang ditambahkan ke nama Program Studi untuk membentuk nama cohort mahasiswa. Contoh: Mahasiswa - Magister Manajemen.';
$string['setting_updatenames'] = 'Ikuti perubahan nama Program Studi';
$string['setting_updatenames_desc'] = 'Jika aktif, nama cohort mahasiswa otomatis ikut diperbarui saat sinkronisasi mengubah nama Program Studi.';
$string['setting_archiveondeleted'] = 'Arsipkan cohort saat Program Studi dihapus';
$string['setting_archiveondeleted_desc'] = 'Jika aktif, cohort mahasiswa disembunyikan dan diberi awalan arsip ketika Program Studi terkait hilang dari API. Anggota cohort tidak dihapus.';
$string['setting_archiveprefix'] = 'Awalan arsip';
$string['setting_archiveprefix_desc'] = 'Awalan yang ditambahkan ke nama cohort saat Program Studi terkait diarsipkan.';
$string['setting_apisyncheading'] = 'Sinkronisasi Program Studi dari API UNW';
$string['setting_apisyncheading_desc'] = 'Pengaturan sumber data untuk mengisi tabel Program Studi dari API Program Studi UNW.';
$string['setting_syncapiurl'] = 'URL API Program Studi';
$string['setting_syncapiurl_desc'] = 'API yang berisi daftar program studi. Semua data dari API disimpan sebagai Program Studi, tanpa memandang jenjang.';
$string['setting_syncapitimeout'] = 'Timeout API';
$string['setting_syncapitimeout_desc'] = 'Batas waktu koneksi API dalam detik.';
$string['adduserprodiheading'] = 'Role akses dan Program Studi';
$string['adduserprodiheading_desc'] = 'Bagian ini wajib diisi saat tambah user baru. Pilih role akses dan satu atau beberapa Program Studi. Role diberikan pada level sistem, dan user dimasukkan ke cohort mahasiswa dari setiap Program Studi yang dipilih.';
$string['field_user'] = 'User';
$string['field_accessrole'] = 'Role akses';
$string['field_accessrole_help'] = 'Wajib pilih role yang sudah ada di database Moodle. Role selain Student diberikan pada level sistem. Setiap user dimasukkan ke cohort mahasiswa dari tiap Program Studi yang dipilih, dan itulah yang memberi akses ke course Prodi tersebut.';
$string['field_prodi'] = 'Program Studi';
$string['field_prodi_help'] = 'Pilih satu atau beberapa Program Studi. User dimasukkan ke cohort mahasiswa dari setiap Program Studi yang dipilih, sehingga otomatis ter-enrol ke semua course yang terkait Prodi tersebut.';
$string['chooseaccessrole'] = 'Pilih role akses';
$string['norolechange'] = 'Tidak mengubah role';
$string['nocohortchange'] = 'Tidak memasukkan ke cohort';
$string['roleassigned'] = 'Role sistem diberikan: {$a}';
$string['rolealreadyassigned'] = 'User sudah memiliki role sistem: {$a}';
$string['cohortassigned'] = 'User dimasukkan ke cohort: {$a}';
$string['cohortalreadyassigned'] = 'User sudah menjadi anggota cohort: {$a}';
$string['cohortnotcreated'] = 'Cohort otomatis untuk Program Studi {$a} belum bisa dibuat.';
$string['prodi'] = 'Program Studi';
$string['prodi_help'] = 'Pilih Program Studi yang memiliki course ini. Setiap Program Studi yang dipilih mendapat metode enrolment Cohort sync, sehingga mahasiswanya otomatis ter-enrol. Menghapus pilihan akan melepas metode enrolment tersebut. Kosongkan untuk course yang tidak terikat Prodi manapun. Field Course category di atas hanya menentukan letak course dalam struktur situs.';
$string['prodicode'] = 'Kode';
$string['prodiid'] = 'ID Program Studi';
$string['cohortid'] = 'ID cohort';
$string['syncmessage'] = 'Pesan';
$string['syncprodibutton'] = 'Sinkronkan Program Studi';
$string['syncprodipage'] = 'Sinkronisasi Semua Program Studi';
$string['syncprodipage_desc'] = 'Sinkronisasi ini mengambil data dari {$a->url}, menyimpan setiap data sebagai Program Studi dengan nama dari jenjang + spasi + nama dan slug sebagai kode, lalu membuat satu cohort mahasiswa per Program Studi. Kategori course tidak disentuh.';
$string['syncprodisuccess'] = 'Sinkronisasi selesai. Dibuat: {$a->created}. Diperbarui: {$a->updated}. Tidak berubah: {$a->unchanged}. Dilewati: {$a->skipped}. Gagal: {$a->failed}. Dinonaktifkan: {$a->deactivated}. Cohort baru: {$a->cohortcreated}. Cohort diperbarui: {$a->cohortupdated}.';
$string['syncprodifailed'] = 'Sinkronisasi gagal: {$a}';
$string['apisyncfailed'] = 'Tidak bisa mengambil data API: {$a}';
$string['apisyncinvalidjson'] = 'Response API bukan JSON valid: {$a}';
$string['apisyncinvaliddata'] = 'Response API tidak memiliki struktur data yang valid.';
$string['error_accessrole_required'] = 'Role akses wajib dipilih.';
$string['error_prodi_required'] = 'Program Studi wajib dipilih.';
$string['nochangesmade'] = 'Tidak ada perubahan yang dipilih.';
$string['userroleassigned'] = 'Pengaturan user berhasil diproses untuk {$a}.';
$string['privacy:metadata'] = 'Plugin Otomasi Prodi Pascasarjana tidak menyimpan data pribadi. Plugin ini hanya menyimpan Program Studi, membuat cohort Moodle untuknya, serta menambahkan metode Cohort sync mahasiswa ke course.';

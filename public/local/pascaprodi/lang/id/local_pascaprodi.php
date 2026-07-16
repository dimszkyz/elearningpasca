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
$string['cohortdescription'] = 'Cohort mahasiswa otomatis untuk kategori Program Studi: {$a}.';
$string['cohortarchiveddescription'] = 'Cohort ini diarsipkan otomatis karena kategori Program Studi terkait telah dihapus. ID kategori lama: {$a->categoryid}. Nama kategori lama: {$a->categoryname}.';
$string['teachercohortarchiveddescription'] = 'Cohort dosen lama dari Otomasi Prodi telah dinonaktifkan. Gunakan role global atau role kategori untuk dosen.';
$string['cohortenrolname'] = 'Otomasi Prodi: {$a}';
$string['setting_enabled'] = 'Aktifkan otomasi cohort Program Studi';
$string['setting_enabled_desc'] = 'Jika aktif, Moodle akan membuat cohort mahasiswa otomatis setiap kali kategori course dibuat.';
$string['setting_cohortheading'] = 'Cohort mahasiswa otomatis';
$string['setting_cohortheading_desc'] = 'Pengaturan nama cohort mahasiswa yang dibuat dari kategori Program Studi.';
$string['setting_nameprefix'] = 'Awalan nama cohort mahasiswa';
$string['setting_nameprefix_desc'] = 'Awalan yang ditambahkan ke nama kategori untuk membentuk nama cohort mahasiswa. Contoh: Mahasiswa - Magister Manajemen.';
$string['setting_updatenames'] = 'Ikuti perubahan nama kategori';
$string['setting_updatenames_desc'] = 'Jika aktif, nama cohort mahasiswa otomatis ikut diperbarui saat nama kategori course diubah.';
$string['setting_archiveondeleted'] = 'Arsipkan cohort saat kategori dihapus';
$string['setting_archiveondeleted_desc'] = 'Jika aktif, cohort mahasiswa otomatis disembunyikan dan diberi awalan arsip ketika kategori terkait dihapus. Anggota cohort tidak dihapus.';
$string['setting_archiveprefix'] = 'Awalan arsip';
$string['setting_archiveprefix_desc'] = 'Awalan yang ditambahkan ke nama cohort saat kategori terkait dihapus.';
$string['setting_enrolheading'] = 'Cohort sync otomatis saat Mata Kuliah dibuat';
$string['setting_enrolheading_desc'] = 'Saat course baru dibuat di kategori Program Studi, plugin dapat otomatis menambahkan metode enrolment Cohort sync untuk cohort mahasiswa kategori tersebut.';
$string['setting_autoenrolstudents'] = 'Otomatis enrol cohort mahasiswa sebagai Student';
$string['setting_autoenrolstudents_desc'] = 'Jika aktif, course baru otomatis mendapat Cohort sync dari cohort mahasiswa kategori course tersebut dengan role Student.';
$string['setting_apisyncheading'] = 'Sinkronisasi kategori Prodi dari API UNW';
$string['setting_apisyncheading_desc'] = 'Pengaturan sumber data untuk membuat kategori Prodi root secara otomatis dari API Program Studi UNW.';
$string['setting_syncapiurl'] = 'URL API Program Studi';
$string['setting_syncapiurl_desc'] = 'API yang berisi daftar program studi. Hanya data dengan jenjang Magister yang akan dibuat menjadi kategori root Moodle.';
$string['setting_syncapitimeout'] = 'Timeout API';
$string['setting_syncapitimeout_desc'] = 'Batas waktu koneksi API dalam detik.';
$string['adduserprodiheading'] = 'Role akses dan Program Studi';
$string['adduserprodiheading_desc'] = 'Bagian ini hanya muncul saat tambah user baru. Pilih role akses dan Prodi/kategori. Mahasiswa dimasukkan ke cohort Prodi. Teacher/globalteacher/Course creator/Manager diberi role pada kategori Prodi yang dipilih.';
$string['field_user'] = 'User';
$string['field_accessrole'] = 'Role akses';
$string['field_accessrole_help'] = 'Pilih role yang sudah ada di database Moodle. Role mahasiswa/student dipakai bersama cohort Prodi. Role dosen/globalteacher/Course creator/Manager akan diberikan pada kategori Prodi yang dipilih, sehingga bisa memilih beberapa Prodi.';
$string['field_systemrole'] = 'Role sistem';
$string['field_systemrole_help'] = 'Field lama. Gunakan Role akses.';
$string['field_categories'] = 'Program Studi / Kategori';
$string['field_categories_help'] = 'Pilih kategori Program Studi. Untuk mahasiswa, pilih satu Prodi agar user dimasukkan ke cohort mahasiswa Prodi tersebut. Untuk dosen/globalteacher/Course creator/Manager, bisa pilih beberapa Prodi dan role akan diberikan pada kategori tersebut.';
$string['field_cohort'] = 'Cohort Prodi';
$string['field_cohort_help'] = 'Field lama. Gunakan Program Studi / Kategori.';
$string['norolechange'] = 'Tidak mengubah role';
$string['nocohortchange'] = 'Tidak memasukkan ke cohort';
$string['roleassigned'] = 'Role sistem diberikan: {$a}';
$string['rolealreadyassigned'] = 'User sudah memiliki role sistem: {$a}';
$string['roleassignedcategory'] = 'Role {$a->role} diberikan pada Prodi/Kategori: {$a->category}';
$string['rolealreadyassignedcategory'] = 'User sudah memiliki role {$a->role} pada Prodi/Kategori: {$a->category}';
$string['cohortassigned'] = 'User dimasukkan ke cohort: {$a}';
$string['cohortalreadyassigned'] = 'User sudah menjadi anggota cohort: {$a}';
$string['cohortassignedcategory'] = 'User dimasukkan ke cohort {$a->cohort} untuk Prodi/Kategori: {$a->category}';
$string['cohortalreadyassignedcategory'] = 'User sudah menjadi anggota cohort {$a->cohort} untuk Prodi/Kategori: {$a->category}';
$string['cohortnotcreated'] = 'Cohort otomatis untuk Prodi/Kategori {$a} belum bisa dibuat.';
$string['teachercohortskipped'] = 'Role dosen/globalteacher diberikan pada kategori Prodi. User tidak dimasukkan ke cohort mahasiswa agar tidak terdaftar sebagai Student.';
$string['synccategoriesbutton'] = 'Sync categories';
$string['synccategoriespage'] = 'Sinkronisasi Kategori Prodi';
$string['synccategoriespage_desc'] = 'Sinkronisasi ini mengambil data dari {$a->url}, hanya memilih jenjang Magister, membuat nama kategori dari jenjang + spasi + nama, memakai slug sebagai ID number, dan membuat kategori pada root.';
$string['synccategoriessuccess'] = 'Sinkronisasi selesai. Dibuat: {$a->created}. Diperbarui: {$a->updated}. Tidak berubah: {$a->unchanged}. Dilewati: {$a->skipped}. Gagal: {$a->failed}. Cohort baru: {$a->cohortcreated}. Cohort diperbarui: {$a->cohortupdated}.';
$string['synccategoriesfailed'] = 'Sinkronisasi gagal: {$a}';
$string['apisyncfailed'] = 'Tidak bisa mengambil data API: {$a}';
$string['apisyncinvalidjson'] = 'Response API bukan JSON valid: {$a}';
$string['apisyncinvaliddata'] = 'Response API tidak memiliki struktur data yang valid.';
$string['categoryid'] = 'ID kategori';
$string['cohortid'] = 'ID cohort';
$string['syncmessage'] = 'Pesan';
$string['error_select_role_or_category'] = 'Pilih minimal role akses atau Program Studi/Kategori.';
$string['error_multiple_categories_teacher_role'] = 'Lebih dari satu Prodi hanya boleh untuk role dosen/globalteacher/Course creator/Manager. Mahasiswa/student cukup pilih satu Prodi.';
$string['nochangesmade'] = 'Tidak ada perubahan yang dipilih.';
$string['userroleassigned'] = 'Pengaturan user berhasil diproses untuk {$a}.';
$string['privacy:metadata'] = 'Plugin Otomasi Prodi Pascasarjana tidak menyimpan data pribadi. Plugin ini hanya membuat dan memperbarui cohort Moodle berdasarkan kategori course serta menambahkan Cohort sync mahasiswa ke course.';
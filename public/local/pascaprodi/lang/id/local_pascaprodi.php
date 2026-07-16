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
$string['userrolepage'] = 'Daftarkan User, Role, dan Cohort';
$string['userrolepage_desc'] = 'Gunakan halaman ini agar admin bisa memilih user, memberi role sistem seperti globalteacher/Course creator, dan memasukkan user ke cohort Prodi tanpa pindah menu.';
$string['field_user'] = 'User';
$string['field_systemrole'] = 'Role sistem';
$string['field_systemrole_help'] = 'Role ini diberikan pada level System. Gunakan untuk role global seperti globalteacher atau Course creator. Pilih Tidak mengubah role jika hanya ingin memasukkan user ke cohort.';
$string['field_cohort'] = 'Cohort Prodi';
$string['field_cohort_help'] = 'Pilih cohort mahasiswa Prodi jika user adalah mahasiswa. Dosen dapat diberi role sistem tanpa harus dimasukkan ke cohort mahasiswa.';
$string['norolechange'] = 'Tidak mengubah role';
$string['nocohortchange'] = 'Tidak memasukkan ke cohort';
$string['roleassigned'] = 'Role sistem diberikan: {$a}';
$string['rolealreadyassigned'] = 'User sudah memiliki role sistem: {$a}';
$string['cohortassigned'] = 'User dimasukkan ke cohort: {$a}';
$string['cohortalreadyassigned'] = 'User sudah menjadi anggota cohort: {$a}';
$string['nochangesmade'] = 'Tidak ada perubahan yang dipilih.';
$string['userroleassigned'] = 'Pengaturan user berhasil diproses untuk {$a}.';
$string['privacy:metadata'] = 'Plugin Otomasi Prodi Pascasarjana tidak menyimpan data pribadi. Plugin ini hanya membuat dan memperbarui cohort Moodle berdasarkan kategori course serta menambahkan Cohort sync mahasiswa ke course.';

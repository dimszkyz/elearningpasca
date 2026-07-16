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
$string['defaultteachernameprefix'] = 'Dosen - ';
$string['defaultarchiveprefix'] = '[ARSIP] ';
$string['cohortdescription'] = 'Cohort mahasiswa otomatis untuk kategori Program Studi: {$a}.';
$string['teachercohortdescription'] = 'Cohort dosen otomatis untuk kategori Program Studi: {$a}.';
$string['cohortarchiveddescription'] = 'Cohort ini diarsipkan otomatis karena kategori Program Studi terkait telah dihapus. ID kategori lama: {$a->categoryid}. Nama kategori lama: {$a->categoryname}.';
$string['cohortenrolname'] = 'Otomasi Prodi: {$a}';
$string['setting_enabled'] = 'Aktifkan otomasi cohort Program Studi';
$string['setting_enabled_desc'] = 'Jika aktif, Moodle akan membuat cohort otomatis setiap kali kategori course dibuat.';
$string['setting_cohortheading'] = 'Cohort otomatis';
$string['setting_cohortheading_desc'] = 'Pengaturan nama cohort mahasiswa dan dosen yang dibuat dari kategori Program Studi.';
$string['setting_nameprefix'] = 'Awalan nama cohort mahasiswa';
$string['setting_nameprefix_desc'] = 'Awalan yang ditambahkan ke nama kategori untuk membentuk nama cohort mahasiswa. Contoh: Mahasiswa - Magister Manajemen.';
$string['setting_teachernameprefix'] = 'Awalan nama cohort dosen';
$string['setting_teachernameprefix_desc'] = 'Awalan yang ditambahkan ke nama kategori untuk membentuk nama cohort dosen. Contoh: Dosen - Magister Manajemen.';
$string['setting_updatenames'] = 'Ikuti perubahan nama kategori';
$string['setting_updatenames_desc'] = 'Jika aktif, nama cohort otomatis ikut diperbarui saat nama kategori course diubah.';
$string['setting_archiveondeleted'] = 'Arsipkan cohort saat kategori dihapus';
$string['setting_archiveondeleted_desc'] = 'Jika aktif, cohort otomatis disembunyikan dan diberi awalan arsip ketika kategori terkait dihapus. Anggota cohort tidak dihapus.';
$string['setting_archiveprefix'] = 'Awalan arsip';
$string['setting_archiveprefix_desc'] = 'Awalan yang ditambahkan ke nama cohort saat kategori terkait dihapus.';
$string['setting_enrolheading'] = 'Cohort sync otomatis saat Mata Kuliah dibuat';
$string['setting_enrolheading_desc'] = 'Saat course baru dibuat di kategori Program Studi, plugin dapat otomatis menambahkan metode enrolment Cohort sync untuk cohort mahasiswa dan dosen kategori tersebut.';
$string['setting_autoenrolstudents'] = 'Otomatis enrol cohort mahasiswa sebagai Student';
$string['setting_autoenrolstudents_desc'] = 'Jika aktif, course baru otomatis mendapat Cohort sync dari cohort mahasiswa kategori course tersebut dengan role Student.';
$string['setting_autoenrolteachers'] = 'Otomatis enrol cohort dosen sebagai Teacher';
$string['setting_autoenrolteachers_desc'] = 'Jika aktif, course baru otomatis mendapat Cohort sync dari cohort dosen kategori course tersebut dengan role Teacher.';
$string['privacy:metadata'] = 'Plugin Otomasi Prodi Pascasarjana tidak menyimpan data pribadi. Plugin ini hanya membuat dan memperbarui cohort Moodle berdasarkan kategori course serta menambahkan Cohort sync ke course.';

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
$string['setting_enabled'] = 'Aktifkan otomasi cohort Program Studi';
$string['setting_enabled_desc'] = 'Jika aktif, Moodle akan membuat cohort otomatis setiap kali kategori course dibuat.';
$string['setting_nameprefix'] = 'Awalan nama cohort';
$string['setting_nameprefix_desc'] = 'Awalan yang ditambahkan ke nama kategori untuk membentuk nama cohort. Contoh: Mahasiswa - Magister Manajemen.';
$string['setting_updatenames'] = 'Ikuti perubahan nama kategori';
$string['setting_updatenames_desc'] = 'Jika aktif, nama cohort otomatis ikut diperbarui saat nama kategori course diubah.';
$string['setting_archiveondeleted'] = 'Arsipkan cohort saat kategori dihapus';
$string['setting_archiveondeleted_desc'] = 'Jika aktif, cohort otomatis disembunyikan dan diberi awalan arsip ketika kategori terkait dihapus. Anggota cohort tidak dihapus.';
$string['setting_archiveprefix'] = 'Awalan arsip';
$string['setting_archiveprefix_desc'] = 'Awalan yang ditambahkan ke nama cohort saat kategori terkait dihapus.';
$string['privacy:metadata'] = 'Plugin Otomasi Prodi Pascasarjana tidak menyimpan data pribadi. Plugin ini hanya membuat dan memperbarui cohort Moodle berdasarkan kategori course.';

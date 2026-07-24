# Integrasi Dinamis Seluruh Program Studi UNW

Dokumen ini melengkapi `docs/siakad-integration.md`. Cakupan Moodle tidak lagi dibatasi pada Pascasarjana atau daftar program studi yang ditulis di source code. Master program studi diambil secara dinamis dari API kampus.

## Endpoint bawaan

```text
https://panel-web.unw.ac.id/api/unw-program-studi
```

Konfigurasi tersedia di:

```text
Site administration → Plugins → Local plugins → Jembatan SIAKAD
```

Field yang digunakan:

- **Sumber data**: pilih `REST API`;
- **URL API program studi**: endpoint master program studi;
- **Bearer token API program studi**: opsional, tergantung proteksi endpoint;
- **Anggap respons sebagai snapshot lengkap**: nonaktif secara bawaan;
- **URL API gabungan SIAKAD**: opsional untuk mahasiswa, dosen, dan tagihan.

## Perilaku sinkronisasi

1. Scheduled task program studi berjalan pada menit `0,15,30,45` setiap jam.
2. Sistem meminta JSON dari endpoint program studi.
3. Respons dinormalisasi menjadi kontrak internal:

```json
{
  "id": "101",
  "kode": "S1-IF",
  "nama": "S1 Informatika",
  "aktif": true
}
```

4. Record baru ditambahkan ke `local_siakad_prodi`.
5. Record dengan source ID atau kode yang sama diperbarui tanpa duplikasi.
6. Mapping kategori Moodle yang sudah dibuat admin tetap dipertahankan.
7. Program studi aktif otomatis tersedia pada pilihan Restrict access Quiz.
8. Program studi baru tampil sebagai **Belum dipetakan** sampai admin memilih kategori Moodle.

## Bentuk respons yang didukung

Adapter menerima root array atau envelope umum berikut:

```json
{"data": [...]}
{"data": {"items": [...]}}
{"items": [...]}
{"results": [...]}
{"prodi": [...]}
{"program_studi": [...]}
{"programStudi": [...]}
```

Nama field umum yang diterima:

| Data internal | Contoh field API yang diterima |
|---|---|
| ID | `id`, `id_program_studi`, `id_prodi`, `program_studi_id`, `uuid` |
| Kode | `kode`, `kode_program_studi`, `kode_prodi`, `kd_prodi`, `code` |
| Nama | `nama`, `nama_program_studi`, `nama_prodi`, `name` |
| Aktif | `aktif`, `active`, `is_active`, `status_aktif`, `status` |

Bila kode tidak dikirim tetapi ID tersedia, ID dipakai sebagai kode internal. Item tanpa ID/kode atau tanpa nama ditolak dengan pesan validasi.

## Program studi baru

Ketika API menambahkan program studi baru:

1. program studi masuk pada sinkronisasi berikutnya;
2. tidak diperlukan perubahan source code atau upgrade plugin;
3. program studi muncul pada halaman **Pemetaan program studi SIAKAD**;
4. admin membuat atau memilih Course Category yang sesuai;
5. setelah pemetaan, rekonsiliasi dapat mengelola cohort mahasiswa dan role dosen;
6. dosen dapat memilih program studi tersebut pada Restrict access Quiz.

## Program studi yang hilang dari API

Opsi snapshot lengkap sengaja nonaktif secara bawaan. Aktifkan hanya setelah dipastikan endpoint selalu mengirim seluruh program studi.

Jika opsi aktif, program studi yang tidak lagi ada pada respons akan:

- diberi `aktif = 0`;
- tidak dihapus dari database;
- tidak muncul untuk konfigurasi restriction baru;
- tetap mempertahankan mapping dan riwayat course/Quiz lama.

Respons kosong tidak pernah digunakan untuk menonaktifkan seluruh program studi. Sinkronisasi akan gagal dengan pesan jelas.

## Sinkronisasi manual

Melalui Moodle:

```text
Site administration → Plugins → Local plugins → Sinkronisasi SIAKAD
```

Tombol **Sinkronkan sekarang** menjalankan endpoint program studi dan, bila dikonfigurasi, endpoint gabungan mahasiswa/dosen/tagihan.

Melalui CLI:

```bat
D:\laragon\bin\php\php-8.4.6-Win32-vs17-x64\php.exe public\local\siakadbridge\cli\sync_program_studies.php
```

Setelah program studi masuk, buka halaman pemetaan:

```text
Site administration → Plugins → Local plugins → Pemetaan program studi SIAKAD
```

Kemudian jalankan rekonsiliasi:

```bat
D:\laragon\bin\php\php-8.4.6-Win32-vs17-x64\php.exe public\local\siakadbridge\cli\reconcile.php
```

## Batas integrasi saat ini

Endpoint program studi hanya menyediakan master program studi. Agar gerbang ujian bekerja untuk seluruh kampus, endpoint gabungan atau adapter tambahan tetap harus menyediakan:

- user;
- mahasiswa dan program studinya;
- status mahasiswa;
- dosen dan program studinya;
- tagihan;
- tahun ajaran;
- semester;
- status pembayaran;
- jenis tagihan;
- penanda wajib/opsional.

Jangan mengaktifkan produksi hanya berdasarkan keberhasilan sinkronisasi program studi. UAT mahasiswa, dosen, tagihan, enrollment course, cohort, dan Quiz tetap diperlukan.

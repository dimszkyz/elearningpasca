# Integrasi SIAKAD dan Gerbang Ujian Moodle

Branch implementasi: `feature/siakad-complete-integration`.

## Komponen

1. `local_pascasync`
   - Plugin yang sudah ada di `main` dan bertugas membuat/memperbarui akun Moodle dari API Pasca.
   - Menyimpan mapping `source_id` Pasca ke user Moodle dan memberi `idnumber=pasca:{source_id}`.
   - Tidak mengatur role, cohort, tagihan, atau akses ujian.

2. `local_siakadbridge`
   - Menyimpan cache/salinan lokal data SIAKAD.
   - Menyediakan halaman admin untuk status tagihan dan pemetaan Prodi.
   - Mendukung impor REST API dan sinkronisasi terjadwal setiap 15 menit.
   - Menghubungkan user SIAKAD dengan user Moodle melalui mapping `local_pascasync`, `idnumber`, username, lalu email unik.
   - Menambahkan mahasiswa ke cohort Prodi dari `local_pascaprodi`.
   - Menghapus membership cohort lama bila mahasiswa pindah Prodi atau menjadi nonaktif.
   - Memberikan role Editing teacher pada kategori kepada dosen aktif pada kategori Prodi yang dipetakan.
   - Menghapus role yang sebelumnya diberikan plugin ketika dosen menjadi nonaktif atau pemetaan berubah.

3. `availability_siakadpaid`
   - Menambahkan kondisi **Tagihan dan program studi SIAKAD** pada `Restrict access`.
   - Dosen memilih Prodi, tahun ajaran, semester, dan opsional jenis tagihan.
   - Akses diberikan hanya kepada mahasiswa aktif pada Prodi yang sesuai dan seluruh tagihan wajib pada periode tersebut berstatus `lunas`.
   - Mahasiswa melihat keterangan lengkap mengenai Prodi, tahun ajaran, semester, jenis tagihan, serta alasan umum akses dapat ditolak.

## Tabel Moodle

Prefix instalasi Moodle ditambahkan otomatis. Dengan prefix `mdl_`, tabelnya:

- `mdl_local_siakad_user`
- `mdl_local_siakad_prodi`
- `mdl_local_siakad_mahasiswa`
- `mdl_local_siakad_dosen`
- `mdl_local_siakad_tagihan`
- `mdl_local_siakad_synclog`

Tabel `local_siakad_*` sengaja digunakan agar tidak bertabrakan dengan tabel inti Moodle `{user}`.

## Instalasi/upgrade lokal

```bat
cd /d "D:\Coding Job\Moodle\elearningpasca"
git fetch origin
git checkout feature/siakad-complete-integration
git pull origin feature/siakad-complete-integration
```

Jalankan upgrade dan bersihkan cache:

```bat
D:\laragon\bin\php\php-8.4.6-Win32-vs17-x64\php.exe admin\cli\upgrade.php --non-interactive
D:\laragon\bin\php\php-8.4.6-Win32-vs17-x64\php.exe admin\cli\purge_caches.php
```

Buka:

```text
http://elearningpasca.test/admin/index.php
```

Moodle akan memasang plugin baru atau meng-upgrade instalasi lama dari versi dummy sebelumnya.

Seed data pengujian dan buat akun Moodle dummy yang belum ada:

```bat
D:\laragon\bin\php\php-8.4.6-Win32-vs17-x64\php.exe public\local\siakadbridge\cli\seed_dummy.php --create-moodle-users
```

Seeder tidak menghapus akun Moodle yang sudah ada. Opsi `--reset` tersedia untuk lingkungan pengujian yang datanya memang boleh dibersihkan, tetapi tidak digunakan pada prosedur standar.

Selaraskan user, cohort, dan role:

```bat
D:\laragon\bin\php\php-8.4.6-Win32-vs17-x64\php.exe public\local\siakadbridge\cli\reconcile.php
```

## Provisioning akun Moodle

Pada produksi, jalankan sinkronisasi akun melalui `local_pascasync` terlebih dahulu. Setelah akun tersedia, `local_siakadbridge` menghubungkannya dengan urutan berikut:

1. `moodleuserid` yang sudah valid;
2. tabel mapping `local_pascasync_map` menggunakan `source_id` yang sama;
3. `mdl_user.idnumber = pasca:{source_id}`;
4. username yang sama;
5. email yang unik.

Karena itu, nilai `users[].id` pada payload SIAKAD sebaiknya sama dengan `source_id` user pada API Pasca. Untuk pengujian manual, akun dengan username/email yang sama masih didukung. `local_siakadbridge` tidak mengimpor atau menyimpan password.

## Program studi data dummy

| Kode | Program studi |
|---|---|
| `MKEP` | Magister Keperawatan |
| `KESMAS` | Kesehatan Masyarakat |
| `MP` | Manajemen Pendidikan |
| `HUKUM` | Hukum |

## Data pengujian utama

| Username | Program studi/status | Kondisi tagihan | Hasil yang diharapkan |
|---|---|---|---|
| `mhs001` | Magister Keperawatan, aktif | UKT lunas | dapat mengikuti ujian MKEP |
| `mhs002` | Magister Keperawatan, aktif | UKT belum lunas | ditolak |
| `mhs003` | Kesehatan Masyarakat, aktif | UKT lunas | ditolak untuk MKEP, diizinkan untuk KESMAS |
| `mhs004` | Magister Keperawatan, aktif | UKT lunas dan praktikum wajib belum lunas | ditolak bila semua jenis tagihan diperiksa |
| `mhs005` | Magister Keperawatan, aktif | UKT lunas dan tagihan opsional belum lunas | diizinkan |
| `mhs006` | Magister Keperawatan, cuti | UKT lunas | ditolak karena tidak aktif |
| `mhs007` | Magister Keperawatan, aktif | tagihan hanya semester ganjil | ditolak untuk semester genap |
| `mhs008` | Magister Keperawatan, aktif | tagihan wajib dibatalkan | ditolak karena tidak ada tagihan wajib aktif |
| `mhs009` | Manajemen Pendidikan, aktif | UKT lunas | diizinkan untuk MP, ditolak untuk HUKUM |
| `mhs010` | Magister Keperawatan, aktif | tidak memiliki tagihan | ditolak |
| `mhs011` | Hukum, aktif | UKT lunas | diizinkan untuk HUKUM, ditolak untuk KESMAS |

Akun dosen dummy tersedia sebagai `dsn001` sampai `dsn004`, masing-masing untuk MKEP, KESMAS, MP, dan HUKUM. Password bawaan hanya untuk akun baru yang dibuat seeder: `Dummy#2026!`.

## Pengaturan admin

Menu:

```text
Site administration → Plugins → Local plugins
```

Halaman yang tersedia:

- **Jembatan SIAKAD**: URL API, token, periode aktif, aturan tagihan, dan role dosen.
- **Kelola tagihan SIAKAD**: tambah/ubah status `lunas`, `belum_lunas`, atau `dibatalkan` tanpa mengedit database langsung.
- **Pemetaan prodi SIAKAD**: hubungkan kode Prodi dengan Course Category Moodle.
- **Sinkronisasi SIAKAD**: jalankan sinkronisasi manual dan lihat log.

## Mengatur ujian per Prodi dan periode

Pada Quiz Moodle:

1. buka **Edit settings**;
2. pada **Restrict access**, klik **Add restriction**;
3. pilih **Tagihan dan program studi SIAKAD**;
4. pilih Program Studi;
5. isi tahun ajaran;
6. pilih semester ganjil atau genap;
7. pilih jenis tagihan bila ujian hanya bergantung pada jenis tagihan tertentu;
8. simpan Quiz.

## Aturan akses ujian

Mode yang direkomendasikan:

```text
Semua tagihan wajib pada periode terpilih harus lunas
```

Keputusan akses menolak mahasiswa bila:

- akun Moodle tidak dapat dipetakan ke mahasiswa aktif;
- Prodi mahasiswa tidak sama dengan Prodi pada Quiz;
- tidak ada tagihan pada tahun ajaran dan semester yang dipilih;
- tidak ada tagihan yang ditandai wajib;
- minimal satu tagihan wajib belum berstatus `lunas`.

Tagihan opsional (`wajib = 0`) tidak memblokir ujian. Tagihan berstatus `dibatalkan` tidak dihitung sebagai tagihan yang harus dibayar.

Keterangan pada Quiz menjelaskan bahwa ujian hanya tersedia bagi mahasiswa aktif dari Prodi, tahun ajaran, dan semester yang dipilih dengan seluruh tagihan wajib lunas. Keterangan juga menyebutkan bahwa akses dapat ditolak karena akun belum terhubung, Prodi berbeda, periode tidak sesuai, data tagihan tidak ditemukan, atau masih terdapat tagihan wajib yang belum lunas.

## Matriks keputusan akses

| Kondisi mahasiswa | Hasil |
|---|---|
| aktif, Prodi sama, periode sama, seluruh tagihan wajib lunas | diizinkan |
| aktif, Prodi sama, tetapi ada tagihan wajib belum lunas | ditolak |
| aktif dan lunas, tetapi Prodi berbeda | ditolak |
| aktif dan Prodi sama, tetapi tahun ajaran/semester tidak memiliki tagihan | ditolak |
| mahasiswa cuti, lulus, nonaktif, atau akun tidak terhubung | ditolak |

## Format REST API

Endpoint harus mengembalikan HTTP 2xx dan JSON berikut. Properti dapat dibungkus dalam objek `data` atau berada langsung di root.

```json
{
  "prodi": [
    {"id":"10","kode":"MKEP","nama":"Magister Keperawatan","aktif":true,"categoryid":12}
  ],
  "users": [
    {"id":"7001","username":"mhs001","fullname":"Mahasiswa Satu","email":"mhs001@kampus.ac.id","role":"mahasiswa"}
  ],
  "mahasiswa": [
    {"id":"mhs-1","username":"mhs001","nim":"240001","nama":"Mahasiswa Satu","email":"mhs001@kampus.ac.id","prodi":"MKEP","status":"aktif"}
  ],
  "dosen": [
    {"id":"dsn-1","username":"dsn001","nidn":"001001","nama":"Dosen Satu","email":"dsn001@kampus.ac.id","prodi":"MKEP","status":"aktif"}
  ],
  "tagihan": [
    {"id":"bill-1","nim":"240001","kodetagihan":"UKT-2026-GENAP-240001","tahunajaran":"2026/2027","semester":"genap","jenis":"UKT","nominal":2500000,"status":"lunas","wajib":true,"paidat":"2026-07-17T10:00:00+07:00"}
  ]
}
```

Nilai status tagihan yang diterima:

- `lunas`
- `belum_lunas`
- `dibatalkan`

Nilai status mahasiswa yang diterima:

- `aktif`
- `cuti`
- `lulus`
- `nonaktif`

Nilai status dosen yang diterima:

- `aktif`
- `nonaktif`

`categoryid` merupakan data lokal Moodle. Bila properti tersebut tidak dikirim oleh API, pemetaan kategori yang dibuat admin dipertahankan. Demikian pula `moodleuserid`, `paidat`, `duedate`, dan `wajib` dipertahankan saat properti terkait tidak dikirim.

### Full snapshot

`fullsnapshot: true` hanya boleh dikirim bila payload berisi snapshot lengkap seluruh data. Record lokal yang tidak ada dalam payload akan dinonaktifkan atau tagihannya dibatalkan. Jangan memakai `fullsnapshot: true` pada endpoint incremental atau payload yang hanya berisi sebagian data.

## Cron

Cron Moodle harus berjalan agar sinkronisasi REST otomatis aktif:

```bat
D:\laragon\bin\php\php-8.4.6-Win32-vs17-x64\php.exe admin\cli\cron.php
```

Pada server Linux, jalankan cron Moodle setiap menit; task SIAKAD sendiri dijadwalkan setiap 15 menit. Lock Moodle mencegah dua sinkronisasi berjalan bersamaan.

## Pengujian

Verifikasi dummy lokal:

```bat
D:\laragon\bin\php\php-8.4.6-Win32-vs17-x64\php.exe public\local\siakadbridge\cli\verify_dummy.php
```

Hasil yang diharapkan:

```text
Summary: 15 passed, 0 failed.
All dummy exam-access scenarios behave as expected.
```

PHPUnit plugin:

```bat
vendor\bin\phpunit --testsuite local_siakadbridge_testsuite
vendor\bin\phpunit --testsuite availability_siakadpaid_testsuite
```

Pengujian mencakup mahasiswa lunas, belum lunas, salah Prodi pada beberapa kombinasi MKEP/KESMAS/MP/HUKUM, beberapa tagihan wajib, tagihan opsional, tagihan dibatalkan, serialisasi kondisi, impor REST idempoten, migrasi ID dummy ke ID sumber nyata, integrasi mapping `local_pascasync`, pelestarian waktu pembayaran/pemetaan kategori, dan pembersihan cohort mahasiswa nonaktif.

## Batas integrasi nyata

Kode sisi Moodle menyediakan kontrak REST, autentikasi bearer token, audit log, task terjadwal, penguncian sinkronisasi, dan rekonsiliasi akses. Aktivasi terhadap SIAKAD produksi tetap membutuhkan endpoint nyata yang mengikuti kontrak JSON di atas, URL server, token, serta keputusan kampus mengenai tahun ajaran/semester aktif dan jenis tagihan yang wajib.

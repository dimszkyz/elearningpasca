# Integrasi SIAKAD dan Gerbang Ujian Moodle

Branch implementasi: `feature/siakad-complete-integration`.

## Komponen

1. `local_siakadbridge`
   - Menyimpan cache/salinan lokal data SIAKAD.
   - Menyediakan halaman admin untuk status tagihan dan pemetaan Prodi.
   - Mendukung impor REST API dan sinkronisasi terjadwal setiap 15 menit.
   - Menghubungkan user SIAKAD dengan user Moodle berdasarkan `username`, lalu `email` bila unik.
   - Menambahkan mahasiswa ke cohort Prodi dari `local_pascaprodi`.
   - Memberikan role Editing teacher pada kategori kepada dosen aktif pada kategori Prodi yang dipetakan.

2. `availability_siakadpaid`
   - Menambahkan kondisi **Tagihan dan program studi SIAKAD** pada `Restrict access`.
   - Dosen memilih Prodi, tahun ajaran, semester, dan opsional jenis tagihan.
   - Akses diberikan hanya kepada mahasiswa aktif pada Prodi yang sesuai dan seluruh tagihan wajib pada periode tersebut berstatus `lunas`.

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
```

Buka:

```text
http://elearningpasca.test/admin/index.php
```

Moodle akan memasang plugin baru atau meng-upgrade instalasi lama dari versi dummy sebelumnya.

Seed data pengujian:

```bat
D:\laragon\bin\php\php-8.4.23-Win32-vs17-x64\php.exe public\local\siakadbridge\cli\seed_dummy.php --reset
```

Selaraskan user, cohort, dan role:

```bat
D:\laragon\bin\php\php-8.4.23-Win32-vs17-x64\php.exe public\local\siakadbridge\cli\reconcile.php
```

## Data pengujian

| Username | Prodi | Tagihan | Hasil ujian TI |
|---|---|---|---|
| `mhs001` | TI | lunas | dapat mengakses |
| `mhs002` | TI | belum_lunas | diblokir |
| `mhs003` | SI | lunas | diblokir untuk TI, dapat untuk SI |
| `dsn001` | TI | n/a | mendapat role kategori setelah Prodi dipetakan |

Buat user Moodle dengan username yang sama. Pemetaan tidak bergantung pada password SIAKAD.

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

## Aturan akses ujian

Mode yang direkomendasikan:

```text
Semua tagihan wajib pada periode terpilih harus lunas
```

Keputusan akses menolak mahasiswa bila:

- akun Moodle tidak dapat dipetakan ke mahasiswa aktif;
- Prodi tidak sama dengan Prodi pada Quiz;
- tidak ada tagihan pada periode terpilih;
- tidak ada tagihan yang ditandai wajib;
- minimal satu tagihan wajib belum berstatus `lunas`.

Tagihan opsional (`wajib = 0`) tidak memblokir ujian.

## Format REST API

Endpoint harus mengembalikan HTTP 2xx dan JSON berikut. Properti dapat dibungkus dalam objek `data` atau berada langsung di root.

```json
{
  "prodi": [
    {"id":"prodi-ti","kode":"TI","nama":"Teknologi Informasi","aktif":true,"categoryid":12}
  ],
  "users": [
    {"id":"user-1","username":"mhs001","fullname":"Mahasiswa Satu","email":"mhs001@kampus.ac.id","role":"mahasiswa"}
  ],
  "mahasiswa": [
    {"id":"mhs-1","username":"mhs001","nim":"240001","nama":"Mahasiswa Satu","email":"mhs001@kampus.ac.id","prodi":"TI","status":"aktif"}
  ],
  "dosen": [
    {"id":"dsn-1","username":"dsn001","nidn":"001001","nama":"Dosen Satu","email":"dsn001@kampus.ac.id","prodi":"TI","status":"aktif"}
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

## Cron

Cron Moodle harus berjalan agar sinkronisasi REST otomatis aktif:

```bat
D:\laragon\bin\php\php-8.4.23-Win32-vs17-x64\php.exe public\admin\cli\cron.php
```

Pada server Linux, jalankan cron Moodle setiap menit; task SIAKAD sendiri dijadwalkan setiap 15 menit.

## Pengujian

PHPUnit plugin:

```bat
vendor\bin\phpunit --testsuite local_siakadbridge_testsuite
vendor\bin\phpunit --testsuite availability_siakadpaid_testsuite
```

Pengujian mencakup mahasiswa lunas, belum lunas, salah Prodi, beberapa tagihan wajib, tagihan opsional, serialisasi kondisi, dan impor REST idempoten.

## Batas integrasi nyata

Kode sisi Moodle sudah menyediakan kontrak REST, autentikasi bearer token, audit log, task terjadwal, dan rekonsiliasi akses. Aktivasi terhadap SIAKAD produksi tetap membutuhkan endpoint nyata yang mengikuti kontrak JSON di atas, URL server, token, serta keputusan kampus mengenai tahun ajaran/semester aktif dan jenis tagihan yang wajib.

# Desain Akademik Native Moodle

Dokumen ini menjadi arah baru pengelolaan akademik E-Learning Pascasarjana tanpa plugin Manajemen Akademik custom yang besar.

## Prinsip utama

Gunakan fitur bawaan Moodle terlebih dahulu:

- **Course Category** = Program Studi / Prodi.
- **Course** = Mata Kuliah.
- **Cohort Mahasiswa** = daftar mahasiswa per Prodi.
- **Cohort Dosen** = daftar dosen per Prodi.
- **Cohort sync enrolment** = otomatis mendaftarkan mahasiswa/dosen Prodi ke Mata Kuliah.
- **Course creator** atau **Manager pada kategori** = hak Dosen untuk membuat Mata Kuliah di Prodi tertentu.
- **Group / Grouping** = pemisahan kelas atau Prodi di dalam satu Mata Kuliah bersama.
- **Restrict access** = pembatasan aktivitas seperti Quiz/Ujian berdasarkan group/grouping bila diperlukan.

Dengan model ini, Moodle tetap menjadi pusat pengelolaan Course, Category, Role, Cohort, dan Quiz. Plugin custom dibuat kecil sebagai pendamping otomasi, bukan pengganti fitur akademik bawaan Moodle.

## Plugin kecil: Otomasi Prodi Pascasarjana

Plugin `local_pascaprodi` mengurangi pekerjaan manual saat admin membuat kategori Prodi dan Mata Kuliah.

Saat kategori Course dibuat:

```text
Category: Magister Manajemen
```

plugin otomatis membuat dua cohort:

```text
Mahasiswa - Magister Manajemen
Dosen - Magister Manajemen
```

Cohort diberi idnumber stabil berbasis ID kategori:

```text
pasca:prodi-category:{categoryid}
pasca:prodi-category-teacher:{categoryid}
```

Perilaku plugin:

- Membuat cohort mahasiswa dan cohort dosen otomatis saat Course Category dibuat.
- Mengubah nama cohort otomatis saat nama Course Category diubah.
- Saat Category dihapus, cohort tidak ikut dihapus. Cohort hanya disembunyikan dan diberi awalan arsip agar anggota cohort tetap aman.
- Saat Course/Mata Kuliah baru dibuat di dalam kategori Prodi, plugin otomatis menambahkan Cohort sync untuk mahasiswa dan dosen kategori tersebut.
- Untuk kategori yang sudah ada sebelum plugin dipasang, jalankan CLI backfill:

```powershell
php local/pascaprodi/cli/sync_categories.php
```

- Untuk Course yang sudah ada sebelum fitur auto-enrol aktif, jalankan:

```powershell
php local/pascaprodi/cli/sync_course_enrolments.php
```

Pengaturan plugin ada di:

```text
Site administration → Plugins → Local plugins → Pasca Study Program automation
```

## Struktur kategori Prodi

Buat kategori Moodle untuk setiap Prodi:

```text
Magister Manajemen
Magister Hukum
Magister Sistem Informasi
```

Menu Moodle:

```text
Site administration → Courses → Manage courses and categories
```

Contoh struktur:

```text
Magister Manajemen
├── Manajemen Strategis
├── Metodologi Penelitian
└── Etika Bisnis

Magister Hukum
├── Hukum Administrasi Negara
├── Metodologi Penelitian Hukum
└── Teori Hukum

Magister Sistem Informasi
├── Manajemen Proyek TI
├── Data Mining
└── Tata Kelola TI
```

## Struktur cohort mahasiswa dan dosen

Setiap Prodi memiliki dua cohort otomatis dari `local_pascaprodi`.

Contoh:

```text
Mahasiswa - Magister Manajemen
Dosen - Magister Manajemen
Mahasiswa - Magister Hukum
Dosen - Magister Hukum
```

Menu Moodle:

```text
Site administration → Users → Accounts → Cohorts
```

Mahasiswa dimasukkan ke cohort mahasiswa sesuai Prodi aktifnya. Dosen dimasukkan ke cohort dosen sesuai Prodi tempat ia mengajar.

## Enrolment Mata Kuliah otomatis

Saat Mata Kuliah dibuat di kategori Prodi, plugin otomatis menambahkan enrolment method **Cohort sync** sesuai kategori.

Contoh:

```text
Course: Manajemen Strategis
Category: Magister Manajemen

Auto Cohort sync:
- Mahasiswa - Magister Manajemen → role Student
- Dosen - Magister Manajemen → role Teacher
```

Hasilnya:

```text
Mahasiswa yang ada di cohort Mahasiswa - Magister Manajemen otomatis menjadi Student di course.
Dosen yang ada di cohort Dosen - Magister Manajemen otomatis menjadi Teacher di course.
Mahasiswa/Dosen dari Prodi lain tidak otomatis masuk.
```

## Akses Dosen membuat Mata Kuliah

Dosen dapat diberi role pada level kategori Prodi, bukan harus menjadi site admin.

Menu Moodle:

```text
Site administration → Courses → Manage courses and categories
Pilih kategori Prodi → Assign roles
```

Role yang disarankan:

- **Course creator** bila dosen hanya perlu membuat Mata Kuliah.
- **Manager** pada kategori bila dosen/kaprodi perlu mengelola Course di Prodi tersebut secara lebih luas.

Contoh:

```text
Dosen A → Course creator pada kategori Magister Manajemen
Dosen B → Course creator pada kategori Magister Hukum
Kaprodi MM → Manager pada kategori Magister Manajemen
```

Dengan cara ini, Dosen tidak perlu diberi akses global sebagai site administrator.

## Course untuk beberapa Prodi

Moodle bawaan hanya menempatkan satu Course pada satu kategori utama. Jadi Course tetap punya satu kategori utama, misalnya:

```text
Course: Metodologi Penelitian
Primary category: Magister Manajemen
```

Jika Course harus dipakai beberapa Prodi, tambahkan Cohort sync tambahan untuk Prodi lain:

```text
Cohort sync tambahan:
- Mahasiswa - Magister Hukum → Student
- Dosen - Magister Hukum → Teacher
- Mahasiswa - Magister Sistem Informasi → Student
- Dosen - Magister Sistem Informasi → Teacher
```

Pengembangan berikutnya bisa berupa halaman kecil “Buat Mata Kuliah Bersama” untuk memilih beberapa Prodi sebelum Course dibuat, lalu plugin menambahkan semua Cohort sync secara otomatis.

## Ujian per Prodi

Jika satu Mata Kuliah hanya untuk satu Prodi, Quiz/Ujian cukup dibuat di Course tersebut.

Jika satu Mata Kuliah dipakai beberapa Prodi, gunakan Group/Grouping:

```text
Group MM
Group MH
Group MSI
```

Lalu batasi Quiz menggunakan:

```text
Quiz settings → Restrict access → Group / Grouping
```

Contoh:

```text
Quiz: UTS Metodologi Penelitian MM
Restrict access: hanya Group MM
```

## Integrasi masa depan

Plugin custom berikutnya hanya dibuat untuk kebutuhan yang tidak bisa ditangani fitur bawaan Moodle, misalnya:

1. Sinkronisasi mahasiswa dari sistem kampus ke Moodle user + cohort Prodi.
2. Sinkronisasi dosen dari sistem kampus ke cohort dosen + role Course creator / Manager pada kategori Prodi.
3. Sinkronisasi status tagihan/SPP/UKT.
4. Pembatasan ikut Quiz/Ujian berdasarkan status pembayaran.
5. Dashboard laporan akademik khusus Pascasarjana.

## Arah teknis berikutnya

Prioritas pengembangan berikutnya:

1. Rapikan struktur kategori Prodi di Moodle.
2. Gunakan `local_pascaprodi` agar cohort mahasiswa dan dosen dibuat otomatis dari kategori.
3. Rapikan SOP memasukkan mahasiswa/dosen ke cohort Prodi.
4. Buat SOP pemberian role Dosen pada kategori Prodi.
5. Buat SOP pembuatan Course bersama lintas Prodi.
6. Setelah alur native stabil, baru bangun plugin kecil berikutnya untuk sinkronisasi dan payment gate.

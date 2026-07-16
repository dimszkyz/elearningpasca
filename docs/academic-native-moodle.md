# Desain Akademik Native Moodle

Dokumen ini menjadi arah baru pengelolaan akademik E-Learning Pascasarjana tanpa plugin Manajemen Akademik custom yang besar.

## Prinsip utama

Gunakan fitur bawaan Moodle terlebih dahulu:

- **Course Category** = Program Studi / Prodi.
- **Course** = Mata Kuliah.
- **Cohort** = daftar mahasiswa per Prodi.
- **Cohort sync enrolment** = otomatis mendaftarkan mahasiswa Prodi ke Mata Kuliah.
- **Course creator** atau **Manager pada kategori** = hak Dosen untuk membuat Mata Kuliah di Prodi tertentu.
- **Group / Grouping** = pemisahan kelas atau Prodi di dalam satu Mata Kuliah bersama.
- **Restrict access** = pembatasan aktivitas seperti Quiz/Ujian berdasarkan group/grouping bila diperlukan.

Dengan model ini, Moodle tetap menjadi pusat pengelolaan Course, Category, Role, Cohort, dan Quiz. Plugin custom dibuat kecil sebagai pendamping otomasi, bukan pengganti fitur akademik bawaan Moodle.

## Plugin kecil: Otomasi Prodi Pascasarjana

Plugin `local_pascaprodi` hanya bertugas mengurangi pekerjaan manual saat admin membuat kategori Prodi.

Saat kategori Course dibuat:

```text
Category: Magister Manajemen
```

plugin otomatis membuat cohort:

```text
Mahasiswa - Magister Manajemen
```

Cohort diberi idnumber stabil berbasis ID kategori:

```text
pasca:prodi-category:{categoryid}
```

Perilaku plugin:

- Membuat cohort otomatis saat Course Category dibuat.
- Mengubah nama cohort otomatis saat nama Course Category diubah.
- Saat Category dihapus, cohort tidak ikut dihapus. Cohort hanya disembunyikan dan diberi awalan arsip agar anggota cohort tetap aman.
- Untuk kategori yang sudah ada sebelum plugin dipasang, jalankan CLI backfill:

```powershell
php local/pascaprodi/cli/sync_categories.php
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

## Struktur cohort mahasiswa

Setiap Prodi memiliki cohort mahasiswa. Dengan `local_pascaprodi`, cohort dibuat otomatis dari kategori Prodi.

Contoh:

```text
Mahasiswa - Magister Manajemen
Mahasiswa - Magister Hukum
Mahasiswa - Magister Sistem Informasi
```

Menu Moodle:

```text
Site administration → Users → Accounts → Cohorts
```

Mahasiswa hanya dimasukkan ke cohort sesuai Prodi aktifnya.

## Enrolment Mata Kuliah

Setiap Mata Kuliah menggunakan enrolment method **Cohort sync** sesuai Prodi.

Menu di dalam Course:

```text
Course → Participants → Enrolment methods → Add method → Cohort sync
```

Contoh:

```text
Course: Manajemen Strategis
Category: Magister Manajemen
Cohort sync: Mahasiswa - Magister Manajemen
```

Hasilnya, hanya mahasiswa yang menjadi anggota cohort Magister Manajemen yang otomatis terdaftar ke Mata Kuliah tersebut.

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
2. Sinkronisasi dosen dari sistem kampus ke role Course creator / Manager pada kategori Prodi.
3. Sinkronisasi status tagihan/SPP/UKT.
4. Pembatasan ikut Quiz/Ujian berdasarkan status pembayaran.
5. Dashboard laporan akademik khusus Pascasarjana.

## Arah teknis berikutnya

Prioritas pengembangan berikutnya:

1. Rapikan struktur kategori Prodi di Moodle.
2. Gunakan `local_pascaprodi` agar cohort Prodi dibuat otomatis dari kategori.
3. Rapikan SOP memasukkan mahasiswa ke cohort Prodi.
4. Buat SOP pemberian role Dosen pada kategori Prodi.
5. Buat SOP pembuatan Course dan Cohort sync.
6. Setelah alur native stabil, baru bangun plugin kecil berikutnya untuk sinkronisasi dan payment gate.

# local_siakaddummy

Local Moodle plugin that provides a dummy academic database for developing the future SIAKAD integration.

## Tables

- `local_siad_user`: source accounts and optional Moodle user mapping.
- `local_siad_mahasiswa`: student identity, NIM, programme, semester and academic status.
- `local_siad_prodi`: study programmes.
- `local_siad_tagihan`: student bills and exam-eligibility status.
- `local_siad_dosen`: lecturer identity and home programme.

The Moodle database prefix configured in `config.php` is added automatically.

## Administration

Open **Site administration > Plugins > Local plugins > Dummy SIAKAD**.

The page can:

1. Create deterministic demo records.
2. Link dummy accounts to existing Moodle users using a unique matching email address.
3. Mark demonstration bills as paid or unpaid.
4. Display the current dummy users, students, programmes, lecturers and bills.

## Demo records

The seed action creates three programmes, three students and one lecturer. The students deliberately have different billing states (`lunas`, `belum_bayar`, and `sebagian`) so quiz access can be tested.

The dummy source password is `Password123!`; only its bcrypt hash is stored. These records are for local integration testing only.

## Integration boundary

Quiz access code calls `local_siakaddummy\service`. When the real SIAKAD API is available, this service can be replaced by an API-backed implementation while preserving the quiz restriction behaviour.

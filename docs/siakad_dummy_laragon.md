# Dummy SIAKAD Integration for Moodle

Branch: `feature/siakad-dummy-integration`

This branch adds a local Moodle dummy integration for the planned SIAKAD bridge.
It does not change `main`.

## Scope

The stakeholder requested dummy data for these SIAKAD entities:

- user
- mahasiswa
- prodi
- tagihan
- dosen

Inside Moodle, the plugin creates safe table names with a `local_siakad_` prefix:

- `mdl_local_siakad_user`
- `mdl_local_siakad_mahasiswa`
- `mdl_local_siakad_prodi`
- `mdl_local_siakad_tagihan`
- `mdl_local_siakad_dosen`

The prefix avoids collision with Moodle's core `{user}` table.

The file `docs/siakad_dummy_schema.sql` contains a separate external database schema using the exact table names requested by the stakeholder. Use that only as a reference for the future real SIAKAD database.

## Moodle behavior added

Two Moodle plugins are added:

1. `local_siakadbridge`
   - Creates and manages dummy SIAKAD data.
   - Provides a CLI seeder for local development.

2. `availability_siakadpaid`
   - Adds a new Restrict access condition.
   - Teachers can set a Quiz to be available only when the student:
     - has a matching SIAKAD mahasiswa record,
     - belongs to the selected prodi,
     - has at least one `lunas` tagihan record.

## Laragon environment used by this project

Recommended local stack:

- Laragon path: `D:\laragon`
- PHP: `8.4.23`
- Database: `MySQL 8.4.3`
- Moodle URL: `http://elearningpasca.test`
- Moodle dataroot: `D:\laragon\moodledata-pasca-new`

## How to test locally

From your cloned repository:

```bat
git fetch origin
git checkout feature/siakad-dummy-integration
```

Open Moodle as admin:

```text
http://elearningpasca.test/admin/index.php
```

Moodle should detect the new plugins and create the dummy tables.

After plugin installation, seed dummy SIAKAD data from the Moodle project root:

```bat
D:\laragon\bin\php\php-8.4.23-Win32-vs17-x64\php.exe public\local\siakadbridge\cli\seed_dummy.php --reset
```

If your PHP folder differs, adjust the command accordingly.

## Dummy users for testing

The seeder creates SIAKAD dummy users with these usernames:

| Username | Prodi | Tagihan | Expected quiz access |
|---|---|---|---|
| `mhs001` | TI | lunas | Allowed for TI quiz |
| `mhs002` | TI | belum_lunas | Blocked |
| `mhs003` | SI | lunas | Allowed for SI quiz, blocked for TI quiz |

Create Moodle users with the same usernames (`mhs001`, `mhs002`, `mhs003`) to test the rule without manually filling `moodleuserid` in the dummy SIAKAD table.

## Teacher flow for setting quiz access

1. Login as teacher/admin.
2. Create or edit a Quiz activity.
3. Open **Restrict access**.
4. Add restriction **SIAKAD billing and study program**.
5. Select one prodi, for example `TI`, or select **Any active study program**.
6. Save.

Result:

- Student can open/attempt the quiz only when SIAKAD dummy data says the student has paid tagihan and belongs to the selected prodi.
- If tagihan is not `lunas`, the quiz is automatically blocked.

## Notes for real SIAKAD integration

Current implementation is intentionally local and dummy-first. For real SIAKAD integration, the manager class can be adjusted later to read from:

- external SIAKAD MySQL/MariaDB database,
- REST API,
- scheduled sync job into `local_siakad_*` tables.

Recommended production direction: keep Moodle restrictions reading from Moodle-local synced tables for speed and auditability, while a scheduled task synchronises from the real SIAKAD system.

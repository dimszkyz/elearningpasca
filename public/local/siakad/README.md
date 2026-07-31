# Dummy SIAKAD integration

This development-only Moodle plugin provides a local schema that can later be replaced by a real SIAKAD API integration.

## Tables

The SIAKAD master data lives in its own database, defined by `db/install_external.xml`:

- `local_siakad_user`: external identity linked to an optional Moodle user.
- `local_siakad_mahasiswa`: student profile, NIM, cohort year, and programme.
- `local_siakad_prodi`: study programme projection, linked to `local_pascaprodi_prodi` via `pascaprodiid`.
- `local_siakad_tagihan`: billing and payment status per student and period.
- `local_siakad_dosen`: lecturer profile and home programme.

One table stays in the Moodle database, in `db/install.xml`, because it is Moodle-side configuration with a foreign key to `quiz`:

- `local_siakad_quizprodi`: quiz-to-programme targeting and payment requirement.

Moodle already owns the canonical `user` table. The SIAKAD user table therefore uses the prefixed name `local_siakad_user` and links to Moodle through `moodleuserid`.

## Database connection

`\local_siakad\external_db::get()` returns the connection holding the SIAKAD tables, and every query against them goes through it. Cross-database references — `local_siakad_user.moodleuserid`, `local_siakad_prodi.pascaprodiid`, `local_siakad_quizprodi.prodiid` — carry no foreign key, since the two databases may sit on different servers.

Configure the connection under *Site administration > Plugins > Local plugins > Dummy SIAKAD integration*. Leaving the database type empty keeps the SIAKAD tables in the Moodle database, which is what a fresh single-database install gets.

To move an existing install onto a separate database:

```
# 1. Create an empty database, e.g. CREATE DATABASE siakad_dummy;
# 2. Fill in the connection settings in the plugin settings page.
# 3. Build the schema and check what will move.
php local/siakad/cli/setup_external_db.php --install --status

# 4. Copy the existing rows across, preserving IDs.
php local/siakad/cli/setup_external_db.php --migrate

# 5. Once verified, and after taking a backup, drop the old copies.
php local/siakad/cli/setup_external_db.php --purge-source
```

`--migrate` skips any target table that already holds rows, so it is safe to re-run.

## Programme model

Programmes are not owned by this plugin. `local_pascaprodi` owns the model:

- Prodi = a row in `local_pascaprodi_prodi`, synced from the UNW Program Studi API.
- Each programme gets a generated student cohort `Mahasiswa - <Prodi>` with idnumber `pasca:prodi:{prodiid}`.

`local_siakad_prodi` is a thin projection of that table. `prodi_sync` mirrors every active programme into it, keyed by `pascaprodiid`, reusing the source `code`. Programmes that disappear or are deactivated upstream leave their row deactivated rather than deleted, so historical mahasiswa, dosen, and quiz mappings keep resolving. When `local_pascaprodi` is absent the mirror is a no-op rather than deactivating everything.

The mirror runs from two places:

- Scheduled task `local_siakad\task\sync_prodi_task`, every six hours.
- CLI backfill:

```powershell
php local/siakad/cli/sync_prodi.php --period=2026-GANJIL
```

## Dummy records

Installation mirrors the existing `local_pascaprodi` programmes, then seeds one paid student, one student with an outstanding bill, and one lecturer for period `2026-GANJIL`. When no programme exists yet, a placeholder is created and relinked by name once a real programme appears. The dummy identities intentionally have `moodleuserid = NULL`.

For a manual test, update the dummy student row so `moodleuserid` points to an existing Moodle student account.

## Automatic exam access on payment

`local_siakad\billing::pay()` settles a bill and immediately calls `refresh_exam_access()`. When every bill in the period is settled, the linked Moodle user is added to the Prodi cohort; if the balance goes back outstanding, the membership is removed. Because `local_pascaprodi` already attaches Cohort sync to every course linked to that programme, a student who pays gains course and exam access with no manual enrolment step.

Re-evaluate everyone for a period with `billing::refresh_all('2026-GANJIL')`, or via the CLI `--period` flag above.

## Exam flow

The companion plugin `quizaccess_siakad` adds these fields to Quiz settings:

1. Target study programmes.
2. Billing period.
3. Require all bills to be fully paid.

A lecturer linked to an active `local_siakad_dosen` row only sees their own programme in the selector. Users with `moodle/site:config` see every active programme. When a lecturer saves an exam that also targets programmes outside their scope, those mappings are preserved rather than dropped.

When a student opens or starts a Quiz attempt, the access rule checks:

1. The Moodle account is linked to an active SIAKAD student.
2. The student's programme is one of the selected programmes.
3. When payment is required, at least one bill exists for the period and every bill is marked `paid` with `paidamount >= amount`.

Users with `mod/quiz:manage` bypass the rule so lecturers and managers can configure and preview the Quiz.

## Future real SIAKAD integration

Keep the table contract stable and replace dummy seeding with a scheduled/API synchronisation service. Use immutable external IDs, transactions, idempotent upserts, audit logs, and a payment status webhook or scheduled reconciliation.

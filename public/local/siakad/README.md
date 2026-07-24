# Dummy SIAKAD integration

This development-only Moodle plugin provides a local schema that can later be replaced by a real SIAKAD API integration.

## Tables

- `local_siakad_user`: external identity linked to an optional Moodle user.
- `local_siakad_mahasiswa`: student profile, NIM, cohort year, and programme.
- `local_siakad_prodi`: study programme master data.
- `local_siakad_tagihan`: billing and payment status per student and period.
- `local_siakad_dosen`: lecturer profile and home programme.
- `local_siakad_quizprodi`: quiz-to-programme targeting and payment requirement.

Moodle already owns the canonical `user` table. The dummy SIAKAD user table therefore uses the prefixed name `local_siakad_user` and links to Moodle through `moodleuserid`.

## Dummy records

Installation seeds three programmes, one paid student, one lecturer, and one paid UKT bill for period `2026-GANJIL`. The dummy identities intentionally have `moodleuserid = NULL`.

For a manual test, update the dummy student row so `moodleuserid` points to an existing Moodle student account.

## Exam flow

The companion plugin `quizaccess_siakad` adds these fields to Quiz settings:

1. Target study programmes.
2. Billing period.
3. Require all bills to be fully paid.

When a student opens or starts a Quiz attempt, the access rule checks:

1. The Moodle account is linked to an active SIAKAD student.
2. The student's programme is one of the selected programmes.
3. When payment is required, at least one bill exists for the period and every bill is marked `paid` with `paidamount >= amount`.

Users with `mod/quiz:manage` bypass the rule so lecturers and managers can configure and preview the Quiz.

## Future real SIAKAD integration

Keep the table contract stable and replace dummy seeding with a scheduled/API synchronisation service. Use immutable external IDs, transactions, idempotent upserts, audit logs, and a payment status webhook or scheduled reconciliation.

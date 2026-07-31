# Pasca Study Program automation

Moodle local plugin: `local_pascaprodi`.

## Model

Study Programs are plugin data, not course categories:

- **Study Program / Program Studi** = a row in `local_pascaprodi_prodi`, synced from the UNW Program Studi API
- **Course Category** = ordinary Moodle content structure, managed by hand
- **Course** = Mata Kuliah
- **Cohort** = one generated student cohort per Study Program, idnumber `pasca:prodi:{prodiid}`
- **Cohort sync** = automatic student enrolment into the courses of a Study Program

A course is linked to a Study Program by attaching that programme's student cohort as a Cohort sync enrolment method. That link is what enrols students, so the course can live in any category the site wants.

## Study Program sync

Go to:

`Site administration → Plugins → Local plugins → Sync All Study Programs`

The sync fetches the configured API, stores every record as a Study Program (`jenjang + ' ' + nama` as the name, `slug` as the code), and creates or refreshes the student cohort for each one. No course categories are created or modified.

A Study Program that disappears from the API is deactivated rather than deleted, so students, bills, and quiz mappings pointing at it keep resolving. Its student cohort is hidden and prefixed as archived when **Archive cohort when a Study Program is dropped** is enabled; cohort members are never deleted.

## Linking a course to Study Programs

The course settings form has a **Study Program** field below Course category. It appears both when adding a new course and when editing one.

Every selected Study Program gets its own Cohort sync enrolment method using the Student role:

- Study Program: `Magister Hukum`, `Magister Keperawatan`
- Resulting enrolment methods: one Cohort sync per Study Program, both using the Student role

When editing, the Study Programs already linked to the course are preselected. Clearing a selection removes that Cohort sync method again. Only the generated student cohorts are managed this way, so Cohort sync methods added by hand are never removed.

The field is optional. Leave it empty for a course that is not tied to any Study Program.

## Adding users

The core **Add a new user** form gains an **Access role and Study Program** section. The selected role is assigned at system level, and the user is added to the student cohort of every selected Study Program, which is what grants access to that programme's courses.

Lecturer permissions are handled with a system role such as `globalteacher` or Course creator. There is no per-programme role context, because Study Programs are not Moodle contexts.

## Migrating from the category model

Earlier versions created one root course category per Study Program, with a cohort idnumbered `pasca:prodi-category:{categoryid}`. Convert an existing site with:

```powershell
php local/pascaprodi/cli/migrate_prodi_categories.php
```

That is a dry run. It lists every legacy Prodi category, the courses inside it, and what would happen. To apply:

```powershell
php local/pascaprodi/cli/migrate_prodi_categories.php --target-category=1 --execute
```

Back up your database first.

The script upserts each category as a Study Program, re-keys its cohort to `pasca:prodi:{prodiid}` **in place** so cohort members and existing Cohort sync enrolment methods survive untouched, relinks `local_siakad_prodi` when that plugin is installed, moves any courses to `--target-category`, and then deletes the now-empty category. It refuses to delete a category that still holds courses.

`--target-category` is required whenever any Prodi category still contains courses.

## Settings

`Site administration → Plugins → Local plugins → Pasca Study Program automation`

- Enable/disable automation
- Student cohort name prefix
- Update student cohort names when Study Program names change
- Archive generated student cohorts when a Study Program is dropped
- Archive prefix
- Program Studi API URL and timeout

## Native academic flow

1. Admin runs the Study Program sync, which creates the programmes and their student cohorts.
2. Admin creates whatever course categories suit the site's content structure.
3. Admin adds students, selecting their Study Program on the user form.
4. Admin assigns a system role such as `globalteacher` to lecturers.
5. Lecturer creates a course and selects its Study Programs.
6. The plugin adds one Cohort sync method per selected Study Program.
7. Students only see and access courses where they are enrolled.

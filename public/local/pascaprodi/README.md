# Pasca Study Program automation

Moodle local plugin: `local_pascaprodi`.

This plugin keeps the academic structure Moodle-native:

- Course Category = Program Studi / Study Program
- Course = Mata Kuliah / Course
- Cohort = Mahasiswa per Program Studi / Students per Study Program
- Cohort sync = automatic enrolment into courses
- Course creator or Manager role = lecturer permission at category level

## What it automates

When a Moodle course category is created, this plugin automatically creates a matching system cohort.

Example:

- Category: `Magister Manajemen`
- Generated cohort: `Mahasiswa - Magister Manajemen`
- Cohort idnumber: `pasca:prodi-category:{categoryid}`

When the category name is updated, the generated cohort name is updated too.

When the category is deleted, the generated cohort is not deleted. It is hidden and renamed with the configured archive prefix, so cohort members are preserved.

## Existing categories

For categories that already existed before this plugin was installed, run:

```powershell
php local/pascaprodi/cli/sync_categories.php
```

## Settings

Go to:

`Site administration → Plugins → Local plugins → Pasca Study Program automation`

Available settings:

- Enable/disable automation
- Cohort name prefix
- Update cohort names when category names change
- Archive generated cohorts when categories are deleted
- Archive prefix

## Manual academic flow

1. Admin creates a Course Category for each Study Program.
2. The plugin creates the matching cohort automatically.
3. Admin adds students to the Study Program cohort.
4. Lecturer creates courses inside the Study Program category.
5. Course uses Cohort sync to enrol the matching Study Program cohort.
6. Students only see and access courses where they are enrolled.

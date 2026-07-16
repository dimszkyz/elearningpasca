# Pasca Study Program automation

Moodle local plugin: `local_pascaprodi`.

This plugin keeps the academic structure Moodle-native:

- Course Category = Program Studi / Study Program
- Course = Mata Kuliah / Course
- Cohort = Mahasiswa/Dosen per Program Studi
- Cohort sync = automatic enrolment into courses
- Course creator or Manager role = lecturer permission at category level

## What it automates

When a Moodle course category is created, this plugin automatically creates matching system cohorts.

Example:

- Category: `Magister Manajemen`
- Generated student cohort: `Mahasiswa - Magister Manajemen`
- Generated teacher cohort: `Dosen - Magister Manajemen`
- Student cohort idnumber: `pasca:prodi-category:{categoryid}`
- Teacher cohort idnumber: `pasca:prodi-category-teacher:{categoryid}`

When the category name is updated, the generated cohort names are updated too.

When the category is deleted, generated cohorts are not deleted. They are hidden and renamed with the configured archive prefix, so cohort members are preserved.

## Automatic course enrolment

When a Moodle course is created inside a Study Program category, the plugin automatically adds Cohort sync enrolment methods for that category:

- Student cohort → role `student`
- Teacher cohort → role `editingteacher`

Example:

- Category: `Magister Manajemen`
- Course: `Manajemen Strategis`
- Student cohort sync: `Mahasiswa - Magister Manajemen` as Student
- Teacher cohort sync: `Dosen - Magister Manajemen` as Teacher

This means users only need to be placed into the correct Prodi cohort. When new courses are created in that Prodi category, members are enrolled automatically.

## Existing categories and courses

For categories that already existed before this plugin was installed, run:

```powershell
php local/pascaprodi/cli/sync_categories.php
```

For courses that already existed before automatic course enrolment was enabled, run:

```powershell
php local/pascaprodi/cli/sync_course_enrolments.php
```

Limit to one category when needed:

```powershell
php local/pascaprodi/cli/sync_course_enrolments.php --categoryid=12
```

## Settings

Go to:

`Site administration → Plugins → Local plugins → Pasca Study Program automation`

Available settings:

- Enable/disable automation
- Student cohort name prefix
- Teacher cohort name prefix
- Update cohort names when category names change
- Archive generated cohorts when categories are deleted
- Archive prefix
- Automatically add student Cohort sync to new courses
- Automatically add teacher Cohort sync to new courses

## Native academic flow

1. Admin creates a Course Category for each Study Program.
2. The plugin creates matching student and teacher cohorts automatically.
3. Admin adds students to the Study Program student cohort.
4. Admin adds lecturers to the Study Program teacher cohort.
5. Lecturer/admin creates courses inside the Study Program category.
6. The plugin automatically adds Cohort sync to the course.
7. Students and teachers only access courses where their cohort is linked.

## Courses shared by multiple Study Programs

A Moodle course can only live in one primary course category. For a course shared by multiple Study Programs, add additional Cohort sync enrolment methods for the other Prodi cohorts, or use a future custom course creation helper that selects multiple Prodi cohorts before creating the course.

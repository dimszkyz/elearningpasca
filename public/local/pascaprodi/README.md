# Pasca Study Program automation

Moodle local plugin: `local_pascaprodi`.

This plugin keeps the academic structure Moodle-native:

- Course Category = Program Studi / Study Program
- Course = Mata Kuliah / Course
- Cohort = Mahasiswa per Program Studi / Students per Study Program
- Cohort sync = automatic student enrolment into courses
- System role or category role = lecturer permission

## What it automates

When a Moodle course category is created, this plugin automatically creates a matching **student** system cohort.

Example:

- Category: `Magister Manajemen`
- Generated cohort: `Mahasiswa - Magister Manajemen`
- Cohort idnumber: `pasca:prodi-category:{categoryid}`

When the category name is updated, the generated student cohort name is updated too.

When the category is deleted, the generated student cohort is not deleted. It is hidden and renamed with the configured archive prefix, so cohort members are preserved.

## Course enrolment automation

When a new Moodle course is created inside a Study Program category, the plugin automatically adds a Cohort sync enrolment method for the matching student cohort using the Student role.

Example:

- Course category: `Magister Hukum`
- Matching cohort: `Mahasiswa - Magister Hukum`
- Automatic enrolment method: `Cohort sync → Student`

Teacher/Dosen access is intentionally not managed by a teacher cohort. Use a system role such as `globalteacher`, Course creator, or a category-level Manager/Course creator assignment.

## Combined user helper

The plugin adds a helper page:

`Site administration → Plugins → Local plugins → Assign User, Role, and Cohort`

Use this page to:

1. Select a Moodle user.
2. Assign a system role such as `globalteacher` or Course creator.
3. Optionally add the user to a Study Program student cohort.

This avoids moving between separate role and cohort menus for common setup tasks.

## Existing categories

For categories that already existed before this plugin was installed, run:

```powershell
php local/pascaprodi/cli/sync_categories.php
```

## Existing courses

For courses that already existed before automatic Cohort sync was enabled, run:

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
- Update student cohort names when category names change
- Archive generated student cohorts when categories are deleted
- Archive prefix
- Automatically add student Cohort sync when a course is created

## Native academic flow

1. Admin creates a Course Category for each Study Program.
2. The plugin creates the matching student cohort automatically.
3. Admin adds students to the Study Program cohort.
4. Admin/lecturer assigns globalteacher or category role to lecturers.
5. Lecturer creates courses inside the Study Program category.
6. The plugin automatically adds Cohort sync for the category's student cohort.
7. Students only see and access courses where they are enrolled.

## Courses shared by multiple Study Programs

A Moodle course can only live in one primary course category. For a course shared by multiple Study Programs, add additional Cohort sync enrolment methods for the other Prodi cohorts, or use a future custom course creation helper that selects multiple Prodi cohorts before creating the course.

# quizaccess_siakad

Moodle Quiz access-rule plugin that allows a lecturer to restrict an exam by study programme and SIAKAD billing eligibility.

## Quiz settings

When editing a Quiz, the lecturer can:

1. Enable the SIAKAD restriction.
2. Select one or more eligible study programmes.
3. Require exam-related bills to be paid.
4. Filter the required bills by academic year, semester and billing type.

## Attempt decision

A student may start a new attempt only when:

- the Moodle account matches an active dummy SIAKAD user;
- the linked student record is active;
- the student's programme is selected for the quiz; and
- every matching active bill marked as an exam requirement has status `lunas`.

Teachers with Quiz management or preview permissions bypass the student restriction. Existing attempts are not interrupted if a billing status changes after the attempt has started.

## Dependency

This plugin requires `local_siakaddummy`. The local plugin is the current data provider and can later be replaced with an adapter to the real SIAKAD API.

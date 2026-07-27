<?php
// This file is part of Moodle - http://moodle.org/
$string['pluginname'] = 'SIAKAD billing and study program';
$string['title'] = 'SIAKAD billing and study program';
$string['description'] = 'Restrict access using the student study program, academic period, and SIAKAD billing status.';
$string['label_prodi'] = 'Study program';
$string['label_tahunajaran'] = 'Academic year';
$string['label_semester'] = 'Semester';
$string['label_jenis'] = 'Bill type';
$string['anyprodi'] = 'Any active study program';
$string['allbilltypes'] = 'All required bill types';
$string['configureddefault'] = 'configured default';
$string['semester_ganjil'] = 'Odd';
$string['semester_genap'] = 'Even';
$string['choose'] = 'Choose...';
$string['error_selectprodi'] = 'Select a study program.';
$string['requires'] = 'This exam is available only to active students in study program {$a->prodi}, academic year {$a->tahunajaran}, semester {$a->semester}, with {$a->jenis} fully paid. Access is denied when the Moodle account is not linked to a student, the study program differs, the academic period does not match, billing data is missing, or a required bill is still unpaid.';
$string['requires_not'] = 'This exam is available only when the following SIAKAD requirement is not met: study program {$a->prodi}, academic year {$a->tahunajaran}, semester {$a->semester}, bill type {$a->jenis}.';
$string['privacy:metadata'] = 'The SIAKAD availability condition stores only activity configuration and no personal data.';

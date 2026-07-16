<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * English strings for the SIAKAD paid availability condition.
 *
 * @package    availability_siakadpaid
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'SIAKAD paid billing';
$string['title'] = 'SIAKAD billing and study program';
$string['description'] = 'Require paid SIAKAD billing and a selected study program.';
$string['label_prodi'] = 'Study program';
$string['anyprodi'] = 'Any active study program';
$string['error_selectprodi'] = 'Select a study program or any active study program.';
$string['requires_lunas_anyprodi'] = 'You must have paid SIAKAD billing.';
$string['requires_lunas_prodi'] = 'You must have paid SIAKAD billing and be registered in study program {$a}.';
$string['requires_not_lunas_anyprodi'] = 'You must not have paid SIAKAD billing.';
$string['requires_not_lunas_prodi'] = 'You must not have paid SIAKAD billing for study program {$a}.';
$string['privacy:metadata'] = 'The availability condition reads dummy SIAKAD records to decide access but does not store additional personal data.';

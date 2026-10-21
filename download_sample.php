<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Download sample CSV file for bulk course copying.
 *
 * @package    tool_bulkcopycourse
 * @copyright  2024 Ferdiyan Deni <ferdiyantodeni@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

require_login();
require_capability('tool/bulkcopycourse:bulkcopy', context_system::instance());

$filename = 'sample_bulk_copy_course.csv';

$headers = [
    'category',
    'copyshortname',
    'fullname',
    'startdate',
    'enddate',
    'shortname',
    'enrols',
    'visible',
    'idnumber',
];

$sampledata = [
    [
        '6',
        'CLP FRM 06/08/2026-07/08/2026',
        'CLP for FRM/JS 06-07 October 2026',
        '06-10-2026 00:00:00',
        '07-10-2026 23:00:00',
        'CLP FRM 06/10/2026-07/10/2026',
        'manual',
        '1',
        '2026-CLP-23',
    ],
    [
        '20',
        'Pendalaman Materi HSE 10/09/2026-11/09/2026',
        'TFT Safety Role Based 13-14 October 2026',
        '13-10-2026 00:00:00',
        '14-10-2026 23:00:00',
        'TFT Safety Role Based 13/10/2026-14/10/2026',
        'manual',
        '1',
        '2026-TFT-21',
    ],
    [
        '20',
        'Pelatihan Operator Alat Berat batch 1',
        'Pelatihan Operator Alat Berat batch 2',
        '20-10-2026 08:00:00',
        '22-10-2026 17:00:00',
        'POAB-B2-OCT2026',
        'manual',
        '1',
        '2026-POAB-02',
    ],
];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');

// Output UTF-8 BOM so Microsoft Excel opens it correctly.
fputs($out, "\xEF\xBB\xBF");

// Write header row.
fputcsv($out, $headers);

// Write sample data rows.
foreach ($sampledata as $row) {
    fputcsv($out, $row);
}

fclose($out);
exit;

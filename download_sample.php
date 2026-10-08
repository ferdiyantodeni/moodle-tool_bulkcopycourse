<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

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
        '78',
        'ExpertProjMgmt 15/09/2026-16/09/2026',
        'AJ People Development 08-09 October 2026',
        '08-10-2026 00:00:00',
        '09-10-2026 23:00:00',
        'AJ_People_Development 08/10/2026-09/10/2026',
        'manual',
        '1',
        '2026-AJPD-01',
    ],
];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
fputcsv($output, $headers);

foreach ($sampledata as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;

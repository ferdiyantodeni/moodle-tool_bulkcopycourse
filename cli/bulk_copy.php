<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/clilib.php');

list($options, $unrecognized) = cli_get_params([
    'help' => false,
    'file' => '',
    'delimiter' => 'auto',
    'encoding' => 'UTF-8',
], [
    'h' => 'help',
    'f' => 'file',
]);

if ($options['help'] || empty($options['file'])) {
    $help = "Tool Bulk Copy Course CLI

Options:
-h, --help            Print out this help
-f, --file=PATH       Path to the CSV file to process
--delimiter=CHAR      CSV delimiter (auto, comma ',', semicolon ';')
--encoding=ENCODING   File encoding (default: UTF-8)

Example:
\$ php admin/tool/bulkcopycourse/cli/bulk_copy.php --file=/var/data/courses.csv
";
    echo $help;
    exit(0);
}

$filepath = $options['file'];
if (!file_exists($filepath)) {
    cli_error("File not found: {$filepath}");
}

cli_heading("Bulk Copy Course CLI Processor");
cli_writeln("Parsing file: {$filepath}");

$parsed = \tool_bulkcopycourse\helper::parse_csv_file($filepath, $options['delimiter'], $options['encoding']);

if (!empty($parsed['errors'])) {
    foreach ($parsed['errors'] as $err) {
        cli_problem("Error: {$err}");
    }
    exit(1);
}

$validated = \tool_bulkcopycourse\helper::validate_rows($parsed['rows']);
$total = count($validated);
cli_writeln("Found {$total} courses to process.");

$success = 0;
$failed = 0;

foreach ($validated as $item) {
    cli_writeln("-----------------------------------------------------------------");
    cli_writeln("Line {$item->linenumber}: Master [{$item->copyshortname}] -> New [{$item->shortname}]");

    if ($item->status === 'error') {
        cli_problem("Validation Failed: " . implode('; ', $item->messages));
        $failed++;
        continue;
    }

    try {
        $admin = get_admin();
        $adminid = $admin ? $admin->id : 2;
        $newcourseid = \tool_bulkcopycourse\course_copier::copy_course($item, $adminid);
        cli_writeln("SUCCESS: Created course ID {$newcourseid} ('{$item->fullname}')");
        $success++;
    } catch (\Throwable $e) {
        cli_problem("COPY ERROR: " . $e->getMessage());
        $failed++;
    }
}

cli_writeln("=================================================================");
cli_heading("Finished: {$success} succeeded, {$failed} failed.");
exit($failed > 0 ? 1 : 0);

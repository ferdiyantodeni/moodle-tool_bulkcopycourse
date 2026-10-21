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
 * Command-line interface for bulk course copying.
 *
 * @package    tool_bulkcopycourse
 * @copyright  2024 Ferdiyan Deni <ferdiyantodeni@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        cli_problem("Validation error: {$err}");
    }
    cli_error("CSV parsing failed.");
}

$rows = $parsed['rows'];
cli_writeln("Found " . count($rows) . " rows. Validating...");

$validated = \tool_bulkcopycourse\helper::validate_rows($rows);

$ready = 0;
$errors = 0;
foreach ($validated as $v) {
    if ($v->status === 'error') {
        $errors++;
        cli_problem("Line {$v->linenumber} [{$v->copyshortname} -> {$v->shortname}]: " . implode('; ', $v->messages));
    } else {
        $ready++;
    }
}

cli_writeln("Validation summary: {$ready} ready, {$errors} error(s).");

if ($ready === 0) {
    cli_error("No courses ready to copy.");
}

$admin = get_admin();
$userid = $admin ? $admin->id : 2;

cli_heading("Starting copy operations...");

foreach ($validated as $item) {
    if ($item->status === 'error') {
        cli_writeln("Skipping Line {$item->linenumber} ({$item->shortname}) due to validation errors.");
        continue;
    }

    cli_write("Copying Line {$item->linenumber}: [{$item->copyshortname}] -> [{$item->shortname}]... ");
    try {
        $newcourseid = \tool_bulkcopycourse\course_copier::copy_course($item, $userid);
        cli_writeln("[SUCCESS] Course ID: {$newcourseid}");
    } catch (\Throwable $e) {
        cli_writeln("[FAILED] " . $e->getMessage());
    }
}

cli_heading("Bulk copy operations finished.");
exit(0);

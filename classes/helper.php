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
 * Helper utilities for bulk course copying.
 *
 * @package    tool_bulkcopycourse
 * @copyright  2024 Ferdiyan Deni <ferdiyantodeni@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_bulkcopycourse;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for bulk course copying.
 */
class helper {

    /**
     * Required header columns.
     */
    public const REQUIRED_COLUMNS = [
        'category',
        'copyshortname',
        'fullname',
        'shortname',
    ];

    /**
     * Optional header columns.
     */
    public const OPTIONAL_COLUMNS = [
        'startdate',
        'enddate',
        'visible',
        'idnumber',
        'enrols',
    ];

    /**
     * Map of Indonesian month names and abbreviations to English.
     */
    protected const INDO_MONTHS = [
        'januari' => 'January',
        'pebruari' => 'February',
        'februari' => 'February',
        'maret' => 'March',
        'april' => 'April',
        'mei' => 'May',
        'juni' => 'June',
        'juli' => 'July',
        'agustus' => 'August',
        'september' => 'September',
        'oktober' => 'October',
        'nopember' => 'November',
        'november' => 'November',
        'desember' => 'December',
        'agt' => 'Aug',
        'okt' => 'Oct',
        'nop' => 'Nov',
        'des' => 'Dec',
    ];

    /**
     * Parse date string to unix timestamp (Moodle's required format).
     *
     * @param string|int|float $datestr Raw input date string or number.
     * @param bool $isenddate If true and no time is provided, defaults time to 23:59:59 instead of 00:00:00.
     * @return int Unix timestamp or 0 if invalid.
     */
    public static function parse_datetime($datestr, bool $isenddate = false): int {
        if ($datestr === null || $datestr === '') {
            return 0;
        }

        // 1. Check if it's numeric (Unix timestamp or Excel serial).
        if (is_numeric($datestr)) {
            $num = (float)$datestr;
            // Existing Unix timestamp (e.g. > 100,000,000, roughly year 1973+)
            if ($num > 100000000) {
                return (int)$num;
            }
            // Excel serial date (e.g. 20000 to 70000 covers years ~1954 to 2091)
            if ($num >= 10000 && $num <= 90000) {
                // Excel base date: Dec 30 1899 (25569 days to Jan 1 1970).
                $unix = ($num - 25569) * 86400;
                return (int)round($unix);
            }
        }

        $datestr = trim((string)$datestr);
        if ($datestr === '') {
            return 0;
        }

        // 2. Remove multiple spaces and normalize separators.
        $clean = preg_replace('/\s+/', ' ', $datestr);

        // 3. Replace Indonesian month names with English.
        foreach (self::INDO_MONTHS as $indo => $eng) {
            $clean = preg_replace('/\b' . preg_quote($indo, '/') . '\b/i', $eng, $clean);
        }

        // 4. Check for standard d/m/Y or d-m-Y formats with or without time.
        // Format A: dd/mm/yyyy hh:mm:ss (or dd-mm-yyyy, dd.mm.yyyy)
        if (preg_match('/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?(?:\s*(am|pm))?)?$/i', $clean, $m)) {
            $day = (int)$m[1];
            $month = (int)$m[2];
            $year = (int)$m[3];

            $hour = isset($m[4]) ? (int)$m[4] : ($isenddate ? 23 : 0);
            $min = isset($m[5]) ? (int)$m[5] : ($isenddate ? 59 : 0);
            $sec = isset($m[6]) && $m[6] !== '' ? (int)$m[6] : ($isenddate ? 59 : 0);

            if (isset($m[7]) && !empty($m[7])) {
                $ampm = strtolower($m[7]);
                if ($ampm === 'pm' && $hour < 12) {
                    $hour += 12;
                } else if ($ampm === 'am' && $hour === 12) {
                    $hour = 0;
                }
            }

            if (checkdate($month, $day, $year)) {
                $ts = mktime($hour, $min, $sec, $month, $day, $year);
                if ($ts !== false && $ts > 0) {
                    return $ts;
                }
            }
        }

        // Format B: yyyy-mm-dd hh:mm:ss (or yyyy/mm/dd)
        if (preg_match('/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})(?:\s+(\d{1,2}):(\d{1,2})(?::(\d{1,2}))?(?:\s*(am|pm))?)?$/i', $clean, $m)) {
            $year = (int)$m[1];
            $month = (int)$m[2];
            $day = (int)$m[3];

            $hour = isset($m[4]) ? (int)$m[4] : ($isenddate ? 23 : 0);
            $min = isset($m[5]) ? (int)$m[5] : ($isenddate ? 59 : 0);
            $sec = isset($m[6]) && $m[6] !== '' ? (int)$m[6] : ($isenddate ? 59 : 0);

            if (isset($m[7]) && !empty($m[7])) {
                $ampm = strtolower($m[7]);
                if ($ampm === 'pm' && $hour < 12) {
                    $hour += 12;
                } else if ($ampm === 'am' && $hour === 12) {
                    $hour = 0;
                }
            }

            if (checkdate($month, $day, $year)) {
                $ts = mktime($hour, $min, $sec, $month, $day, $year);
                if ($ts !== false && $ts > 0) {
                    return $ts;
                }
            }
        }

        // 5. Try strtotime() for textual dates (e.g. "06 Oct 2026", "2026-10-06T14:30:00")
        $ts = strtotime($clean);
        if ($ts !== false && $ts > 0) {
            // If user did not supply a time component and this is an end date, adjust to end of day.
            if ($isenddate && !preg_match('/\d{1,2}:\d{2}/', $clean)) {
                $ts = strtotime(date('Y-m-d 23:59:59', $ts));
            }
            return $ts;
        }

        return 0;
    }

    /**
     * Auto-detect CSV delimiter (comma, semicolon, or tab).
     *
     * @param string $content
     * @return string
     */
    public static function detect_delimiter(string $content): string {
        $firstline = strtok($content, "\r\n");
        if ($firstline === false) {
            return ',';
        }

        $semicolons = substr_count($firstline, ';');
        $commas = substr_count($firstline, ',');
        $tabs = substr_count($firstline, "\t");

        if ($semicolons > $commas && $semicolons > $tabs) {
            return ';';
        }
        if ($tabs > $commas && $tabs > $semicolons) {
            return "\t";
        }
        return ',';
    }

    /**
     * Clean BOM and convert encoding to UTF-8.
     *
     * @param string $content
     * @param string $encoding
     * @return string
     */
    public static function clean_content(string $content, string $encoding = 'UTF-8'): string {
        // Strip UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        // Convert encoding if not UTF-8
        if (strtoupper($encoding) !== 'UTF-8' && function_exists('mb_convert_encoding')) {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        return $content;
    }

    /**
     * Parse CSV content and extract validated rows and headers.
     *
     * @param string $content Raw CSV string.
     * @param string $delimiter 'auto', ',', ';', or "\t".
     * @param string $encoding 'UTF-8', 'ISO-8859-1', etc.
     * @return array Array containing 'headers', 'rows', and 'errors'.
     */
    public static function parse_csv_content(string $content, string $delimiter = 'auto', string $encoding = 'UTF-8'): array {
        $content = self::clean_content($content, $encoding);

        if ($delimiter === 'auto') {
            $delimiter = self::detect_delimiter($content);
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return [
                'headers' => [],
                'rows' => [],
                'errors' => [get_string('err_filenotfound', 'tool_bulkcopycourse')],
            ];
        }

        // Parse header
        $rawheader = array_shift($lines);
        $stream = fopen('php://memory', 'r+');
        fputs($stream, $rawheader);
        rewind($stream);
        $headers = fgetcsv($stream, 0, $delimiter);
        fclose($stream);

        if (!$headers) {
            return [
                'headers' => [],
                'rows' => [],
                'errors' => [get_string('err_filenotfound', 'tool_bulkcopycourse')],
            ];
        }

        // Normalize header strings (lowercase, trim, strip BOM)
        $cleanheaders = [];
        foreach ($headers as $h) {
            $clean = strtolower(trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h)));
            $cleanheaders[] = $clean;
        }

        // Check required columns
        $missing = [];
        foreach (self::REQUIRED_COLUMNS as $col) {
            if (!in_array($col, $cleanheaders, true)) {
                $missing[] = $col;
            }
        }

        if (!empty($missing)) {
            return [
                'headers' => $cleanheaders,
                'rows' => [],
                'errors' => [get_string('err_missingcolumns', 'tool_bulkcopycourse', implode(', ', $missing))],
            ];
        }

        $rows = [];
        $linenumber = 1; // line 1 was header

        foreach ($lines as $line) {
            $linenumber++;
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $stream = fopen('php://memory', 'r+');
            fputs($stream, $line);
            rewind($stream);
            $values = fgetcsv($stream, 0, $delimiter);
            fclose($stream);

            if (!$values || count($values) < 2) {
                continue;
            }

            $rowdata = new \stdClass();
            $rowdata->linenumber = $linenumber;

            foreach ($cleanheaders as $idx => $headername) {
                $val = isset($values[$idx]) ? trim($values[$idx]) : '';
                $rowdata->$headername = $val;
            }

            $rows[] = $rowdata;
        }

        return [
            'headers' => $cleanheaders,
            'rows' => $rows,
            'errors' => [],
        ];
    }

    /**
     * Parse CSV file directly from filesystem path.
     *
     * @param string $filepath
     * @param string $delimiter
     * @param string $encoding
     * @return array
     */
    public static function parse_csv_file(string $filepath, string $delimiter = 'auto', string $encoding = 'UTF-8'): array {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            return [
                'headers' => [],
                'rows' => [],
                'errors' => [get_string('err_filenotfound', 'tool_bulkcopycourse')],
            ];
        }

        $content = file_get_contents($filepath);
        return self::parse_csv_content($content, $delimiter, $encoding);
    }

    /**
     * Validate an array of parsed row objects.
     *
     * @param array $rows
     * @return array Array of validated objects with 'status' and 'messages' properties.
     */
    public static function validate_rows(array $rows): array {
        global $DB;

        $validatedrows = [];
        $seennewshortnames = [];

        foreach ($rows as $row) {
            $item = clone $row;
            $errors = [];
            $warnings = [];

            // 1. Validate Category
            $categoryid = (int)$item->category;
            if ($categoryid <= 0 || !$DB->record_exists('course_categories', ['id' => $categoryid])) {
                $errors[] = get_string('err_categorynotfound', 'tool_bulkcopycourse', $item->category);
            }

            // 2. Validate Source Course (copyshortname)
            $sourcecourse = $DB->get_record('course', ['shortname' => $item->copyshortname]);
            if (!$sourcecourse) {
                $errors[] = get_string('err_sourcecoursenotfound', 'tool_bulkcopycourse', $item->copyshortname);
                $item->sourcecourseid = 0;
            } else {
                $item->sourcecourseid = $sourcecourse->id;
                $item->sourceshortname = $sourcecourse->shortname;
            }

            // 3. Validate Target Fullname
            if (empty($item->fullname)) {
                $errors[] = get_string('err_fullnameempty', 'tool_bulkcopycourse');
            }

            // 4. Validate Target Shortname
            if (empty($item->shortname)) {
                $errors[] = get_string('err_shortnameempty', 'tool_bulkcopycourse');
            } else {
                // Check if already in DB
                if ($DB->record_exists('course', ['shortname' => $item->shortname])) {
                    $errors[] = get_string('err_shortnametaken', 'tool_bulkcopycourse', $item->shortname);
                }

                // Check for duplicates in current batch
                if (isset($seennewshortnames[$item->shortname])) {
                    $errors[] = get_string('err_duplicateinbatch', 'tool_bulkcopycourse', $item->shortname);
                } else {
                    $seennewshortnames[$item->shortname] = true;
                }
            }

            // 5. Parse Dates (supports all common date/time formats, Indonesian months, and Excel serial dates)
            $item->startdate_timestamp = self::parse_datetime($item->startdate ?? '', false);
            $item->enddate_timestamp = self::parse_datetime($item->enddate ?? '', true);

            if (!empty($item->startdate) && $item->startdate_timestamp === 0) {
                $warnings[] = get_string('err_invaliddate', 'tool_bulkcopycourse') . " (startdate: {$item->startdate})";
            }
            if (!empty($item->enddate) && $item->enddate_timestamp === 0) {
                $warnings[] = get_string('err_invaliddate', 'tool_bulkcopycourse') . " (enddate: {$item->enddate})";
            }
            if ($item->startdate_timestamp > 0 && $item->enddate_timestamp > 0 && $item->enddate_timestamp < $item->startdate_timestamp) {
                $warnings[] = get_string('err_enddate_before_startdate', 'tool_bulkcopycourse');
            }

            // 6. Visible
            $item->visible_val = isset($item->visible) && (string)$item->visible !== '' ? (int)$item->visible : 1;

            // 7. ID Number
            $item->idnumber_val = isset($item->idnumber) ? trim($item->idnumber) : '';

            // 8. Enrols
            $item->enrols_val = isset($item->enrols) ? trim($item->enrols) : 'manual';

            // Determine status
            if (!empty($errors)) {
                $item->status = 'error';
                $item->messages = $errors;
            } elseif (!empty($warnings)) {
                $item->status = 'warning';
                $item->messages = $warnings;
            } else {
                $item->status = 'ready';
                $item->messages = [];
            }

            $validatedrows[] = $item;
        }

        return $validatedrows;
    }

    /**
     * Get footer signature HTML.
     *
     * @return string
     */
    public static function get_footer_html(): string {
        $plugin = new \stdClass();
        $pluginfile = __DIR__ . '/../version.php';
        if (file_exists($pluginfile)) {
            include($pluginfile);
        }
        $versionstr = !empty($plugin->release) ? $plugin->release . ' (' . $plugin->version . ')' : (string)($plugin->version ?? '1.0.0');

        $authorlink = \html_writer::link(
            'https://gridiyans.my.id',
            'Ferdiyanto Deni Sanjaya (gridiyans.my.id)',
            ['target' => '_blank', 'rel' => 'noopener noreferrer', 'class' => 'text-muted font-weight-bold']
        );

        return \html_writer::div(
            get_string('version_info', 'tool_bulkcopycourse', ['version' => s($versionstr), 'author' => $authorlink]),
            'text-center text-muted small mt-4 pt-3 border-top'
        );
    }
}

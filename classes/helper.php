<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

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
     * Supports:
     * - dd/mm/yyyy hh:mm:ss, dd-mm-yyyy hh:mm:ss, dd.mm.yyyy hh:mm:ss
     * - dd/mm/yyyy hh:mm, dd-mm-yyyy hh:mm, dd.mm.yyyy hh:mm
     * - dd/mm/yyyy, dd-mm-yyyy, dd.mm.yyyy
     * - yyyy-mm-dd hh:mm:ss, yyyy-mm-dd
     * - yyyy/mm/dd hh:mm:ss, yyyy/mm/dd
     * - 12-hour format with AM/PM (e.g. 06/10/2026 02:30:00 PM)
     * - Textual months in English and Indonesian (e.g. "06 Oktober 2026 00:00:00", "06-Oct-2026")
     * - Excel date serial numbers (e.g. 46301 or 46301.958333)
     * - Existing Unix timestamps
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

        // 2. Translate Indonesian month names to English.
        $lowercased = strtolower($datestr);
        foreach (self::INDO_MONTHS as $idmonth => $enmonth) {
            if (strpos($lowercased, $idmonth) !== false) {
                $datestr = preg_replace('/\b' . preg_quote($idmonth, '/') . '\b/i', $enmonth, $datestr);
            }
        }

        // 3. Normalize multiple spaces.
        $datestr = preg_replace('/\s+/', ' ', $datestr);

        // 4. Try parsing with explicit known formats.
        $formats = [
            // Standard day-month-year 24h with seconds
            'd/m/Y H:i:s',
            'd-m-Y H:i:s',
            'd.m.Y H:i:s',

            // Day-month-year 24h without seconds
            'd/m/Y H:i',
            'd-m-Y H:i',
            'd.m.Y H:i',

            // Day-month-year 12h with AM/PM
            'd/m/Y h:i:s A',
            'd/m/Y h:i:s a',
            'd-m-Y h:i:s A',
            'd-m-Y h:i:s a',
            'd/m/Y h:i A',
            'd/m/Y h:i a',
            'd-m-Y h:i A',
            'd-m-Y h:i a',

            // ISO Year-month-day
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y/m/d H:i:s',
            'Y/m/d H:i',

            // Textual month formats (e.g. 06 Oct 2026 00:00:00, 6 October 2026)
            'd M Y H:i:s',
            'd F Y H:i:s',
            'd-M-Y H:i:s',
            'd-F-Y H:i:s',
            'd M Y H:i',
            'd F Y H:i',
            'd-M-Y H:i',
            'd-F-Y H:i',

            // Date only (day first)
            'd/m/Y',
            'd-m-Y',
            'd.m.Y',
            'd M Y',
            'd F Y',
            'd-M-Y',
            'd-F-Y',

            // Date only (year first)
            'Y-m-d',
            'Y/m/d',

            // US Month-day-year fallback
            'm/d/Y H:i:s',
            'm/d/Y H:i',
            'm-d-Y H:i:s',
            'm-d-Y H:i',
            'm/d/Y',
            'm-d-Y',
        ];

        foreach ($formats as $fmt) {
            $dt = \DateTime::createFromFormat($fmt, $datestr);
            // Verify createFromFormat parsed without errors or warnings
            $errors = \DateTime::getLastErrors();
            if ($dt !== false && empty($errors['warning_count']) && empty($errors['error_count'])) {
                // If format is date-only and this is an end date, set time to 23:59:59
                if ($isenddate && in_array($fmt, ['d/m/Y', 'd-m-Y', 'd.m.Y', 'd M Y', 'd F Y', 'd-M-Y', 'd-F-Y', 'Y-m-d', 'Y/m/d', 'm/d/Y', 'm-d-Y'], true)) {
                    $dt->setTime(23, 59, 59);
                }
                return $dt->getTimestamp();
            }
        }

        // 5. Fallback: strtotime with dot replacement.
        // PHP strtotime interprets dots as American format mm.dd.yy if year is 2 digits,
        // but with 4-digit years converting dots to dashes helps strtotime recognize dd-mm-yyyy.
        $normalized = str_replace('.', '-', $datestr);
        $ts = strtotime($normalized);
        if ($ts !== false && $ts > 0) {
            if ($isenddate && strpos($datestr, ':') === false) {
                // Set to end of day if no time was included
                $ts = strtotime('23:59:59', $ts);
            }
            return (int)$ts;
        }

        return 0;
    }

    /**
     * Detect CSV delimiter from content.
     *
     * @param string $firstline
     * @return string
     */
    public static function detect_delimiter(string $firstline): string {
        $delimiters = [',', ';', "\t", '|'];
        $counts = [];

        foreach ($delimiters as $delim) {
            $counts[$delim] = substr_count($firstline, $delim);
        }

        arsort($counts);
        $topdelim = key($counts);
        return ($counts[$topdelim] > 0) ? $topdelim : ',';
    }

    /**
     * Parse uploaded CSV file.
     *
     * @param string $filepath Path to CSV file.
     * @param string $delimiter Optional specified delimiter.
     * @param string $encoding File encoding.
     * @return array Array containing 'headers', 'rows', and 'errors'.
     */
    public static function parse_csv_file(string $filepath, string $delimiter = '', string $encoding = 'UTF-8'): array {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            return [
                'headers' => [],
                'rows' => [],
                'errors' => [get_string('err_filenotfound', 'tool_bulkcopycourse')],
            ];
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            return [
                'headers' => [],
                'rows' => [],
                'errors' => [get_string('err_filenotfound', 'tool_bulkcopycourse')],
            ];
        }

        // Convert encoding if needed.
        if (strtoupper($encoding) !== 'UTF-8') {
            $converted = @iconv($encoding, 'UTF-8//IGNORE', $content);
            if ($converted !== false) {
                $content = $converted;
            }
        }

        // Remove UTF-8 BOM if present.
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        // Normalize newlines.
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", trim($content));

        if (empty($lines)) {
            return [
                'headers' => [],
                'rows' => [],
                'errors' => ['File is empty.'],
            ];
        }

        $headerline = $lines[0];
        if (empty($delimiter) || $delimiter === 'auto') {
            $delimiter = self::detect_delimiter($headerline);
        }

        $rawheaders = str_getcsv($headerline, $delimiter);
        $headers = [];
        foreach ($rawheaders as $h) {
            $headers[] = strtolower(trim($h));
        }

        // Check required columns.
        $missing = [];
        foreach (self::REQUIRED_COLUMNS as $req) {
            if (!in_array($req, $headers, true)) {
                $missing[] = $req;
            }
        }

        if (!empty($missing)) {
            return [
                'headers' => $headers,
                'rows' => [],
                'errors' => [get_string('err_missingcolumns', 'tool_bulkcopycourse', implode(', ', $missing))],
            ];
        }

        $rows = [];
        $linenumber = 1;

        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }
            $linenumber++;

            $data = str_getcsv($line, $delimiter);
            if (count($data) === 1 && $data[0] === null) {
                continue;
            }

            $rowdata = new \stdClass();
            $rowdata->linenumber = $linenumber;

            foreach ($headers as $colidx => $colname) {
                $val = isset($data[$colidx]) ? trim($data[$colidx]) : '';
                $rowdata->$colname = $val;
            }

            $rows[] = $rowdata;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'errors' => [],
        ];
    }

    /**
     * Validate course rows against Moodle database.
     *
     * @param array $rows
     * @return array Array of validated rows with status and error messages.
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
                $warnings[] = 'Tanggal selesai mendahului tanggal mulai (enddate < startdate)';
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
            'Version ' . s($versionstr) . ' &bull; Developed by ' . $authorlink,
            'text-center text-muted small mt-4 pt-3 border-top'
        );
    }
}


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
 * External service to copy a single course item.
 *
 * @package    tool_bulkcopycourse
 * @copyright  2024 Ferdiyan Deni <ferdiyantodeni@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_bulkcopycourse\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

// Compatibility fallback for older Moodle versions if core_external namespace does not exist.
if (!class_exists('core_external\external_api', true)) {
    class_alias('external_api', 'core_external\external_api');
    class_alias('external_function_parameters', 'core_external\external_function_parameters');
    class_alias('external_value', 'core_external\external_value');
    class_alias('external_single_structure', 'core_external\external_single_structure');
}

/**
 * External service class to execute copying of a single course item.
 */
class copy_course_item extends external_api {

    /**
     * Parameter description for execute.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'itemid' => new external_value(PARAM_INT, 'ID of the bulk copy course item to copy', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute course copying for a single item.
     *
     * @param int $itemid
     * @return array
     */
    public static function execute(int $itemid): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'itemid' => $itemid,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('tool/bulkcopycourse:bulkcopy', $context);

        $item = $DB->get_record('tool_bulkcopycourse_items', ['id' => $params['itemid']]);
        if (!$item) {
            return [
                'success' => false,
                'itemid' => $params['itemid'],
                'linenumber' => 0,
                'shortname' => '',
                'newcourseid' => 0,
                'courseurl' => '',
                'error' => get_string('err_itemnotfound', 'tool_bulkcopycourse'),
            ];
        }

        $job = $DB->get_record('tool_bulkcopycourse_jobs', ['id' => $item->jobid]);
        if ($job && $job->status === 'pending') {
            $job->status = 'processing';
            $job->timemodified = time();
            $DB->update_record('tool_bulkcopycourse_jobs', $job);
        }

        $item->status = 'processing';
        $item->timemodified = time();
        $DB->update_record('tool_bulkcopycourse_items', $item);

        try {
            $newcourseid = \tool_bulkcopycourse\course_copier::copy_course($item, (int)$USER->id);

            $item->status = 'success';
            $item->newcourseid = $newcourseid;
            $item->errormessage = null;
            $item->timemodified = time();
            $DB->update_record('tool_bulkcopycourse_items', $item);

            if ($job) {
                $job->completedcourses++;
                $job->timemodified = time();
                $DB->update_record('tool_bulkcopycourse_jobs', $job);
            }

            $courseurl = (new \moodle_url('/course/view.php', ['id' => $newcourseid]))->out(false);

            return [
                'success' => true,
                'itemid' => (int)$item->id,
                'linenumber' => (int)$item->linenumber,
                'shortname' => (string)$item->shortname,
                'newcourseid' => (int)$newcourseid,
                'courseurl' => $courseurl,
                'error' => '',
            ];
        } catch (\Throwable $e) {
            $item->status = 'error';
            $item->errormessage = $e->getMessage();
            $item->timemodified = time();
            $DB->update_record('tool_bulkcopycourse_items', $item);

            if ($job) {
                $job->failedcourses++;
                $job->timemodified = time();
                $DB->update_record('tool_bulkcopycourse_jobs', $job);
            }

            return [
                'success' => false,
                'itemid' => (int)$item->id,
                'linenumber' => (int)$item->linenumber,
                'shortname' => (string)$item->shortname,
                'newcourseid' => 0,
                'courseurl' => '',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Return description for execute.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the copy succeeded'),
            'itemid' => new external_value(PARAM_INT, 'Item ID'),
            'linenumber' => new external_value(PARAM_INT, 'Line number in CSV'),
            'shortname' => new external_value(PARAM_TEXT, 'Target shortname'),
            'newcourseid' => new external_value(PARAM_INT, 'Created course ID or 0', VALUE_DEFAULT, 0),
            'courseurl' => new external_value(PARAM_RAW, 'Direct link to the new course', VALUE_DEFAULT, ''),
            'error' => new external_value(PARAM_RAW, 'Error message if failed', VALUE_DEFAULT, ''),
        ]);
    }
}

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
 * External service to finalize a bulk copy job.
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
 * External service class to finalize a job status.
 */
class finish_job extends external_api {

    /**
     * Parameter description for execute.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'jobid' => new external_value(PARAM_INT, 'ID of the bulk copy job to finalize', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute job finalization.
     *
     * @param int $jobid
     * @return array
     */
    public static function execute(int $jobid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'jobid' => $jobid,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('tool/bulkcopycourse:bulkcopy', $context);

        $job = $DB->get_record('tool_bulkcopycourse_jobs', ['id' => $params['jobid']]);
        if ($job) {
            $job->status = ($job->failedcourses > 0 && $job->completedcourses === 0) ? 'failed' : 'completed';
            $job->timemodified = time();
            $DB->update_record('tool_bulkcopycourse_jobs', $job);
        }

        return [
            'success' => true,
        ];
    }

    /**
     * Return description for execute.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Operation result status'),
        ]);
    }
}

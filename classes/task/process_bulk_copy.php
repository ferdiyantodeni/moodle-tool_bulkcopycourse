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
 * Ad-hoc background task processor for bulk course copying.
 *
 * @package    tool_bulkcopycourse
 * @copyright  2024 Ferdiyan Deni <ferdiyantodeni@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_bulkcopycourse\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Ad-hoc task to process bulk course copy in the background.
 */
class process_bulk_copy extends \core\task\adhoc_task {

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_process_bulk_copy', 'tool_bulkcopycourse');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        $jobid = !empty($data->jobid) ? (int)$data->jobid : 0;
        $userid = !empty($data->userid) ? (int)$data->userid : 0;

        if (!$jobid) {
            mtrace("Bulk Copy Course: No job ID provided.");
            return;
        }

        $job = $DB->get_record('tool_bulkcopycourse_jobs', ['id' => $jobid]);
        if (!$job) {
            mtrace("Bulk Copy Course: Job #{$jobid} not found.");
            return;
        }

        mtrace("Bulk Copy Course: Starting Job #{$jobid} ({$job->totalcourses} courses)...");

        $job->status = 'processing';
        $job->timemodified = time();
        $DB->update_record('tool_bulkcopycourse_jobs', $job);

        $items = $DB->get_records('tool_bulkcopycourse_items', ['jobid' => $jobid], 'linenumber ASC');

        foreach ($items as $item) {
            if ($item->status !== 'pending') {
                continue;
            }

            mtrace(" -> Copying Line {$item->linenumber}: [{$item->copyshortname}] -> [{$item->shortname}]...");

            $item->status = 'processing';
            $item->timemodified = time();
            $DB->update_record('tool_bulkcopycourse_items', $item);

            try {
                $newcourseid = \tool_bulkcopycourse\course_copier::copy_course($item, $userid);

                $item->status = 'success';
                $item->newcourseid = $newcourseid;
                $item->errormessage = null;
                $item->timemodified = time();
                $DB->update_record('tool_bulkcopycourse_items', $item);

                $job->completedcourses++;
                $job->timemodified = time();
                $DB->update_record('tool_bulkcopycourse_jobs', $job);

                mtrace("    [OK] Created Course ID: {$newcourseid}");
            } catch (\Throwable $e) {
                $item->status = 'error';
                $item->errormessage = $e->getMessage();
                $item->timemodified = time();
                $DB->update_record('tool_bulkcopycourse_items', $item);

                $job->failedcourses++;
                $job->timemodified = time();
                $DB->update_record('tool_bulkcopycourse_jobs', $job);

                mtrace("    [FAILED] " . $e->getMessage());
            }
        }

        $job->status = ($job->failedcourses > 0 && $job->completedcourses === 0) ? 'failed' : 'completed';
        $job->timemodified = time();
        $DB->update_record('tool_bulkcopycourse_jobs', $job);

        mtrace("Bulk Copy Course: Job #{$jobid} finished. Completed: {$job->completedcourses}, Failed: {$job->failedcourses}.");
    }
}

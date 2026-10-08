<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

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

        $items = $DB->get_records('tool_bulkcopycourse_items', [
            'jobid' => $jobid,
            'status' => 'pending',
        ], 'linenumber ASC');

        foreach ($items as $item) {
            mtrace("Processing line {$item->linenumber}: '{$item->shortname}' from source '{$item->copyshortname}'...");

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

                mtrace("Line {$item->linenumber} SUCCESS: New course ID {$newcourseid}");
            } catch (\Throwable $e) {
                $item->status = 'error';
                $item->errormessage = $e->getMessage();
                $item->timemodified = time();
                $DB->update_record('tool_bulkcopycourse_items', $item);

                $job->failedcourses++;
                $job->timemodified = time();
                $DB->update_record('tool_bulkcopycourse_jobs', $job);

                mtrace("Line {$item->linenumber} ERROR: " . $e->getMessage());
            }
        }

        // Finalize job status.
        $job->status = ($job->failedcourses > 0 && $job->completedcourses === 0) ? 'failed' : 'completed';
        $job->timemodified = time();
        $DB->update_record('tool_bulkcopycourse_jobs', $job);

        mtrace("Bulk Copy Course: Job #{$jobid} finished. Completed: {$job->completedcourses}, Failed: {$job->failedcourses}.");
    }
}

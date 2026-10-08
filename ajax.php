<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();
require_capability('tool/bulkcopycourse:bulkcopy', context_system::instance());

header('Content-Type: application/json; charset=utf-8');

$action = required_param('action', PARAM_ALPHAEXT);

if ($action === 'copy_single') {
    $itemid = required_param('itemid', PARAM_INT);
    $item = $DB->get_record('tool_bulkcopycourse_items', ['id' => $itemid]);

    if (!$item) {
        echo json_encode(['success' => false, 'error' => 'Item not found.']);
        exit;
    }

    $job = $DB->get_record('tool_bulkcopycourse_jobs', ['id' => $item->jobid]);
    if (!$job) {
        echo json_encode(['success' => false, 'error' => 'Job not found.']);
        exit;
    }

    // If job was pending, mark as processing
    if ($job->status === 'pending') {
        $job->status = 'processing';
        $job->timemodified = time();
        $DB->update_record('tool_bulkcopycourse_jobs', $job);
    }

    $item->status = 'processing';
    $item->timemodified = time();
    $DB->update_record('tool_bulkcopycourse_items', $item);

    try {
        $newcourseid = \tool_bulkcopycourse\course_copier::copy_course($item, $USER->id);

        $item->status = 'success';
        $item->newcourseid = $newcourseid;
        $item->errormessage = null;
        $item->timemodified = time();
        $DB->update_record('tool_bulkcopycourse_items', $item);

        $job->completedcourses++;
        $job->timemodified = time();
        $DB->update_record('tool_bulkcopycourse_jobs', $job);

        $courseurl = (new moodle_url('/course/view.php', ['id' => $newcourseid]))->out(false);

        echo json_encode([
            'success' => true,
            'itemid' => $item->id,
            'linenumber' => $item->linenumber,
            'shortname' => $item->shortname,
            'newcourseid' => $newcourseid,
            'courseurl' => $courseurl,
        ]);
        exit;
    } catch (\Throwable $e) {
        $item->status = 'error';
        $item->errormessage = $e->getMessage();
        $item->timemodified = time();
        $DB->update_record('tool_bulkcopycourse_items', $item);

        $job->failedcourses++;
        $job->timemodified = time();
        $DB->update_record('tool_bulkcopycourse_jobs', $job);

        echo json_encode([
            'success' => false,
            'itemid' => $item->id,
            'linenumber' => $item->linenumber,
            'shortname' => $item->shortname,
            'error' => $e->getMessage(),
        ]);
        exit;
    }
} else if ($action === 'finish_job') {
    $jobid = required_param('jobid', PARAM_INT);
    $job = $DB->get_record('tool_bulkcopycourse_jobs', ['id' => $jobid]);

    if ($job) {
        $job->status = ($job->failedcourses > 0 && $job->completedcourses === 0) ? 'failed' : 'completed';
        $job->timemodified = time();
        $DB->update_record('tool_bulkcopycourse_jobs', $job);
    }

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action.']);
exit;

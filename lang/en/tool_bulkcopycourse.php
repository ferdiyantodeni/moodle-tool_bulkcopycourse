<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Bulk Copy Courses';
$string['bulkcopycourse:bulkcopy'] = 'Bulk copy courses from templates';
$string['uploadfile'] = 'Course CSV File';
$string['uploadfile_help'] = 'Upload a CSV file containing courses to be copied. The file must contain columns: category, copyshortname, fullname, startdate, enddate, shortname, enrols, visible, idnumber.';
$string['delimiter'] = 'CSV Delimiter';
$string['encoding'] = 'File Encoding';
$string['previewtitle'] = 'Preview Bulk Course Copy';
$string['previewdesc'] = 'Please review the validated courses below before proceeding with the copy operation.';
$string['startcopy'] = 'Start Copying Courses';
$string['startcopy_background'] = 'Queue in Background (Ad-hoc Task)';
$string['download_sample'] = 'Download Sample CSV Template';
$string['status_ready'] = 'Ready';
$string['status_warning'] = 'Warning';
$string['status_error'] = 'Error';
$string['status_success'] = 'Success';
$string['status_processing'] = 'Processing';
$string['status_pending'] = 'Pending';
$string['status_failed'] = 'Failed';

// Table Columns
$string['col_linenumber'] = 'Line';
$string['col_copyshortname'] = 'Source Course (copyshortname)';
$string['col_fullname'] = 'New Full Name';
$string['col_shortname'] = 'New Short Name';
$string['col_category'] = 'Category ID';
$string['col_dates'] = 'Start / End Date';
$string['col_visible'] = 'Visible';
$string['col_idnumber'] = 'ID Number';
$string['col_enrols'] = 'Enrolment';
$string['col_status'] = 'Validation Status';
$string['col_actions'] = 'Actions';

// Validation messages
$string['err_filenotfound'] = 'Uploaded file could not be read.';
$string['err_missingcolumns'] = 'Required columns missing: {$a}';
$string['err_sourcecoursenotfound'] = 'Source course with shortname "{$a}" not found.';
$string['err_categorynotfound'] = 'Category ID {$a} does not exist.';
$string['err_shortnametaken'] = 'Shortname "{$a}" is already used by another course.';
$string['err_shortnameempty'] = 'Shortname cannot be empty.';
$string['err_fullnameempty'] = 'Fullname cannot be empty.';
$string['err_duplicateinbatch'] = 'Duplicate shortname "{$a}" found in this file.';
$string['err_invaliddate'] = 'Invalid date format.';

// Processing & Progress
$string['processing_title'] = 'Processing Bulk Course Copy';
$string['processing_desc'] = 'Copying courses in progress. Please do not close this window until complete.';
$string['copy_in_progress'] = 'Copying: {$a}';
$string['copy_success'] = 'Successfully created course: {$a}';
$string['copy_failed'] = 'Failed to copy: {$a}';
$string['all_completed'] = 'Bulk copy completed!';
$string['view_course'] = 'View Course';
$string['view_history'] = 'View Copy History';
$string['back_to_upload'] = 'Back to Upload';
$string['total_courses'] = 'Total Courses: {$a}';
$string['completed_courses'] = 'Completed: {$a}';
$string['failed_courses'] = 'Failed: {$a}';
$string['recent_jobs'] = 'Recent Bulk Copy Jobs';
$string['no_jobs_found'] = 'No previous jobs found.';
$string['job_id'] = 'Job #{$a}';
$string['created_date'] = 'Created Date';
$string['confirm_start'] = 'Are you sure you want to proceed with copying {$a} courses?';
$string['task_process_bulk_copy'] = 'Background Bulk Course Copy Processor';
$string['history'] = 'Job History';
$string['privacy:metadata:jobs'] = 'Information about bulk course copy jobs initiated by the user.';
$string['privacy:metadata:jobs:userid'] = 'The ID of the user who initiated the bulk copy job.';
$string['privacy:metadata:jobs:name'] = 'The name of the bulk copy job.';
$string['privacy:metadata:jobs:status'] = 'The execution status of the job.';
$string['privacy:metadata:jobs:totalcourses'] = 'The total number of courses in the job.';
$string['privacy:metadata:jobs:timecreated'] = 'The timestamp when the job was created.';
$string['privacy:metadata:jobs:timemodified'] = 'The timestamp when the job was last modified.';


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
 * English language strings for tool_bulkcopycourse.
 *
 * @package    tool_bulkcopycourse
 * @copyright  2024 Ferdiyan Deni <ferdiyantodeni@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Bulk Copy Courses';
$string['plugin_subtitle'] = 'Bulk duplicate courses from master templates (without copying old student data).';
$string['bulkcopycourse:bulkcopy'] = 'Bulk copy courses from templates';
$string['uploadfile'] = 'Course CSV File';
$string['uploadfile_help'] = 'Upload a CSV file containing courses to be copied. The file must contain columns: category, copyshortname, fullname, startdate, enddate, shortname, enrols, visible, idnumber.';
$string['delimiter'] = 'CSV Delimiter';
$string['encoding'] = 'File Encoding';
$string['autodetect'] = 'Auto-detect';
$string['comma'] = 'Comma (,)';
$string['semicolon'] = 'Semicolon (;)';
$string['tab'] = 'Tab (\\t)';
$string['previewtitle'] = 'Preview Bulk Course Copy';
$string['previewdesc'] = 'Please review the validated courses below before proceeding with the copy operation.';
$string['startcopy'] = 'Start Copying Courses';
$string['startcopy_live'] = 'Start Copying (Live with Progress Bar)';
$string['startcopy_background'] = 'Queue in Background (Ad-hoc Task)';
$string['download_sample'] = 'Download Sample CSV Template';
$string['status_ready'] = 'Ready';
$string['status_warning'] = 'Warning';
$string['status_error'] = 'Error';
$string['status_success'] = 'Success';
$string['status_processing'] = 'Processing';
$string['status_pending'] = 'Pending';
$string['status_failed'] = 'Failed';
$string['status_queued'] = 'Queued';

// Table Columns & CSV Headers
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
$string['col_totalcourses'] = 'Total Courses';
$string['header_jobid'] = 'Job ID';
$string['header_jobname'] = 'Job Name';
$string['header_totalcourses'] = 'Total Courses';
$string['created_by'] = 'Created By';
$string['created_date'] = 'Created Date';
$string['new_course_id'] = 'New Course ID';
$string['new_course_url'] = 'New Course URL';
$string['error_or_message'] = 'Error / Message';
$string['course_link'] = 'Course Link';
$string['course_list_in_job'] = 'Courses in this Job';

// CSV Information Box
$string['csv_format_title'] = 'CSV File Column Format';
$string['csv_format_desc'] = 'The uploaded CSV file must contain the following first-row column headers:';
$string['col_desc_category'] = 'Category ID where the new course will be placed';
$string['col_desc_copyshortname'] = 'Shortname of the master course to duplicate';
$string['col_desc_fullname'] = 'Full name for the new course';
$string['col_desc_shortname'] = 'Unique short name for the new course';
$string['col_desc_startdate'] = 'Course start date (e.g. 06-10-2026 00:00:00 or 2026-10-06)';
$string['col_desc_enddate'] = 'Course end date (e.g. 07-10-2026 23:00:00 or 2026-10-07)';
$string['col_desc_enrols'] = 'Enrolment method (manual, self, etc.)';
$string['col_desc_visible'] = 'Visibility (1 = show, 0 = hide)';
$string['col_desc_idnumber'] = 'Course identification number';
$string['required'] = 'Required';
$string['optional'] = 'Optional';

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
$string['err_enddate_before_startdate'] = 'End date is earlier than start date (enddate < startdate)';
$string['err_cannotcreatecourse'] = 'Could not create destination course record.';
$string['err_itemnotfound'] = 'Queue item not found.';

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
$string['queue_remaining'] = 'Remaining Queue';
$string['recent_jobs'] = 'Recent Bulk Copy Jobs';
$string['no_jobs_found'] = 'No previous jobs found.';
$string['job_id'] = 'Job #{$a}';
$string['confirm_start'] = 'Are you sure you want to proceed with copying {$a} courses?';
$string['task_process_bulk_copy'] = 'Background Bulk Course Copy Processor';
$string['history'] = 'Job History';
$string['history_list'] = 'History List';
$string['view_results_report'] = 'View Report & Copied Courses';
$string['copy_more_courses'] = 'Copy More Courses';
$string['download_results_csv'] = 'Download Results (CSV)';
$string['download_all_csv'] = 'Download All Jobs Summary (CSV)';
$string['delete_job'] = 'Delete This Job';
$string['confirm_delete_job'] = 'Are you sure you want to delete history for job #{$a}?';
$string['job_deleted_success'] = 'Job history #{$a} was successfully deleted.';
$string['cancel_job'] = 'Cancel / Delete';
$string['confirm_cancel_job'] = 'Are you sure you want to cancel this job?';
$string['job_cancelled_success'] = 'Job was successfully cancelled.';
$string['ready_to_copy'] = 'Ready to Copy';
$string['error_invalid'] = 'Error / Invalid';
$string['job_already_queued'] = 'This job is already queued or currently processing.';
$string['job_queued_success'] = 'Bulk copy job has been queued in background (Ad-hoc Task).';
$string['job_in_queue_desc'] = 'This job is currently in the background queue (Ad-hoc Task). Moodle cron will execute the duplication automatically in the background.';
$string['job_in_progress_desc'] = 'This job is currently being processed.';
$string['view_queue_status'] = 'View Queue Status & Results';
$string['warning_errors_in_batch'] = 'Warning: There are {$a} course row(s) with errors. Rows with ERROR status will be skipped automatically when copying.';
$string['queueing_in_progress'] = 'Queueing in background...';
$string['live_log_title'] = 'Real-time Copy Process Log:';
$string['preparing_log'] = '[{$a}] Preparing bulk course copy process...';
$string['preparing_items'] = 'Preparing {$a} courses to copy...';
$string['copying_line'] = 'Copying Line {$a->line}: master [{$a->source}] to [{$a->target}]...';
$string['copy_success_detail'] = 'Successfully created course ID: {$a->id} ({$a->shortname})';
$string['copy_failed_detail'] = 'Failed copying Line {$a->line} ({$a->shortname}): {$a->error}';
$string['network_error'] = 'Network/Server error on Line {$a->line}: {$a->error}';
$string['no_pending_courses'] = 'No pending courses to process in this job.';
$string['version_info'] = 'Version {$a->version} • Developed by {$a->author}';

// Privacy API
$string['privacy:metadata:jobs'] = 'Information about bulk course copy jobs initiated by the user.';
$string['privacy:metadata:jobs:userid'] = 'The ID of the user who initiated the bulk copy job.';
$string['privacy:metadata:jobs:name'] = 'The name of the bulk copy job.';
$string['privacy:metadata:jobs:status'] = 'The execution status of the job.';
$string['privacy:metadata:jobs:totalcourses'] = 'The total number of courses in the job.';
$string['privacy:metadata:jobs:timecreated'] = 'The timestamp when the job was created.';
$string['privacy:metadata:jobs:timemodified'] = 'The timestamp when the job was last modified.';

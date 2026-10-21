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
 * Preview page for validated courses before bulk copying.
 *
 * @package    tool_bulkcopycourse
 * @copyright  2024 Ferdiyan Deni <ferdiyantodeni@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

try {
    admin_externalpage_setup('tool_bulkcopycourse');
} catch (\Throwable $e) {
    require_login();
    require_capability('tool/bulkcopycourse:bulkcopy', context_system::instance());
}

$context = context_system::instance();
require_capability('tool/bulkcopycourse:bulkcopy', $context);

$jobid = required_param('jobid', PARAM_INT);
$job = $DB->get_record('tool_bulkcopycourse_jobs', ['id' => $jobid], '*', MUST_EXIST);
$items = $DB->get_records('tool_bulkcopycourse_items', ['jobid' => $jobid], 'linenumber ASC');

$url = new moodle_url('/admin/tool/bulkcopycourse/preview.php', ['jobid' => $jobid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('previewtitle', 'tool_bulkcopycourse'));
$PAGE->set_heading(get_string('previewtitle', 'tool_bulkcopycourse'));

// Check actions
$action = optional_param('action', '', PARAM_ALPHAEXT);

if ($action === 'queue_background' && confirm_sesskey()) {
    if ($job->status === 'queued' || $job->status === 'processing') {
        \core\notification::warning(get_string('job_already_queued', 'tool_bulkcopycourse'));
        redirect(new moodle_url('/admin/tool/bulkcopycourse/history.php', ['jobid' => $jobid]));
    }

    // Set job status to queued immediately to prevent double submissions.
    $job->status = 'queued';
    $job->timemodified = time();
    $DB->update_record('tool_bulkcopycourse_jobs', $job);

    $task = new \tool_bulkcopycourse\task\process_bulk_copy();
    $task->set_custom_data(['jobid' => $jobid, 'userid' => $USER->id]);
    \core\task\manager::queue_adhoc_task($task, true);

    \core\notification::success(get_string('job_queued_success', 'tool_bulkcopycourse'));
    redirect(new moodle_url('/admin/tool/bulkcopycourse/history.php', ['jobid' => $jobid]));
} else if ($action === 'delete_job' && confirm_sesskey()) {
    $DB->delete_records('tool_bulkcopycourse_items', ['jobid' => $jobid]);
    $DB->delete_records('tool_bulkcopycourse_jobs', ['id' => $jobid]);
    \core\notification::info(get_string('job_cancelled_success', 'tool_bulkcopycourse'));
    redirect(new moodle_url('/admin/tool/bulkcopycourse/index.php'));
}

echo $OUTPUT->header();

$readycount = 0;
$errorcount = 0;

foreach ($items as $item) {
    if ($item->status === 'error') {
        $errorcount++;
    } else {
        $readycount++;
    }
}

// Summary cards
echo '<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-light shadow-sm text-center py-3">
            <h6 class="text-muted mb-1">' . get_string('col_totalcourses', 'tool_bulkcopycourse') . '</h6>
            <h2 class="font-weight-bold mb-0">' . count($items) . '</h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white shadow-sm text-center py-3">
            <h6 class="text-white-50 mb-1">' . get_string('ready_to_copy', 'tool_bulkcopycourse') . '</h6>
            <h2 class="font-weight-bold mb-0">' . $readycount . '</h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card ' . ($errorcount > 0 ? 'bg-danger text-white' : 'bg-light text-muted') . ' shadow-sm text-center py-3">
            <h6 class="' . ($errorcount > 0 ? 'text-white-50' : 'text-muted') . ' mb-1">' . get_string('error_invalid', 'tool_bulkcopycourse') . '</h6>
            <h2 class="font-weight-bold mb-0">' . $errorcount . '</h2>
        </div>
    </div>
</div>';

// Action buttons
if ($job->status === 'queued') {
    echo $OUTPUT->notification(
        '<i class="fa fa-clock-o mr-1"></i> ' . get_string('job_in_queue_desc', 'tool_bulkcopycourse'),
        \core\output\notification::NOTIFY_INFO
    );
} else if ($job->status === 'processing') {
    echo $OUTPUT->notification(
        '<i class="fa fa-spinner fa-spin mr-1"></i> ' . get_string('job_in_progress_desc', 'tool_bulkcopycourse'),
        \core\output\notification::NOTIFY_INFO
    );
}

echo html_writer::start_div('mb-4 d-flex justify-content-between align-items-center flex-wrap');
echo html_writer::start_div('btn-toolbar mt-2 mt-md-0');

if ($readycount > 0) {
    if ($job->status === 'queued' || $job->status === 'processing') {
        echo html_writer::link(
            new moodle_url('/admin/tool/bulkcopycourse/history.php', ['jobid' => $jobid]),
            '<i class="fa fa-eye mr-1"></i> ' . get_string('view_queue_status', 'tool_bulkcopycourse'),
            ['class' => 'btn btn-info btn-lg mr-2']
        );
    } else {
        // Live Browser Copy button
        echo html_writer::link(
            new moodle_url('/admin/tool/bulkcopycourse/process.php', ['jobid' => $jobid]),
            '<i class="fa fa-play mr-1"></i> ' . get_string('startcopy_live', 'tool_bulkcopycourse'),
            ['class' => 'btn btn-primary btn-lg mr-2']
        );

        // Background Task button with double-click protection
        echo html_writer::link(
            new moodle_url('/admin/tool/bulkcopycourse/preview.php', [
                'jobid' => $jobid,
                'action' => 'queue_background',
                'sesskey' => sesskey(),
            ]),
            '<i class="fa fa-cogs mr-1"></i> ' . get_string('startcopy_background', 'tool_bulkcopycourse'),
            [
                'class' => 'btn btn-outline-info btn-lg mr-2',
                'id' => 'btn-queue-background',
                'onclick' => "this.classList.add('disabled'); this.style.pointerEvents='none'; this.innerHTML='<i class=\"fa fa-spinner fa-spin mr-1\"></i> " . addslashes_js(get_string('queueing_in_progress', 'tool_bulkcopycourse')) . "';",
            ]
        );
    }
}

echo html_writer::end_div();

echo html_writer::link(
    new moodle_url('/admin/tool/bulkcopycourse/preview.php', [
        'jobid' => $jobid,
        'action' => 'delete_job',
        'sesskey' => sesskey(),
    ]),
    '<i class="fa fa-trash mr-1"></i> ' . get_string('cancel_job', 'tool_bulkcopycourse'),
    ['class' => 'btn btn-outline-danger mt-2 mt-md-0', 'onclick' => 'return confirm("' . addslashes_js(get_string('confirm_cancel_job', 'tool_bulkcopycourse')) . '");']
);

echo html_writer::end_div();

if ($errorcount > 0) {
    echo $OUTPUT->notification(
        get_string('warning_errors_in_batch', 'tool_bulkcopycourse', $errorcount),
        \core\output\notification::NOTIFY_WARNING
    );
}

// Table of items
echo html_writer::start_div('card shadow-sm');
echo html_writer::start_div('card-header font-weight-bold bg-white');
echo '<i class="fa fa-table mr-1"></i> ' . get_string('previewdesc', 'tool_bulkcopycourse');
echo html_writer::end_div();
echo html_writer::start_div('card-body p-0');
echo html_writer::start_tag('div', ['class' => 'table-responsive']);
echo '<table class="table table-hover table-striped mb-0">';
echo '<thead class="thead-light">
        <tr>
            <th style="width: 50px;">' . get_string('col_linenumber', 'tool_bulkcopycourse') . '</th>
            <th>' . get_string('col_status', 'tool_bulkcopycourse') . '</th>
            <th>' . get_string('col_copyshortname', 'tool_bulkcopycourse') . '</th>
            <th>' . get_string('col_fullname', 'tool_bulkcopycourse') . '</th>
            <th>' . get_string('col_shortname', 'tool_bulkcopycourse') . '</th>
            <th>' . get_string('col_category', 'tool_bulkcopycourse') . '</th>
            <th>' . get_string('col_dates', 'tool_bulkcopycourse') . '</th>
            <th>' . get_string('col_idnumber', 'tool_bulkcopycourse') . '</th>
            <th>' . get_string('error_or_message', 'tool_bulkcopycourse') . '</th>
        </tr>
      </thead>
      <tbody>';

foreach ($items as $item) {
    $rowclass = '';
    $badge = '';

    if ($item->status === 'error') {
        $rowclass = 'table-danger';
        $badge = '<span class="badge badge-danger">' . get_string('status_error', 'tool_bulkcopycourse') . '</span>';
    } else {
        $badge = '<span class="badge badge-success">' . get_string('status_ready', 'tool_bulkcopycourse') . '</span>';
    }

    $startstr = !empty($item->startdate) ? userdate($item->startdate, '%d-%m-%Y %H:%M:%S') : '-';
    $endstr = !empty($item->enddate) ? userdate($item->enddate, '%d-%m-%Y %H:%M:%S') : '-';

    echo '<tr class="' . $rowclass . '">
            <td class="font-weight-bold text-center">' . $item->linenumber . '</td>
            <td>' . $badge . '</td>
            <td><code class="text-dark font-weight-bold">' . s($item->copyshortname) . '</code></td>
            <td>' . s($item->fullname) . '</td>
            <td><code>' . s($item->shortname) . '</code></td>
            <td>' . $item->category . '</td>
            <td class="small">' . $startstr . '<br><span class="text-muted">s/d</span> ' . $endstr . '</td>
            <td>' . s($item->idnumber) . '</td>
            <td class="small text-danger">' . s($item->errormessage) . '</td>
          </tr>';
}

echo '</tbody></table></div></div></div>';

echo \tool_bulkcopycourse\helper::get_footer_html();

echo $OUTPUT->footer();

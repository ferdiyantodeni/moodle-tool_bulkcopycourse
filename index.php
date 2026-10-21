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
 * Main index page and CSV upload form for bulk course copying.
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

$url = new moodle_url('/admin/tool/bulkcopycourse/index.php');
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'tool_bulkcopycourse'));
$PAGE->set_heading(get_string('pluginname', 'tool_bulkcopycourse'));

$form = new \tool_bulkcopycourse\form\upload_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/tool/bulkcopycourse/index.php'));
} else if ($data = $form->get_data()) {
    $filecontent = $form->get_file_content('coursefile');

    if (empty($filecontent)) {
        \core\notification::error(get_string('err_filenotfound', 'tool_bulkcopycourse'));
    } else {
        $delimiter = $data->delimiter ?? 'auto';
        $encoding = $data->encoding ?? 'UTF-8';

        $parsed = \tool_bulkcopycourse\helper::parse_csv_content($filecontent, $delimiter, $encoding);

        if (!empty($parsed['errors'])) {
            foreach ($parsed['errors'] as $err) {
                \core\notification::error($err);
            }
        } else if (empty($parsed['rows'])) {
            \core\notification::error(get_string('err_filenotfound', 'tool_bulkcopycourse'));
        } else {
            // Validate all rows
            $validatedrows = \tool_bulkcopycourse\helper::validate_rows($parsed['rows']);

            // Create a pending job
            $filename = $form->get_new_filename('coursefile');
            $job = new \stdClass();
            $job->name = !empty($filename) ? $filename : ('Job ' . date('Y-m-d H:i:s'));
            $job->userid = $USER->id;
            $job->status = 'pending';
            $job->totalcourses = count($validatedrows);
            $job->completedcourses = 0;
            $job->failedcourses = 0;
            $job->timecreated = time();
            $job->timemodified = time();

            $jobid = $DB->insert_record('tool_bulkcopycourse_jobs', $job);

            // Insert items into database
            foreach ($validatedrows as $row) {
                $item = new \stdClass();
                $item->jobid = $jobid;
                $item->linenumber = $row->linenumber;
                $item->category = (int)$row->category;
                $item->sourcecourseid = $row->sourcecourseid ?? 0;
                $item->copyshortname = $row->copyshortname;
                $item->fullname = $row->fullname;
                $item->shortname = $row->shortname;
                $item->idnumber = $row->idnumber_val ?? '';
                $item->startdate = $row->startdate_timestamp ?? 0;
                $item->enddate = $row->enddate_timestamp ?? 0;
                $item->visible = $row->visible_val ?? 1;
                $item->enrols = $row->enrols_val ?? 'manual';
                $item->status = ($row->status === 'error') ? 'error' : 'pending';
                $item->errormessage = !empty($row->messages) ? implode('; ', $row->messages) : null;
                $item->newcourseid = 0;
                $item->timecreated = time();
                $item->timemodified = time();

                $DB->insert_record('tool_bulkcopycourse_items', $item);
            }

            redirect(new moodle_url('/admin/tool/bulkcopycourse/preview.php', ['jobid' => $jobid]));
        }
    }
}

echo $OUTPUT->header();

// Action toolbar
echo html_writer::start_div('mb-4 d-flex justify-content-between align-items-center flex-wrap');
echo html_writer::div(
    html_writer::tag('h4', get_string('pluginname', 'tool_bulkcopycourse'), ['class' => 'm-0']) .
    html_writer::tag('p', get_string('plugin_subtitle', 'tool_bulkcopycourse'), ['class' => 'text-muted m-0'])
);
echo html_writer::start_div('btn-group mt-2 mt-md-0');
echo html_writer::link(
    new moodle_url('/admin/tool/bulkcopycourse/download_sample.php'),
    '<i class="fa fa-download mr-1"></i> ' . get_string('download_sample', 'tool_bulkcopycourse'),
    ['class' => 'btn btn-outline-primary']
);
echo html_writer::link(
    new moodle_url('/admin/tool/bulkcopycourse/history.php'),
    '<i class="fa fa-history mr-1"></i> ' . get_string('view_history', 'tool_bulkcopycourse'),
    ['class' => 'btn btn-outline-secondary']
);
echo html_writer::end_div();
echo html_writer::end_div();

// Instruction box
echo html_writer::start_div('card mb-4 border-info');
echo html_writer::start_div('card-header bg-info text-white font-weight-bold');
echo '<i class="fa fa-info-circle mr-1"></i> ' . get_string('csv_format_title', 'tool_bulkcopycourse');
echo html_writer::end_div();
echo html_writer::start_div('card-body');
echo html_writer::tag('p', get_string('csv_format_desc', 'tool_bulkcopycourse'));
echo html_writer::start_tag('div', ['class' => 'table-responsive']);
echo '<table class="table table-bordered table-sm mb-0">
    <thead class="thead-light">
        <tr>
            <th>Header CSV</th>
            <th>' . get_string('col_status', 'tool_bulkcopycourse') . '</th>
            <th>Keterangan / Format Contoh</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>category</code></td>
            <td><span class="badge badge-danger">' . get_string('required', 'tool_bulkcopycourse') . '</span></td>
            <td>' . get_string('col_desc_category', 'tool_bulkcopycourse') . '</td>
        </tr>
        <tr>
            <td><code>copyshortname</code></td>
            <td><span class="badge badge-danger">' . get_string('required', 'tool_bulkcopycourse') . '</span></td>
            <td>' . get_string('col_desc_copyshortname', 'tool_bulkcopycourse') . '</td>
        </tr>
        <tr>
            <td><code>fullname</code></td>
            <td><span class="badge badge-danger">' . get_string('required', 'tool_bulkcopycourse') . '</span></td>
            <td>' . get_string('col_desc_fullname', 'tool_bulkcopycourse') . '</td>
        </tr>
        <tr>
            <td><code>shortname</code></td>
            <td><span class="badge badge-danger">' . get_string('required', 'tool_bulkcopycourse') . '</span></td>
            <td>' . get_string('col_desc_shortname', 'tool_bulkcopycourse') . '</td>
        </tr>
        <tr>
            <td><code>startdate</code></td>
            <td><span class="badge badge-secondary">' . get_string('optional', 'tool_bulkcopycourse') . '</span></td>
            <td>' . get_string('col_desc_startdate', 'tool_bulkcopycourse') . '</td>
        </tr>
        <tr>
            <td><code>enddate</code></td>
            <td><span class="badge badge-secondary">' . get_string('optional', 'tool_bulkcopycourse') . '</span></td>
            <td>' . get_string('col_desc_enddate', 'tool_bulkcopycourse') . '</td>
        </tr>
        <tr>
            <td><code>enrols</code></td>
            <td><span class="badge badge-secondary">' . get_string('optional', 'tool_bulkcopycourse') . '</span></td>
            <td>' . get_string('col_desc_enrols', 'tool_bulkcopycourse') . '</td>
        </tr>
        <tr>
            <td><code>visible</code></td>
            <td><span class="badge badge-secondary">' . get_string('optional', 'tool_bulkcopycourse') . '</span></td>
            <td>' . get_string('col_desc_visible', 'tool_bulkcopycourse') . '</td>
        </tr>
        <tr>
            <td><code>idnumber</code></td>
            <td><span class="badge badge-secondary">' . get_string('optional', 'tool_bulkcopycourse') . '</span></td>
            <td>' . get_string('col_desc_idnumber', 'tool_bulkcopycourse') . '</td>
        </tr>
    </tbody>
</table>';
echo html_writer::end_tag('div');
echo html_writer::end_div();
echo html_writer::end_div();

// Render form
$form->display();

echo \tool_bulkcopycourse\helper::get_footer_html();

echo $OUTPUT->footer();

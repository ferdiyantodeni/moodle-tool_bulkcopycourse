<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

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
        // Save temporary file to parse
        $tempfile = make_temp_directory('bulkcopycourse') . '/' . uniqid('csv_', true) . '.csv';
        file_put_contents($tempfile, $filecontent);

        $delimiter = $data->delimiter ?? 'auto';
        $encoding = $data->encoding ?? 'UTF-8';

        $parsed = \tool_bulkcopycourse\helper::parse_csv_file($tempfile, $delimiter, $encoding);
        @unlink($tempfile);

        if (!empty($parsed['errors'])) {
            foreach ($parsed['errors'] as $err) {
                \core\notification::error($err);
            }
        } else if (empty($parsed['rows'])) {
            \core\notification::error('No valid data rows found in uploaded file.');
        } else {
            // Validate rows
            $validatedrows = \tool_bulkcopycourse\helper::validate_rows($parsed['rows']);

            // Create Job Record
            $job = new \stdClass();
            $job->userid = $USER->id;
            $job->name = 'Bulk Copy ' . userdate(time(), '%d %b %Y %H:%M');
            $job->status = 'pending';
            $job->totalcourses = count($validatedrows);
            $job->completedcourses = 0;
            $job->failedcourses = 0;
            $job->timecreated = time();
            $job->timemodified = time();

            $jobid = $DB->insert_record('tool_bulkcopycourse_jobs', $job);

            // Insert Items
            foreach ($validatedrows as $row) {
                $item = new \stdClass();
                $item->jobid = $jobid;
                $item->linenumber = $row->linenumber;
                $item->category = (int)$row->category;
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
echo html_writer::start_div('mb-4 d-flex justify-content-between align-items-center');
echo html_writer::div(
    html_writer::tag('h4', get_string('pluginname', 'tool_bulkcopycourse'), ['class' => 'm-0']) .
    html_writer::tag('p', 'Salin kursus baru secara massal dari template master (tanpa menyertakan data peserta lama).', ['class' => 'text-muted m-0'])
);
echo html_writer::start_div('btn-group');
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
echo '<i class="fa fa-info-circle mr-1"></i> Format Kolom File CSV';
echo html_writer::end_div();
echo html_writer::start_div('card-body');
echo html_writer::tag('p', 'File CSV yang diunggah harus memiliki format header baris pertama persis seperti tabel berikut:');
echo html_writer::start_tag('div', ['class' => 'table-responsive']);
echo '<table class="table table-bordered table-sm mb-0">
    <thead class="thead-light">
        <tr>
            <th>category</th>
            <th>copyshortname</th>
            <th>fullname</th>
            <th>startdate</th>
            <th>enddate</th>
            <th>shortname</th>
            <th>enrols</th>
            <th>visible</th>
            <th>idnumber</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><code>6</code></td>
            <td><code>CLP FRM 06/08/2026-07/08/2026</code></td>
            <td><code>CLP for FRM/JS 06-07 October 2026</code></td>
            <td><code>06-10-2026 00:00:00</code></td>
            <td><code>07-10-2026 23:00:00</code></td>
            <td><code>CLP FRM 06/10/2026-07/10/2026</code></td>
            <td><code>manual</code></td>
            <td><code>1</code></td>
            <td><code>2026-CLP-23</code></td>
        </tr>
    </tbody>
</table>';
echo html_writer::end_tag('div');
echo html_writer::start_tag('ul', ['class' => 'mt-3 mb-0 text-muted small']);
echo html_writer::tag('li', '<b>category</b>: ID numerik kategori kursus Moodle (contoh: 6, 20, 78).');
echo html_writer::tag('li', '<b>copyshortname</b>: Shortname kursus master / sumber yang ada di Moodle yang akan dicopy aktivitas dan materinya.');
echo html_writer::tag('li', '<b>fullname</b>: Nama lengkap kursus baru.');
echo html_writer::tag('li', '<b>shortname</b>: Shortname kursus baru (harus unik).');
echo html_writer::tag('li', '<b>startdate & enddate</b>: Fleksibel! Mendukung berbagai format: <code>DD/MM/YYYY HH:ii:ss</code>, <code>DD-MM-YYYY HH:ii:ss</code>, <code>DD.MM.YYYY</code>, <code>YYYY-MM-DD</code>, format 12 jam (AM/PM), nama bulan Indonesia/Inggris (misal: <code>06 Oktober 2026</code>), maupun serial tanggal Excel. Sistem akan otomatis mengonversi ke Unix timestamp Moodle.');
echo html_writer::tag('li', '<b>visible</b>: 1 (tampilkan) atau 0 (sembunyikan).');
echo html_writer::tag('li', '<b>idnumber</b>: Kode unik / ID Number kursus (opsional).');
echo html_writer::tag('li', '<b>enrols</b>: Metode pendaftaran (contoh: <code>manual</code>).');
echo html_writer::end_tag('ul');
echo html_writer::end_div();
echo html_writer::end_div();

// Upload form card
echo html_writer::start_div('card mb-4 shadow-sm');
echo html_writer::start_div('card-body');
$form->display();
echo html_writer::end_div();
echo html_writer::end_div();

// Recent Jobs Table
$recentjobs = $DB->get_records('tool_bulkcopycourse_jobs', [], 'id DESC', '*', 0, 10);
if (!empty($recentjobs)) {
    echo html_writer::tag('h5', '<i class="fa fa-list mr-1"></i> ' . get_string('recent_jobs', 'tool_bulkcopycourse'), ['class' => 'mt-4 mb-3']);
    echo html_writer::start_tag('div', ['class' => 'table-responsive']);
    echo '<table class="table table-hover table-striped table-bordered align-middle">';
    echo '<thead class="thead-light">
            <tr>
                <th>ID</th>
                <th>Nama Pekerjaan</th>
                <th>Total Kursus</th>
                <th>Selesai</th>
                <th>Gagal</th>
                <th>Status</th>
                <th>Waktu Dibuat</th>
                <th>Aksi</th>
            </tr>
          </thead>
          <tbody>';

    foreach ($recentjobs as $rj) {
        $badgeclass = 'badge-secondary';
        if ($rj->status === 'completed') {
            $badgeclass = 'badge-success';
        } else if ($rj->status === 'processing') {
            $badgeclass = 'badge-info';
        } else if ($rj->status === 'queued') {
            $badgeclass = 'badge-primary';
        } else if ($rj->status === 'failed') {
            $badgeclass = 'badge-danger';
        } else if ($rj->status === 'pending') {
            $badgeclass = 'badge-warning';
        }

        $actions = '';
        if ($rj->status === 'pending') {
            $actions .= html_writer::link(
                new moodle_url('/admin/tool/bulkcopycourse/preview.php', ['jobid' => $rj->id]),
                'Lanjutkan / Preview',
                ['class' => 'btn btn-sm btn-primary mr-1']
            );
        } else if ($rj->status === 'processing') {
            $actions .= html_writer::link(
                new moodle_url('/admin/tool/bulkcopycourse/process.php', ['jobid' => $rj->id]),
                'Lihat Progress',
                ['class' => 'btn btn-sm btn-info mr-1']
            );
        } else if ($rj->status === 'queued') {
            $actions .= html_writer::link(
                new moodle_url('/admin/tool/bulkcopycourse/history.php', ['jobid' => $rj->id]),
                'Status Antrean',
                ['class' => 'btn btn-sm btn-primary mr-1']
            );
        } else {
            $actions .= html_writer::link(
                new moodle_url('/admin/tool/bulkcopycourse/history.php', ['jobid' => $rj->id]),
                'Detail Riwayat',
                ['class' => 'btn btn-sm btn-outline-secondary mr-1']
            );
        }

        echo '<tr>
                <td>#' . $rj->id . '</td>
                <td>' . s($rj->name) . '</td>
                <td>' . $rj->totalcourses . '</td>
                <td><span class="text-success font-weight-bold">' . $rj->completedcourses . '</span></td>
                <td><span class="text-danger font-weight-bold">' . $rj->failedcourses . '</span></td>
                <td><span class="badge ' . $badgeclass . '">' . strtoupper($rj->status) . '</span></td>
                <td>' . userdate($rj->timecreated, '%d %b %Y %H:%M') . '</td>
                <td>' . $actions . '</td>
              </tr>';
    }

    echo '</tbody></table></div>';
}

echo \tool_bulkcopycourse\helper::get_footer_html();

echo $OUTPUT->footer();

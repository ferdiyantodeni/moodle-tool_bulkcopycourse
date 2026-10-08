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
        \core\notification::warning('Pekerjaan ini sudah berada dalam antrean atau sedang diproses.');
        redirect(new moodle_url('/admin/tool/bulkcopycourse/history.php', ['jobid' => $jobid]));
    }

    // Set job status to queued immediately to prevent double submissions.
    $job->status = 'queued';
    $job->timemodified = time();
    $DB->update_record('tool_bulkcopycourse_jobs', $job);

    $task = new \tool_bulkcopycourse\task\process_bulk_copy();
    $task->set_custom_data(['jobid' => $jobid, 'userid' => $USER->id]);
    \core\task\manager::queue_adhoc_task($task, true);

    \core\notification::success('Pekerjaan salin kursus massal telah berhasil dimasukkan ke antrean background (Ad-hoc Task).');
    redirect(new moodle_url('/admin/tool/bulkcopycourse/history.php', ['jobid' => $jobid]));
} else if ($action === 'delete_job' && confirm_sesskey()) {
    $DB->delete_records('tool_bulkcopycourse_items', ['jobid' => $jobid]);
    $DB->delete_records('tool_bulkcopycourse_jobs', ['id' => $jobid]);
    \core\notification::info('Pekerjaan berhasil dibatalkan.');
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
            <h6 class="text-muted mb-1">Total Kursus</h6>
            <h2 class="font-weight-bold mb-0">' . count($items) . '</h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white shadow-sm text-center py-3">
            <h6 class="text-white-50 mb-1">Siap Disalin (Ready)</h6>
            <h2 class="font-weight-bold mb-0">' . $readycount . '</h2>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card ' . ($errorcount > 0 ? 'bg-danger text-white' : 'bg-light text-muted') . ' shadow-sm text-center py-3">
            <h6 class="' . ($errorcount > 0 ? 'text-white-50' : 'text-muted') . ' mb-1">Error / Tidak Valid</h6>
            <h2 class="font-weight-bold mb-0">' . $errorcount . '</h2>
        </div>
    </div>
</div>';

// Action buttons
if ($job->status === 'queued') {
    echo $OUTPUT->notification(
        '<i class="fa fa-clock-o mr-1"></i> <b>Pekerjaan ini sudah berada di antrean background (Ad-hoc Task).</b> Cron Moodle akan mengeksekusi penyalinan kursus ini secara otomatis di latar belakang.',
        \core\output\notification::NOTIFY_INFO
    );
} else if ($job->status === 'processing') {
    echo $OUTPUT->notification(
        '<i class="fa fa-spinner fa-spin mr-1"></i> <b>Pekerjaan ini sedang dalam proses penyalinan.</b>',
        \core\output\notification::NOTIFY_INFO
    );
}

echo html_writer::start_div('mb-4 d-flex justify-content-between align-items-center');
echo html_writer::start_div('btn-toolbar');

if ($readycount > 0) {
    if ($job->status === 'queued' || $job->status === 'processing') {
        echo html_writer::link(
            new moodle_url('/admin/tool/bulkcopycourse/history.php', ['jobid' => $jobid]),
            '<i class="fa fa-eye mr-1"></i> Lihat Status Antrean & Hasil',
            ['class' => 'btn btn-info btn-lg mr-2']
        );
    } else {
        // Live Browser Copy button
        echo html_writer::link(
            new moodle_url('/admin/tool/bulkcopycourse/process.php', ['jobid' => $jobid]),
            '<i class="fa fa-play mr-1"></i> ' . get_string('startcopy', 'tool_bulkcopycourse') . ' (Langsung dengan Progress Bar)',
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
                'onclick' => "this.classList.add('disabled'); this.style.pointerEvents='none'; this.innerHTML='<i class=\"fa fa-spinner fa-spin mr-1\"></i> Memasukkan ke antrean...';",
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
    '<i class="fa fa-trash mr-1"></i> Batalkan / Hapus',
    ['class' => 'btn btn-outline-danger', 'onclick' => 'return confirm("Yakin ingin membatalkan pekerjaan ini?");']
);

echo html_writer::end_div();

if ($errorcount > 0) {
    echo $OUTPUT->notification(
        'Perhatian: Terdapat ' . $errorcount . ' baris kursus yang memiliki error. Baris yang memiliki status ERROR akan dilewati secara otomatis saat proses salin dijalankan.',
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
            <th style="width: 50px;">Baris</th>
            <th>Status</th>
            <th>Kursus Sumber (copyshortname)</th>
            <th>Nama Lengkap Baru</th>
            <th>Shortname Baru</th>
            <th>Kategori</th>
            <th>Tgl Mulai / Selesai</th>
            <th>ID Number</th>
            <th>Pesan Validasi</th>
        </tr>
      </thead>
      <tbody>';

foreach ($items as $item) {
    $rowclass = '';
    $badge = '';

    if ($item->status === 'error') {
        $rowclass = 'table-danger';
        $badge = '<span class="badge badge-danger">ERROR</span>';
    } else {
        $badge = '<span class="badge badge-success">READY</span>';
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

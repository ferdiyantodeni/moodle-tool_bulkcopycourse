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

$jobid = optional_param('jobid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHAEXT);

// Handle CSV Export for a specific job
if ($action === 'export_csv' && $jobid > 0 && confirm_sesskey()) {
    $job = $DB->get_record('tool_bulkcopycourse_jobs', ['id' => $jobid], '*', MUST_EXIST);
    $items = $DB->get_records('tool_bulkcopycourse_items', ['jobid' => $jobid], 'linenumber ASC');

    $filename = 'bulk_copy_job_' . $jobid . '_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    // Output UTF-8 BOM so Microsoft Excel opens it with proper encoding
    fputs($out, "\xEF\xBB\xBF");

    fputcsv($out, [
        'Line',
        'Status',
        'Category ID',
        'Source Shortname (copyshortname)',
        'New Shortname',
        'New Fullname',
        'ID Number',
        'Start Date',
        'End Date',
        'Enrols',
        'New Course ID',
        'New Course URL',
        'Error / Message'
    ]);

    foreach ($items as $item) {
        $courseurl = ($item->newcourseid > 0) ? (new moodle_url('/course/view.php', ['id' => $item->newcourseid]))->out(false) : '';
        $startstr = !empty($item->startdate) ? date('d-m-Y H:i:s', $item->startdate) : '';
        $endstr = !empty($item->enddate) ? date('d-m-Y H:i:s', $item->enddate) : '';

        fputcsv($out, [
            $item->linenumber,
            strtoupper($item->status),
            $item->category,
            $item->copyshortname,
            $item->shortname,
            $item->fullname,
            $item->idnumber,
            $startstr,
            $endstr,
            $item->enrols,
            $item->newcourseid > 0 ? $item->newcourseid : '',
            $courseurl,
            $item->errormessage ?? '',
        ]);
    }

    fclose($out);
    exit;
} else if ($action === 'export_all_csv' && confirm_sesskey()) {
    $jobs = $DB->get_records('tool_bulkcopycourse_jobs', [], 'id DESC');

    $filename = 'bulk_copy_all_jobs_' . date('Ymd_His') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    fputs($out, "\xEF\xBB\xBF");

    fputcsv($out, [
        'Job ID',
        'Job Name',
        'Total Courses',
        'Completed',
        'Failed',
        'Status',
        'Created Date',
        'Created By'
    ]);

    foreach ($jobs as $j) {
        $user = $DB->get_record('user', ['id' => $j->userid]);
        fputcsv($out, [
            $j->id,
            $j->name,
            $j->totalcourses,
            $j->completedcourses,
            $j->failedcourses,
            strtoupper($j->status),
            date('d-m-Y H:i:s', $j->timecreated),
            $user ? fullname($user) : 'User #' . $j->userid
        ]);
    }

    fclose($out);
    exit;
} else if ($action === 'delete' && $jobid > 0 && confirm_sesskey()) {
    $DB->delete_records('tool_bulkcopycourse_items', ['jobid' => $jobid]);
    $DB->delete_records('tool_bulkcopycourse_jobs', ['id' => $jobid]);
    \core\notification::success('Riwayat pekerjaan #' . $jobid . ' berhasil dihapus.');
    redirect(new moodle_url('/admin/tool/bulkcopycourse/history.php'));
}

$url = new moodle_url('/admin/tool/bulkcopycourse/history.php', $jobid ? ['jobid' => $jobid] : []);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('view_history', 'tool_bulkcopycourse'));
$PAGE->set_heading(get_string('view_history', 'tool_bulkcopycourse'));

echo $OUTPUT->header();

echo html_writer::start_div('mb-4 d-flex justify-content-between align-items-center flex-wrap');
echo html_writer::tag('h4', '<i class="fa fa-history mr-1"></i> ' . get_string('view_history', 'tool_bulkcopycourse'), ['class' => 'm-0']);
echo html_writer::start_div('btn-toolbar mt-2 mt-md-0');

if ($jobid > 0) {
    // Buttons for detail view
    echo html_writer::link(
        new moodle_url('/admin/tool/bulkcopycourse/history.php', [
            'action' => 'export_csv',
            'jobid' => $jobid,
            'sesskey' => sesskey(),
        ]),
        '<i class="fa fa-download mr-1"></i> Unduh Hasil (CSV)',
        ['class' => 'btn btn-outline-success mr-2']
    );
    echo html_writer::link(
        new moodle_url('/admin/tool/bulkcopycourse/history.php', [
            'action' => 'delete',
            'jobid' => $jobid,
            'sesskey' => sesskey(),
        ]),
        '<i class="fa fa-trash mr-1"></i> Hapus Pekerjaan Ini',
        [
            'class' => 'btn btn-outline-danger mr-2',
            'onclick' => "return confirm('Apakah Anda yakin ingin menghapus riwayat pekerjaan #{$jobid}?');",
        ]
    );
    echo html_writer::link(
        new moodle_url('/admin/tool/bulkcopycourse/history.php'),
        '<i class="fa fa-arrow-left mr-1"></i> Daftar Riwayat',
        ['class' => 'btn btn-secondary']
    );
} else {
    // Buttons for list view
    echo html_writer::link(
        new moodle_url('/admin/tool/bulkcopycourse/history.php', [
            'action' => 'export_all_csv',
            'sesskey' => sesskey(),
        ]),
        '<i class="fa fa-download mr-1"></i> Unduh Rekap Semua (CSV)',
        ['class' => 'btn btn-outline-success mr-2']
    );
    echo html_writer::link(
        new moodle_url('/admin/tool/bulkcopycourse/index.php'),
        '<i class="fa fa-upload mr-1"></i> ' . get_string('back_to_upload', 'tool_bulkcopycourse'),
        ['class' => 'btn btn-primary']
    );
}

echo html_writer::end_div();
echo html_writer::end_div();

if ($jobid > 0) {
    // Detail view for specific job
    $job = $DB->get_record('tool_bulkcopycourse_jobs', ['id' => $jobid], '*', MUST_EXIST);
    $items = $DB->get_records('tool_bulkcopycourse_items', ['jobid' => $jobid], 'linenumber ASC');
    $user = $DB->get_record('user', ['id' => $job->userid]);

    $badgeclass = 'badge-secondary';
    if ($job->status === 'completed') {
        $badgeclass = 'badge-success';
    } else if ($job->status === 'processing') {
        $badgeclass = 'badge-info';
    } else if ($job->status === 'queued') {
        $badgeclass = 'badge-primary';
    } else if ($job->status === 'failed') {
        $badgeclass = 'badge-danger';
    }

    echo html_writer::start_div('card mb-4 shadow-sm');
    echo html_writer::start_div('card-header bg-white d-flex justify-content-between align-items-center');
    echo html_writer::tag('h5', 'Pekerjaan #' . $job->id . ' - ' . s($job->name), ['class' => 'm-0 font-weight-bold']);
    echo html_writer::tag('span', strtoupper($job->status), ['class' => 'badge ' . $badgeclass . ' p-2']);
    echo html_writer::end_div();
    echo html_writer::start_div('card-body');

    echo '<div class="row text-center mb-3">
            <div class="col-md-3">
                <div class="p-2 border rounded bg-light">
                    <small class="text-muted">Total Kursus</small>
                    <h4 class="m-0 font-weight-bold">' . $job->totalcourses . '</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 border rounded bg-light">
                    <small class="text-success font-weight-bold">Berhasil</small>
                    <h4 class="m-0 font-weight-bold text-success">' . $job->completedcourses . '</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 border rounded bg-light">
                    <small class="text-danger font-weight-bold">Gagal</small>
                    <h4 class="m-0 font-weight-bold text-danger">' . $job->failedcourses . '</h4>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-2 border rounded bg-light">
                    <small class="text-muted">Dibuat Oleh</small>
                    <h6 class="m-0 font-weight-bold">' . ($user ? fullname($user) : 'System') . '</h6>
                    <small class="text-muted">' . userdate($job->timecreated, '%d %b %Y %H:%M') . '</small>
                </div>
            </div>
          </div>';

    echo html_writer::start_tag('div', ['class' => 'table-responsive mt-3']);
    echo '<table class="table table-hover table-striped table-bordered align-middle">';
    echo '<thead class="thead-light">
            <tr>
                <th style="width: 50px;">Baris</th>
                <th>Status</th>
                <th>Kursus Sumber</th>
                <th>Nama Lengkap Baru</th>
                <th>Shortname Baru</th>
                <th>Kategori</th>
                <th>ID Number</th>
                <th>Hasil / Error</th>
                <th>Aksi</th>
            </tr>
          </thead>
          <tbody>';

    foreach ($items as $item) {
        $itembadge = '';
        $courselink = '-';

        if ($item->status === 'success') {
            $itembadge = '<span class="badge badge-success">BERHASIL</span>';
            if ($item->newcourseid > 0) {
                $courselink = html_writer::link(
                    new moodle_url('/course/view.php', ['id' => $item->newcourseid]),
                    '<i class="fa fa-external-link mr-1"></i> Buka Kursus',
                    ['class' => 'btn btn-sm btn-outline-primary', 'target' => '_blank']
                );
            }
        } else if ($item->status === 'error') {
            $itembadge = '<span class="badge badge-danger">ERROR</span>';
        } else if ($item->status === 'processing') {
            $itembadge = '<span class="badge badge-info">MEMPROSES</span>';
        } else {
            $itembadge = '<span class="badge badge-secondary">MENUNGGU</span>';
        }

        echo '<tr>
                <td class="font-weight-bold text-center">' . $item->linenumber . '</td>
                <td>' . $itembadge . '</td>
                <td><code>' . s($item->copyshortname) . '</code></td>
                <td>' . s($item->fullname) . '</td>
                <td><code>' . s($item->shortname) . '</code></td>
                <td>' . $item->category . '</td>
                <td>' . s($item->idnumber) . '</td>
                <td class="small ' . ($item->errormessage ? 'text-danger' : 'text-muted') . '">' .
                    ($item->errormessage ? s($item->errormessage) : ($item->status === 'success' ? 'Kursus ID: ' . $item->newcourseid : '-')) .
                '</td>
                <td>' . $courselink . '</td>
              </tr>';
    }

    echo '</tbody></table></div>';
    echo html_writer::end_div();
    echo html_writer::end_div();

} else {
    // List all past jobs
    $jobs = $DB->get_records('tool_bulkcopycourse_jobs', [], 'id DESC', '*', 0, 50);

    if (empty($jobs)) {
        echo $OUTPUT->notification(get_string('no_jobs_found', 'tool_bulkcopycourse'), \core\output\notification::NOTIFY_INFO);
    } else {
        echo html_writer::start_tag('div', ['class' => 'table-responsive']);
        echo '<table class="table table-hover table-striped table-bordered align-middle">';
        echo '<thead class="thead-light">
                <tr>
                    <th style="width: 70px;">Job ID</th>
                    <th>Nama Pekerjaan</th>
                    <th>Total Kursus</th>
                    <th>Berhasil</th>
                    <th>Gagal</th>
                    <th>Status</th>
                    <th>Waktu Dibuat</th>
                    <th style="min-width: 220px;">Aksi</th>
                </tr>
              </thead>
              <tbody>';

        foreach ($jobs as $j) {
            $badgeclass = 'badge-secondary';
            if ($j->status === 'completed') {
                $badgeclass = 'badge-success';
            } else if ($j->status === 'processing') {
                $badgeclass = 'badge-info';
            } else if ($j->status === 'queued') {
                $badgeclass = 'badge-primary';
            } else if ($j->status === 'failed') {
                $badgeclass = 'badge-danger';
            } else if ($j->status === 'pending') {
                $badgeclass = 'badge-warning';
            }

            $detailurl = (new moodle_url('/admin/tool/bulkcopycourse/history.php', ['jobid' => $j->id]))->out(false);
            $csvurl = (new moodle_url('/admin/tool/bulkcopycourse/history.php', [
                'action' => 'export_csv',
                'jobid' => $j->id,
                'sesskey' => sesskey(),
            ]))->out(false);
            $deleteurl = (new moodle_url('/admin/tool/bulkcopycourse/history.php', [
                'action' => 'delete',
                'jobid' => $j->id,
                'sesskey' => sesskey(),
            ]))->out(false);

            echo '<tr>
                    <td class="font-weight-bold text-center">#' . $j->id . '</td>
                    <td>' . s($j->name) . '</td>
                    <td>' . $j->totalcourses . '</td>
                    <td><span class="text-success font-weight-bold">' . $j->completedcourses . '</span></td>
                    <td><span class="text-danger font-weight-bold">' . $j->failedcourses . '</span></td>
                    <td><span class="badge ' . $badgeclass . '">' . strtoupper($j->status) . '</span></td>
                    <td>' . userdate($j->timecreated, '%d %b %Y %H:%M') . '</td>
                    <td class="text-nowrap">
                        <a href="' . $detailurl . '" class="btn btn-sm btn-info mr-1">
                            <i class="fa fa-eye mr-1"></i> Detail
                        </a>
                        <a href="' . $csvurl . '" class="btn btn-sm btn-success mr-1" title="Unduh CSV Hasil">
                            <i class="fa fa-download mr-1"></i> CSV
                        </a>
                        <a href="' . $deleteurl . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Apakah Anda yakin ingin menghapus riwayat pekerjaan #' . $j->id . '?\');">
                            <i class="fa fa-trash mr-1"></i> Hapus
                        </a>
                    </td>
                  </tr>';
        }

        echo '</tbody></table></div>';
    }
}

echo \tool_bulkcopycourse\helper::get_footer_html();

echo $OUTPUT->footer();

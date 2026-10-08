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

$url = new moodle_url('/admin/tool/bulkcopycourse/process.php', ['jobid' => $jobid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('processing_title', 'tool_bulkcopycourse'));
$PAGE->set_heading(get_string('processing_title', 'tool_bulkcopycourse'));

echo $OUTPUT->header();

// Extract pending items to queue in JS
$queueitems = [];
foreach ($items as $item) {
    if ($item->status === 'pending') {
        $queueitems[] = [
            'id' => (int)$item->id,
            'linenumber' => (int)$item->linenumber,
            'copyshortname' => $item->copyshortname,
            'fullname' => $item->fullname,
            'shortname' => $item->shortname,
        ];
    }
}

$jsqueue = json_encode($queueitems);
$sesskey = sesskey();
$ajaxurl = (new moodle_url('/admin/tool/bulkcopycourse/ajax.php'))->out(false);
$historyurl = (new moodle_url('/admin/tool/bulkcopycourse/history.php', ['jobid' => $jobid]))->out(false);
$indexurl = (new moodle_url('/admin/tool/bulkcopycourse/index.php'))->out(false);
?>

<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white font-weight-bold">
        <i class="fa fa-spinner fa-spin mr-1" id="job-spinner"></i>
        <?php echo get_string('processing_title', 'tool_bulkcopycourse'); ?> - Job #<?php echo $job->id; ?>
    </div>
    <div class="card-body">
        <p class="text-muted mb-3" id="process-description">
            <?php echo get_string('processing_desc', 'tool_bulkcopycourse'); ?>
        </p>

        <!-- Progress Bar -->
        <div class="progress mb-3" style="height: 28px; font-size: 14px;">
            <div id="progressbar" class="progress-bar progress-bar-striped progress-bar-animated bg-success"
                 role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                0%
            </div>
        </div>

        <!-- Metric badges -->
        <div class="d-flex justify-content-between text-center mb-3">
            <span class="badge badge-light p-2 border">Total: <b id="count-total"><?php echo count($items); ?></b></span>
            <span class="badge badge-success p-2">Berhasil: <b id="count-success"><?php echo $job->completedcourses; ?></b></span>
            <span class="badge badge-danger p-2">Gagal / Dilewati: <b id="count-failed"><?php echo $job->failedcourses; ?></b></span>
            <span class="badge badge-info p-2">Sisa Antrean: <b id="count-remaining"><?php echo count($queueitems); ?></b></span>
        </div>

        <!-- Live Log Console -->
        <h6 class="font-weight-bold"><i class="fa fa-terminal mr-1"></i> Log Proses Salin Real-time:</h6>
        <div id="log-console" class="bg-dark text-light p-3 rounded mb-3"
             style="height: 220px; overflow-y: auto; font-family: monospace; font-size: 13px; line-height: 1.6;">
            <div class="text-secondary">[<?php echo date('H:i:s'); ?>] Memulai persiapan proses salin kursus massal...</div>
        </div>

        <!-- Action buttons after finished -->
        <div id="finished-actions" class="text-center mt-3" style="display: none;">
            <a href="<?php echo $historyurl; ?>" class="btn btn-success btn-lg mr-2">
                <i class="fa fa-check-circle mr-1"></i> Lihat Laporan & Hasil Salin
            </a>
            <a href="<?php echo $indexurl; ?>" class="btn btn-outline-secondary btn-lg">
                <i class="fa fa-upload mr-1"></i> Salin Kursus Lainnya
            </a>
        </div>
    </div>
</div>

<!-- Table of items with live status updates -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white font-weight-bold">
        <i class="fa fa-list mr-1"></i> Daftar Kursus dalam Pekerjaan Ini
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0" id="table-items">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 50px;">Baris</th>
                        <th style="width: 140px;">Status</th>
                        <th>Kursus Sumber</th>
                        <th>Shortname Baru</th>
                        <th>Nama Lengkap Baru</th>
                        <th>Tautan Kursus</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr id="row-item-<?php echo $item->id; ?>">
                        <td class="font-weight-bold text-center"><?php echo $item->linenumber; ?></td>
                        <td class="item-status">
                            <?php if ($item->status === 'success'): ?>
                                <span class="badge badge-success"><i class="fa fa-check"></i> BERHASIL</span>
                            <?php elseif ($item->status === 'error'): ?>
                                <span class="badge badge-danger"><i class="fa fa-times"></i> ERROR</span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><i class="fa fa-clock-o"></i> MENUNGGU</span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo s($item->copyshortname); ?></code></td>
                        <td><code><?php echo s($item->shortname); ?></code></td>
                        <td><?php echo s($item->fullname); ?></td>
                        <td class="item-link">
                            <?php if ($item->newcourseid > 0): ?>
                                <a href="<?php echo (new moodle_url('/course/view.php', ['id' => $item->newcourseid]))->out(false); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fa fa-external-link"></i> Buka Kursus
                                </a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var queue = <?php echo $jsqueue; ?>;
    var totalItems = <?php echo count($items); ?>;
    var completedCount = <?php echo $job->completedcourses; ?>;
    var failedCount = <?php echo $job->failedcourses; ?>;
    var totalToProcess = queue.length;
    var processedCount = 0;

    var logConsole = document.getElementById('log-console');
    var progressBar = document.getElementById('progressbar');
    var countSuccess = document.getElementById('count-success');
    var countFailed = document.getElementById('count-failed');
    var countRemaining = document.getElementById('count-remaining');
    var spinner = document.getElementById('job-spinner');
    var finishedActions = document.getElementById('finished-actions');
    var processDesc = document.getElementById('process-description');

    function appendLog(message, colorClass) {
        var now = new Date();
        var timeStr = now.toTimeString().split(' ')[0];
        var div = document.createElement('div');
        if (colorClass) {
            div.className = colorClass;
        }
        div.innerHTML = '[' + timeStr + '] ' + message;
        logConsole.appendChild(div);
        logConsole.scrollTop = logConsole.scrollHeight;
    }

    function updateProgress() {
        var totalDone = completedCount + failedCount;
        var percentage = totalItems > 0 ? Math.round((totalDone / totalItems) * 100) : 100;
        progressBar.style.width = percentage + '%';
        progressBar.setAttribute('aria-valuenow', percentage);
        progressBar.textContent = totalDone + ' / ' + totalItems + ' (' + percentage + '%)';

        countSuccess.textContent = completedCount;
        countFailed.textContent = failedCount;
        countRemaining.textContent = Math.max(0, totalToProcess - processedCount);
    }

    function processNext() {
        if (queue.length === 0) {
            // All items processed
            appendLog('<b>Semua item dalam antrean selesai diproses!</b>', 'text-success font-weight-bold');
            spinner.className = 'fa fa-check-circle mr-1 text-success';
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.remove('progress-bar-striped');
            finishedActions.style.display = 'block';
            processDesc.textContent = 'Proses salin kursus massal telah selesai.';

            // Notify server to finish job status
            fetch('<?php echo $ajaxurl; ?>?action=finish_job&jobid=<?php echo $jobid; ?>&sesskey=<?php echo $sesskey; ?>');
            return;
        }

        var item = queue.shift();
        processedCount++;

        appendLog('Menyalin Baris ' + item.linenumber + ': dari master [<code>' + item.copyshortname + '</code>] ke [<code>' + item.shortname + '</code>]...', 'text-info');

        var rowEl = document.getElementById('row-item-<?php echo $jobid; ?>_' + item.id) || document.getElementById('row-item-' + item.id);
        if (rowEl) {
            var statusEl = rowEl.querySelector('.item-status');
            if (statusEl) {
                statusEl.innerHTML = '<span class="badge badge-primary"><i class="fa fa-spinner fa-spin"></i> PROSES...</span>';
            }
        }

        var formData = new FormData();
        formData.append('action', 'copy_single');
        formData.append('itemid', item.id);
        formData.append('sesskey', '<?php echo $sesskey; ?>');

        fetch('<?php echo $ajaxurl; ?>', {
            method: 'POST',
            body: formData
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                completedCount++;
                appendLog('&#x2714; Berhasil membuat kursus ID: ' + data.newcourseid + ' (' + data.shortname + ')', 'text-success');

                if (rowEl) {
                    var statusEl = rowEl.querySelector('.item-status');
                    if (statusEl) {
                        statusEl.innerHTML = '<span class="badge badge-success"><i class="fa fa-check"></i> BERHASIL</span>';
                    }
                    var linkEl = rowEl.querySelector('.item-link');
                    if (linkEl) {
                        linkEl.innerHTML = '<a href="' + data.courseurl + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-external-link"></i> Buka Kursus</a>';
                    }
                }
            } else {
                failedCount++;
                appendLog('&#x2718; Gagal menyalin Baris ' + item.linenumber + ' (' + item.shortname + '): ' + data.error, 'text-danger');

                if (rowEl) {
                    var statusEl = rowEl.querySelector('.item-status');
                    if (statusEl) {
                        statusEl.innerHTML = '<span class="badge badge-danger" title="' + data.error + '"><i class="fa fa-times"></i> GAGAL</span>';
                    }
                }
            }
            updateProgress();
            // Process next course after a slight delay to allow UI refresh
            setTimeout(processNext, 300);
        })
        .catch(function(err) {
            failedCount++;
            appendLog('&#x2718; Error jaringan/server pada Baris ' + item.linenumber + ': ' + err.message, 'text-danger');
            if (rowEl) {
                var statusEl = rowEl.querySelector('.item-status');
                if (statusEl) {
                    statusEl.innerHTML = '<span class="badge badge-danger"><i class="fa fa-times"></i> GAGAL</span>';
                }
            }
            updateProgress();
            setTimeout(processNext, 300);
        });
    }

    updateProgress();

    if (queue.length > 0) {
        appendLog('Mempersiapkan ' + queue.length + ' kursus untuk disalin...', 'text-warning');
        setTimeout(processNext, 800);
    } else {
        appendLog('Tidak ada kursus berstatus pending yang perlu diproses.', 'text-muted');
        spinner.className = 'fa fa-check-circle mr-1 text-success';
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.remove('progress-bar-striped');
        finishedActions.style.display = 'block';
    }
});
</script>

<?php
echo \tool_bulkcopycourse\helper::get_footer_html();

echo $OUTPUT->footer();

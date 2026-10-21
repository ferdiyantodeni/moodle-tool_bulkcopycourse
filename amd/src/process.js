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
 * Bulk course copy process manager.
 *
 * @module     tool_bulkcopycourse/process
 * @copyright  2024 Ferdiyan Deni <ferdiyantodeni@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/str'], function(ajax, str) {
    'use strict';

    return {
        /**
         * Initialize copy process.
         *
         * @param {Number} jobId
         * @param {Number} totalItems
         * @param {Number} completedCount
         * @param {Number} failedCount
         * @param {Array} queue
         */
        init: function(jobId, totalItems, completedCount, failedCount, queue) {
            var processedCount = 0;
            var totalToProcess = queue.length;

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
                if (logConsole) {
                    logConsole.appendChild(div);
                    logConsole.scrollTop = logConsole.scrollHeight;
                }
            }

            function updateProgress() {
                var totalDone = completedCount + failedCount;
                var percentage = totalItems > 0 ? Math.round((totalDone / totalItems) * 100) : 100;
                if (progressBar) {
                    progressBar.style.width = percentage + '%';
                    progressBar.setAttribute('aria-valuenow', percentage);
                    progressBar.textContent = totalDone + ' / ' + totalItems + ' (' + percentage + '%)';
                }

                if (countSuccess) {
                    countSuccess.textContent = completedCount;
                }
                if (countFailed) {
                    countFailed.textContent = failedCount;
                }
                if (countRemaining) {
                    countRemaining.textContent = Math.max(0, totalToProcess - processedCount);
                }
            }

            function processNext() {
                if (queue.length === 0) {
                    str.get_string('all_completed', 'tool_bulkcopycourse').then(function(s) {
                        appendLog('<b>' + s + '</b>', 'text-success font-weight-bold');
                        if (processDesc) {
                            processDesc.textContent = s;
                        }
                    });

                    if (spinner) {
                        spinner.className = 'fa fa-check-circle mr-1 text-success';
                    }
                    if (progressBar) {
                        progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                    }
                    if (finishedActions) {
                        finishedActions.style.display = 'block';
                    }

                    // Finalize job status via external service
                    ajax.call([{
                        methodname: 'tool_bulkcopycourse_finish_job',
                        args: {jobid: jobId}
                    }]);
                    return;
                }

                var item = queue.shift();
                processedCount++;

                str.get_string('copying_line', 'tool_bulkcopycourse', {
                    line: item.linenumber,
                    source: item.copyshortname,
                    target: item.shortname
                }).then(function(s) {
                    appendLog(s, 'text-info');
                });

                var rowEl = document.getElementById('row-item-' + item.id);
                if (rowEl) {
                    var statusEl = rowEl.querySelector('.item-status');
                    if (statusEl) {
                        str.get_string('status_processing', 'tool_bulkcopycourse').then(function(s) {
                            statusEl.innerHTML = '<span class="badge badge-primary"><i class="fa fa-spinner fa-spin"></i> ' + s + '</span>';
                        });
                    }
                }

                ajax.call([{
                    methodname: 'tool_bulkcopycourse_copy_item',
                    args: {itemid: item.id}
                }])[0].then(function(data) {
                    if (data.success) {
                        completedCount++;
                        str.get_string('copy_success_detail', 'tool_bulkcopycourse', {
                            id: data.newcourseid,
                            shortname: data.shortname
                        }).then(function(s) {
                            appendLog('&#x2714; ' + s, 'text-success');
                        });

                        if (rowEl) {
                            var statusEl = rowEl.querySelector('.item-status');
                            if (statusEl) {
                                str.get_string('status_success', 'tool_bulkcopycourse').then(function(s) {
                                    statusEl.innerHTML = '<span class="badge badge-success"><i class="fa fa-check"></i> ' + s + '</span>';
                                });
                            }
                            var linkEl = rowEl.querySelector('.item-link');
                            if (linkEl && data.courseurl) {
                                str.get_string('view_course', 'tool_bulkcopycourse').then(function(s) {
                                    linkEl.innerHTML = '<a href="' + data.courseurl + '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-external-link"></i> ' + s + '</a>';
                                });
                            }
                        }
                    } else {
                        failedCount++;
                        var errMsg = data.error || 'Error';
                        str.get_string('copy_failed_detail', 'tool_bulkcopycourse', {
                            line: item.linenumber,
                            shortname: item.shortname,
                            error: errMsg
                        }).then(function(s) {
                            appendLog('&#x2718; ' + s, 'text-danger');
                        });

                        if (rowEl) {
                            var statusEl = rowEl.querySelector('.item-status');
                            if (statusEl) {
                                str.get_string('status_failed', 'tool_bulkcopycourse').then(function(s) {
                                    statusEl.innerHTML = '<span class="badge badge-danger" title="' + errMsg + '"><i class="fa fa-times"></i> ' + s + '</span>';
                                });
                            }
                        }
                    }
                    updateProgress();
                    setTimeout(processNext, 250);
                }).catch(function(err) {
                    failedCount++;
                    var msg = err.message || (err.error ? err.error : String(err));
                    str.get_string('network_error', 'tool_bulkcopycourse', {
                        line: item.linenumber,
                        error: msg
                    }).then(function(s) {
                        appendLog('&#x2718; ' + s, 'text-danger');
                    });

                    if (rowEl) {
                        var statusEl = rowEl.querySelector('.item-status');
                        if (statusEl) {
                            str.get_string('status_failed', 'tool_bulkcopycourse').then(function(s) {
                                statusEl.innerHTML = '<span class="badge badge-danger"><i class="fa fa-times"></i> ' + s + '</span>';
                            });
                        }
                    }
                    updateProgress();
                    setTimeout(processNext, 250);
                });
            }

            updateProgress();

            if (queue.length > 0) {
                str.get_string('preparing_items', 'tool_bulkcopycourse', queue.length).then(function(s) {
                    appendLog(s, 'text-warning');
                });
                setTimeout(processNext, 600);
            } else {
                str.get_string('no_pending_courses', 'tool_bulkcopycourse').then(function(s) {
                    appendLog(s, 'text-muted');
                });
                if (spinner) {
                    spinner.className = 'fa fa-check-circle mr-1 text-success';
                }
                if (progressBar) {
                    progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
                }
                if (finishedActions) {
                    finishedActions.style.display = 'block';
                }
            }
        }
    };
});

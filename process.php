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
 * Processing page for bulk course copying.
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

$url = new moodle_url('/admin/tool/bulkcopycourse/process.php', ['jobid' => $jobid]);
$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('processing_title', 'tool_bulkcopycourse'));
$PAGE->set_heading(get_string('processing_title', 'tool_bulkcopycourse'));

echo $OUTPUT->header();

// Extract items for Mustache template and queue for AMD module.
$templateitems = [];
$queueitems = [];

foreach ($items as $item) {
    $courseurl = ($item->newcourseid > 0)
        ? (new moodle_url('/course/view.php', ['id' => $item->newcourseid]))->out(false)
        : '';

    $templateitems[] = [
        'id' => (int)$item->id,
        'linenumber' => (int)$item->linenumber,
        'status' => $item->status,
        'copyshortname' => s($item->copyshortname),
        'shortname' => s($item->shortname),
        'fullname' => s($item->fullname),
        'courseurl' => $courseurl,
        'issuccess' => ($item->status === 'success'),
        'iserror' => ($item->status === 'error'),
        'ispending' => ($item->status === 'pending' || $item->status === 'processing'),
    ];

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

$initiallog = get_string('preparing_log', 'tool_bulkcopycourse', date('H:i:s'));

$templatedata = [
    'jobid' => $jobid,
    'totalcount' => count($items),
    'completedcourses' => (int)$job->completedcourses,
    'failedcourses' => (int)$job->failedcourses,
    'remainingcount' => count($queueitems),
    'initiallog' => $initiallog,
    'historyurl' => (new moodle_url('/admin/tool/bulkcopycourse/history.php', ['jobid' => $jobid]))->out(false),
    'indexurl' => (new moodle_url('/admin/tool/bulkcopycourse/index.php'))->out(false),
    'items' => $templateitems,
];

echo $OUTPUT->render_from_template('tool_bulkcopycourse/process', $templatedata);

// Initialize AMD module
$PAGE->requires->js_call_amd('tool_bulkcopycourse/process', 'init', [
    (int)$jobid,
    count($items),
    (int)$job->completedcourses,
    (int)$job->failedcourses,
    $queueitems,
]);

echo \tool_bulkcopycourse\helper::get_footer_html();

echo $OUTPUT->footer();

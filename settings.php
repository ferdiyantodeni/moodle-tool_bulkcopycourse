<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig || has_capability('tool/bulkcopycourse:bulkcopy', context_system::instance())) {
    $ADMIN->add('courses', new admin_externalpage(
        'tool_bulkcopycourse',
        get_string('pluginname', 'tool_bulkcopycourse'),
        new moodle_url('/admin/tool/bulkcopycourse/index.php'),
        'tool/bulkcopycourse:bulkcopy'
    ));
}

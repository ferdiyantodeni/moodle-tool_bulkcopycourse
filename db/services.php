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
 * External services definitions for tool_bulkcopycourse.
 *
 * @package    tool_bulkcopycourse
 * @copyright  2024 Ferdiyan Deni <ferdiyantodeni@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_bulkcopycourse_copy_item' => [
        'classname'   => 'tool_bulkcopycourse\external\copy_course_item',
        'methodname'  => 'execute',
        'description' => 'Copies a single course item in a bulk copy job',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'tool/bulkcopycourse:bulkcopy',
    ],
    'tool_bulkcopycourse_finish_job' => [
        'classname'   => 'tool_bulkcopycourse\external\finish_job',
        'methodname'  => 'execute',
        'description' => 'Marks a bulk copy job as finished',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'tool/bulkcopycourse:bulkcopy',
    ],
];

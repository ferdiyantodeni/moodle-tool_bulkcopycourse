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
 * Bulk copy course upload form.
 *
 * @package    tool_bulkcopycourse
 * @copyright  2024 Ferdiyan Deni <ferdiyantodeni@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_bulkcopycourse\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for uploading course CSV file.
 */
class upload_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'uploadheader', get_string('uploadfile', 'tool_bulkcopycourse'));

        // File manager / upload element.
        $filepickeroptions = [
            'subdirs' => 0,
            'maxbytes' => get_max_upload_file_size(),
            'maxfiles' => 1,
            'accepted_types' => ['.csv', '.txt'],
        ];

        $mform->addElement(
            'filepicker',
            'coursefile',
            get_string('uploadfile', 'tool_bulkcopycourse'),
            null,
            $filepickeroptions
        );
        $mform->addRule('coursefile', null, 'required', null, 'client');
        $mform->addHelpButton('coursefile', 'uploadfile', 'tool_bulkcopycourse');

        // CSV delimiter.
        $delimiters = [
            'auto' => get_string('autodetect', 'tool_bulkcopycourse'),
            ',' => get_string('comma', 'tool_bulkcopycourse'),
            ';' => get_string('semicolon', 'tool_bulkcopycourse'),
            '\t' => get_string('tab', 'tool_bulkcopycourse'),
        ];
        $mform->addElement(
            'select',
            'delimiter',
            get_string('delimiter', 'tool_bulkcopycourse'),
            $delimiters
        );
        $mform->setDefault('delimiter', 'auto');

        // Encoding.
        $encodings = [
            'UTF-8' => 'UTF-8',
            'ISO-8859-1' => 'ISO-8859-1 (Latin 1)',
            'WINDOWS-1252' => 'Windows-1252',
        ];
        $mform->addElement(
            'select',
            'encoding',
            get_string('encoding', 'tool_bulkcopycourse'),
            $encodings
        );
        $mform->setDefault('encoding', 'UTF-8');

        $this->add_action_buttons(false, get_string('previewtitle', 'tool_bulkcopycourse'));
    }
}

<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace tool_bulkcopycourse\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Bulk copy course upload form.
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
            'auto' => 'Auto-detect',
            ',' => 'Comma (,)',
            ';' => 'Semicolon (;)',
            '\t' => 'Tab',
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
            'ISO-8859-1' => 'ISO-8859-1',
            'Windows-1252' => 'Windows-1252',
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

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
 * @package    CopyCheck
 * @copyright  2014 Solin
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class copycheck_settings_form extends moodleform {

    // Form definition
    public function definition () {
        $mform =& $this->_form;
		
		// Use of the form
        $mform->addElement('checkbox', 'copycheck_use', get_string('copycheck_use', 'plagiarism_copycheck'));
        $mform->addHelpButton('copycheck_use', 'copycheck_use', 'plagiarism_copycheck');

		$mform->addElement('text', 'webservice_url', get_string('webservice_url', 'plagiarism_copycheck'), 'size=70');
		$mform->setType('webservice_url', PARAM_RAW);
        $mform->addHelpButton('webservice_url', 'webservice_url', 'plagiarism_copycheck');

		$mform->addElement('text', 'reporturl', get_string('reporturl', 'plagiarism_copycheck'), 'size=70');
		$mform->setType('reporturl', PARAM_RAW);
        $mform->addHelpButton('reporturl', 'reporturl', 'plagiarism_copycheck');

		$mform->addElement('text', 'clientcode', get_string('clientcode', 'plagiarism_copycheck'));
		$mform->setType('clientcode', PARAM_RAW);

        $this->add_action_buttons(true);
    }
}

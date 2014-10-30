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

global $CFG;
require_once($CFG->dirroot.'/plagiarism/lib.php');

class plagiarism_plugin_copycheck extends plagiarism_plugin {

// Todo:
// - checken verschil file of online text
// - link naar rapport in grade scherm
// - pagina voor iframe voor rapport

    /**
     * Return the list of form element names.
     *
     * @return array contains the form element names.
     */
    public function get_configs() {
        return array("copycheck_use", "clientcode");
    }

    /**
     * hook to allow plagiarism specific information to be displayed beside a submission 
     * @param array  $linkarraycontains all relevant information for the plugin to generate a link
     * @return string
     * 
     */
    public function get_links($linkarray) {
        print_object($linkarray);
		// Fixme - Here comes the link to the new page where the rapport is displayed
		/*		
		global $DB;
		
		$assign_id = "";
		$user_id = "";
		$link = $DB->get_field('plagiarism_copycheck', 'report_url', array('assign_id' => $assign_id, 'user_id' => $user_id));
		*/
		return 'HOWDTY';
    }

    /**
     * hook to add plagiarism specific settings to a module settings page
     * @param object $mform  - Moodle form
     * @param object $context - current context
     * @param string $modulename - Name of the module
     */
    public function get_form_elements_module($mform, $context, $modulename = "") {
        global $DB;
		
		// Only with the assign module
		if ($modulename != 'mod_assign') return;
		
		$checked = 0;
		$cmid = optional_param('update', 0, PARAM_INT);
		
		if ($cmid)
		{
			$sql  = "SELECT pca.enabled ";
			$sql .= "FROM {course_modules} cm ";
			$sql .= "JOIN {plagiarism_copycheck_assign} pca ON cm.instance = pca.assign_id ";
			$sql .= "WHERE cm.id = " . $cmid . " ";
			$checked = $DB->get_field_sql($sql);
		}

		$mform->addElement('header', 'copycheck', get_string('pluginname', 'plagiarism_copycheck'));
        $mform->addElement('checkbox', 'copycheck_use', get_string('copycheck_use', 'plagiarism_copycheck'));
		$mform->setDefault('copycheck_use', $checked);
	}


    /* hook to save plagiarism specific settings on a module settings page
     * @param object $data - data from an mform submission.
     */
    public function save_form_elements($data) {	
		global $DB;
		
		$current_assignment_config = $DB->get_record('plagiarism_copycheck_assign', array('assign_id' => $data->instance));
		
		$record = new stdClass();
		$record->assign_id = $data->instance;

		if (isset($data->copycheck_use)) $record->enabled = $data->copycheck_use;
		else							 $record->enabled = 0;

		if ($current_assignment_config)	
		{
			$record->id = $current_assignment_config->id;
			$DB->update_record('plagiarism_copycheck_assign', $record);
		}
		else
		{
			$DB->insert_record('plagiarism_copycheck_assign', $record);
		}
    }

}
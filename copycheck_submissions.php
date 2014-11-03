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


class plagiarism_plugin_copycheck_submissions {

	public static function check_and_send_submission_file_to_copycheck($event) {
        global $DB, $CFG;

		$copycheck_config = get_config('plagiarism_copycheck');

		if (isset($copycheck_config->copycheck_use) && $copycheck_config->copycheck_use)
		{	
			$submission_id = $event->objectid;
			$user_id = $event->userid;
			$course_id = $event->courseid;
			$contextid = $event->contextid;
			
			if ($assign_id = $DB->get_field('assignsubmission_file', 'assignment', array('id' => $submission_id)))
			{
				if ($assignment_copycheck = $DB->get_field('plagiarism_copycheck_assign', 'enabled', array('assign_id' => $assign_id)))
				{
					require_once($CFG->dirroot . '/mod/assign/locallib.php');

					$fileinfos = $DB->get_records_sql("SELECT * FROM {files} WHERE filename != '.' AND contextid=" . $contextid . " AND userid = " . $user_id);
					
					foreach ($fileinfos as $fileinfo)
					{
						$current_copycheck_record = $DB->get_record('plagiarism_copycheck', array('assign_id' => $assign_id, 'user_id' => $user_id, 'file_id' => $fileinfo->id, 'file_type' => 'file'));

						if (!$current_copycheck_record)
						{
							// Set the soapClient
							if (!isset($soapClient)) $soapClient = self::get_soap_client();

							$file_ext = "." . pathinfo($fileinfo->filename, PATHINFO_EXTENSION);
							
							if (self::check_file_extension($soapClient, $file_ext))
							{
								$guid = self::NewGuid();
								
								// Get the content of the file
								$content = "";
								$fs = get_file_storage();
								$file = $fs->get_file($fileinfo->contextid, $fileinfo->component, $fileinfo->filearea, $fileinfo->itemid, $fileinfo->filepath, $fileinfo->filename);
								
								if ($file) $content = $file->get_content();

								$xml = self::get_copycheck_xml_template($guid, $fileinfo->filename);
								
								$clientcode = $copycheck_config->clientcode;

								$parameters = array("guidStr" => $guid, "docFileBytes" => $content, "xmlFileBytes" => $xml, "klantcode" => $clientcode);
									
								// Make the soap call
								$soapRequest = $soapClient->submitDocumentMoodle($parameters);
								
								// Insert information in copycheck database
								$record = new stdClass();
								$record->assign_id = $assign_id;
								$record->user_id = $user_id;
								$record->file_type = "file";
								$record->file_id = $fileinfo->id;
								$record->guid = $guid;
								$record->timecreated = time();
								
								$new_record_id = $DB->insert_record('plagiarism_copycheck', $record);
								
								// Get the report url
								$report_url = $soapRequest->submitDocumentMoodleResult;
								
								// Update report url in copycheck database
								$update_record = new stdClass();
								$update_record->id = $new_record_id;
								$update_record->report_url = $report_url;

								$DB->update_record('plagiarism_copycheck', $update_record);
							}
						}
					}
				}
			}
		}
    }

	public static function check_and_send_submission_text_to_copycheck($event) {
		global $DB, $CFG;

		$copycheck_config = get_config('plagiarism_copycheck');

		if (isset($copycheck_config->copycheck_use) && $copycheck_config->copycheck_use)
		{	
			$submission_id = $event->objectid;
			$user_id = $event->userid;

			if ($assignsubmission = $DB->get_record('assignsubmission_onlinetext', array('id' => $submission_id)))
			{
				if ($assignment_copycheck = $DB->get_field('plagiarism_copycheck_assign', 'enabled', array('assign_id' => $assignsubmission->assignment)))
				{
					// Set the soapClient
					$soapClient = self::get_soap_client();

					$guid = self::NewGuid();
			
					$filename = $user_id . "_" . $submission_id . ".html";

					$xml = self::get_copycheck_xml_template($guid, $filename);
					
					$clientcode = $copycheck_config->clientcode;

					$parameters = array("guidStr" => $guid, "docFileBytes" => $assignsubmission->onlinetext, "xmlFileBytes" => $xml, "klantcode" => $clientcode);
								
					// Make the soap call
					$soapRequest = $soapClient->submitDocumentMoodle($parameters);

					// Insert information in copycheck database		
					$record = new stdClass();
					$record->assign_id = $assignsubmission->assignment;
					$record->user_id = $user_id;
					$record->file_type = "onlinetext";
					$record->file_id = $assignsubmission->id;
					$record->guid = $guid;
					$record->timecreated = time();
					
					$new_record_id = $DB->insert_record('plagiarism_copycheck', $record);
					
					// Get the report url
					$report_url = $soapRequest->submitDocumentMoodleResult;
					
					// Update report url in copycheck database
					$update_record = new stdClass();
					$update_record->id = $new_record_id;
					$update_record->report_url = $report_url;
					
					$DB->update_record('plagiarism_copycheck', $update_record);
				}
			}
		}
	}


	public static function NewGuid() { 
		$s = strtolower(md5(uniqid(rand(), true))); 
		$guidText = substr($s, 0, 8) . '-' . substr($s, 8, 4) . '-' . substr($s, 12, 4). '-' . substr($s, 16, 4). '-' . substr($s, 20); 

		return $guidText;
	}


	public static function get_copycheck_xml_template($guid, $filename) {
		global $DB;
		
		$copycheck_config = get_config('plagiarism_copycheck');
		$clientcode = $copycheck_config->clientcode;

		$admin = current(get_admins());

		$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
		<CopyCheck>
		  <didptr></didptr>
		  <computername></computername>
		  <client>moodle</client>
		  <taal></taal>
		  <managername></managername>
		  <servername></servername>
		  <erroremailadres></erroremailadres>
		  <klantcode>" . $clientcode . "</klantcode>
		  <wachtwoord></wachtwoord>
		  <naaminstelling></naaminstelling>
		  <projectcode></projectcode>
		  <guid>" . $guid . "</guid>
		  <documenttitle>" . $filename . "</documenttitle>
		  <hl></hl>
		  <lastWriteTicks></lastWriteTicks>
		  <lengte></lengte>
		  <dirdocument></dirdocument>
		  <orgdirdocument></orgdirdocument>
		  <fullname></fullname>
		  <suffix></suffix>
		  <language></language>
		  <subject></subject>
		  <woordenopslaan></woordenopslaan>
		  <maakimage></maakimage>
		  <kijkincopycheckdb></kijkincopycheckdb>
		  <kijkophetinternet></kijkophetinternet>
		  <maakrapportage></maakrapportage>
		  <documentopslaan></documentopslaan>
		  <orgperc></orgperc>
		  <maxrapsize></maxrapsize>
		  <stuuremail></stuuremail>
		  <emailadres>" . $admin->email . "</emailadres>
		  <emailgrens></emailgrens>
		  <submitdatum></submitdatum>
		  <submittijd></submittijd>
		  <submitted></submitted>
		  <negeer></negeer>
		  <reporturl></reporturl>
		  <klas></klas>
		  <studentnummer></studentnummer>
		  <studentnaam></studentnaam>
		  <studentemailadres></studentemailadres>
		  <orgperc></orgperc>
		  <statuscode></statuscode>
		  <statusdescription></statusdescription>
		  <reportformat></reportformat>
		  <skipauthortitle></skipauthortitle>
		  <skipsametitle></skipsametitle>
		  <reportlanguage></reportlanguage>
		</CopyCheck>";

		return utf8_encode($xml);
	}


	public static function get_soap_client() {
	
		$soapClient = new SoapClient("http://deiputs.com/CCservices.asmx?wsdl", array("trace" => 1, "exceptions" => 0));
		
		return $soapClient;      
	}


	public static function check_file_extension($soapClient, $ext) {

		$resultset = $soapClient->getSupportedFileExtensions();
		$supported_extensions = $resultset->getSupportedFileExtensionsResult;
		$file_extenstions = explode(";", $supported_extensions);
		
		$valid_file = false;
		foreach ($file_extenstions as $file_extenstion)
		{
			if (trim($file_extenstion) == $ext)
			{
				$valid_file = true;
				break;
			}
		}
		
		return $valid_file;
	}

}
?>
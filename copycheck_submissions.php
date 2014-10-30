<?php
defined('MOODLE_INTERNAL') || die();

class plagiarism_plugin_copycheck_submissions {

	public static function check_and_send_submission_file_to_copycheck($event) {
        global $DB, $CFG;
		
		$submission_id = $event->objectid;
		$user_id = $event->userid;
		$course_id = $event->courseid;
		$contextid = $event->contextid;
		
		if ($assign_id = $DB->get_field('assignsubmission_file', 'assignment', array('id' => $submission_id)))
		{
			require_once($CFG->dirroot . '/mod/assign/locallib.php');

			$fileinfos = $DB->get_records_sql("SELECT * FROM {files} WHERE filename != '.' AND contextid=" . $contextid . " AND userid = " . $user_id);
			
			foreach ($fileinfos as $fileinfo)
			{
				$current_copycheck_record = $DB->get_record('plagiarism_copycheck', array('assign_id' => $assign_id, 'user_id' => $user_id, 'file_id' => $fileinfo->id, 'file_type' => 'file'));

				if (!$current_copycheck_record)
				{
					if (!isset($soapClient)) $soapClient = self::get_soap_client();

					$file_path = '/' . $contextid . '/' . $fileinfo->component . '/' . $fileinfo->filearea . '/' . $fileinfo->itemid . $fileinfo->filepath . $fileinfo->filename;
					$file_url = file_encode_url($CFG->wwwroot . "/pluginfile.php", $file_path);
					$file_ext = "." . pathinfo($fileinfo->filename, PATHINFO_EXTENSION);
					
					if (self::check_file_extension($soapClient, $file_ext))
					{
						$guid = self::NewGuid();

						$handle = fopen($file_url, "rb");
						$content = stream_get_contents($handle);
						fclose($handle);

						$xml = self::get_copycheck_xml_template($guid, $fileinfo->filename);
						
						$copycheck_config = get_config('plagiarism_copycheck');
						$clientcode = $copycheck_config->clientcode;

						$parameters = array("guidStr" => $guid, "docFileBytes" => $content, "xmlFileBytes" => $xml, "klantcode" => $clientcode);
							
						
						//$soapRequest = $client->submitDocumentMoodle($parameters);

						//echo "request: <br>\n";
						//print_object( $client->__getLastRequest() );
						//echo "response: <br>\n";
						//print_object( $client->__getLastResponse() );

						
						$record = new stdClass();
						$record->assign_id = $assign_id;
						$record->user_id = $user_id;
						$record->file_type = "file";
						$record->file_id = $fileinfo->id;
						$record->guid = $guid;
						
						echo "insert_record";
						print_object($record);
						$new_record_id = $DB->insert_record('plagiarism_copycheck', $record);
						
						//$report_url = $soapRequest->submitDocumentMoodleResult;
						//print_object($report_url);
						
						//update report url in record
						//$update_record = new stdClass();
						//$update_record->id = $new_record_id;
						//$update_record->report_url = $report_url;
					}
				}
				// fixme - del
				else print_object($current_copycheck_record);
			}
		}
		
		//exit('hier');
    }

	public static function check_and_send_submission_text_to_copycheck($event) {
		global $DB, $CFG;

		print_object($event);

		$submission_id = $event->objectid;
		$user_id = $event->userid;
		$course_id = $event->courseid;
		$contextid = $event->contextid;

		if ($assignsubmission = $DB->get_record('assignsubmission_onlinetext', array('id' => $submission_id)))
		{
			$current_copycheck_record = $DB->get_record('plagiarism_copycheck', array('assign_id' => $assignsubmission->assignment, 'user_id' => $user_id, 'file_id' => $assignsubmission->id, 'file_type' => 'onlinetext'));

			if (!$current_copycheck_record)
			{

				//$soapClient = self::get_soap_client();

				$guid = self::NewGuid();
		
				$filename = $user_id . "_" . $submission_id . ".html";

				$xml = self::get_copycheck_xml_template($guid, $filename);
				
				$copycheck_config = get_config('plagiarism_copycheck');
				$clientcode = $copycheck_config->clientcode;

				$parameters = array("guidStr" => $guid, "docFileBytes" => $assignsubmission->onlinetext, "xmlFileBytes" => $xml, "klantcode" => $clientcode);
					
				
				//$soapRequest = $client->submitDocumentMoodle($parameters);

				//echo "request: <br>\n";
				//print_object( $client->__getLastRequest() );
				//echo "response: <br>\n";
				//print_object( $client->__getLastResponse() );

				
				$record = new stdClass();
				$record->assign_id = $assignsubmission->assignment;
				$record->user_id = $user_id;
				$record->file_type = "onlinetext";
				$record->file_id = $assignsubmission->id;
				$record->guid = $guid;
				
				echo "insert_record";
				print_object($record);
				$new_record_id = $DB->insert_record('plagiarism_copycheck', $record);
				
				//$report_url = $soapRequest->submitDocumentMoodleResult;
				//print_object($report_url);
				
				//update report url in record
				//$update_record = new stdClass();
				//$update_record->id = $new_record_id;
				//$update_record->report_url = $report_url;
			}
		}

		//exit();
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
      
	  //Fixme - something with try
	  /*
	   try { 
            $options = array( 
                'soap_version'=>SOAP_1_2, 
                'exceptions'=>true, 
                'trace'=>1, 
                'cache_wsdl'=>WSDL_CACHE_NONE 
            ); 
            $client = new SoapClient('http://example.com/doc.asmx?WSDL', $options); 
		// Note where 'Get' and 'request' tags are in the XML 
            $results = $client->Get(array('request'=>array('CustomerId'=>'1234'))); 
        } catch (Exception $e) { 
            echo "<h2>Exception Error!</h2>"; 
            echo $e->getMessage(); 
        } 
	*/
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
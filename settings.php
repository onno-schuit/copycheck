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


require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/plagiarismlib.php');
require_once($CFG->dirroot . '/plagiarism/copycheck/lib.php');

require_login();
admin_externalpage_setup('plagiarismcopycheck');
$context = context_system::instance();
require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

require_once($CFG->dirroot.'/plagiarism/copycheck/copycheck_settings_form.php');
$mform = new copycheck_settings_form();

$mform->set_data(get_config('plagiarism_copycheck'));

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));
}

if (($data = $mform->get_data()) && confirm_sesskey()) 
{  
	foreach ($data as $field => $value) 
	{
        if (strpos($field, "submit") === false) 
		{
            set_config($field, $value, 'plagiarism_copycheck');
        }
    }

    echo $OUTPUT->notification(get_string('saved_copycheck_settings', 'plagiarism_copycheck'), 'notifysuccess');

	// Fixme - also check connection ???
}


echo $OUTPUT->header();

echo "<h1>" . get_string('copycheck_settings_header', 'plagiarism_copycheck') . "</h1>\n";

echo $OUTPUT->box_start();
$mform->display();
echo $OUTPUT->box_end();


//----------------------------------------------------------------------------------------------------------------

/*
$filename = "test.txt";
$handle = fopen($filename, "rb");
$content = fread($handle, filesize($filename));
fclose($handle);

$guid = plagiarism_plugin_copycheck::NewGuid();
// Fixme
//$reporturl = $CFG->wwwroot . "/plagiarism/copycheck/settings.php";
$reporturl = $CFG->wwwroot;

$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<CopyCheck>
  <client>
  </client>
  <managername>
  </managername>
  <servername>
  </servername>
  <klantcode>solin-2014</klantcode>
  <wachtwoord>
  </wachtwoord>
  <guid>" . $guid . "</guid>
  <documenttitle>" . $filename . "</documenttitle>
  <suffix>
  </suffix>
  <language>
  </language>
  <subject>
  </subject>
  <woordenopslaan>
  </woordenopslaan>
  <maakimage>
  </maakimage>
  <kijkincopycheckdb>true</kijkincopycheckdb>
  <kijkophetinternet>true</kijkophetinternet>
  <maakrapportage>true</maakrapportage>
  <documentopslaan>true</documentopslaan>
  <orgperc>
  </orgperc>
  <maxrapsize>
  </maxrapsize>
  <stuuremail>
  </stuuremail>
  <emailadres>martijn@solin.nl</emailadres>
  <submitdatum>
  </submitdatum>
  <submittijd>
  </submittijd>
  <submitted>
  </submitted>
  <negeer>
  </negeer>
  <reporturl>" . $reporturl . "</reporturl>
  <klas>
  </klas>
  <studentnummer>
  </studentnummer>
  <studentnaam>A User</studentnaam>
  <studentemailadres>a@solin.nl</studentemailadres>
  <orgperc>
  </orgperc>
  <statuscode>
  </statuscode>
  <statusdescription>
  </statusdescription>
  <reportformat>
  </reportformat>
  <skipauthortitle>False</skipauthortitle>
  <erroremailadres>
  </erroremailadres>
</CopyCheck>";


$wsdl = "http://www.copycheck.eu/CCservices.asmx?WSDL";
    
$client = new SoapClient($wsdl, array("trace" => 1, "exceptions" => 0));

$parameters = array("guidStr" => $guid, "docFileBytes" => $content, "xmlFileBytes" => utf8_encode($xml), "klantcode" => "solin-2014");
    
$values = $client->submitDocument2nd($parameters);

echo "request: <br>\n";
print_object( $client->__getLastRequest() );
echo "response: <br>\n";
print_object( $client->__getLastResponse() );

$resultset = $values->submitDocument2ndResult;
print_object($resultset);
*/



echo $OUTPUT->footer();


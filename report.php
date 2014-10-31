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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$report_id = required_param('id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$is_previous_report = optional_param('previous_report', 0, PARAM_INT);

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, true, $cm);

$url_parameters = array('id' => $report_id, 'cmid' => $cmid);
if($is_previous_report) $url_parameters['previous_report'] = $is_previous_report;
$url = new moodle_url('/plagiarism/copycheck/report.php', $url_parameters);
$PAGE->set_url($url);

$PAGE->set_title(get_string('pluginname', 'plagiarism_copycheck'));
$PAGE->set_heading(get_string('copycheck_report', 'plagiarism_copycheck'));
$PAGE->navbar->add(get_string('copycheck_report', 'plagiarism_copycheck'), "");

$context = context_module::instance($cmid);
require_capability('mod/assign:grade', $context);

$copycheck = $DB->get_record('plagiarism_copycheck', array('id' => $report_id));
$user = $DB->get_record('user', array('id' => $copycheck->user_id));

echo $OUTPUT->header();

echo "<h2>" . get_string('copycheck_report_title', 'plagiarism_copycheck') . fullname($user) . "</h2>\n";
echo "<iframe src='" . $copycheck->report_url . "' height='800' width='900'></iframe>\n";
echo "<p>&nbsp;</p>\n";

if (!$is_previous_report)
{
	$sql  = "SELECT * ";
	$sql .= "FROM {plagiarism_copycheck} ";
	$sql .= "WHERE assign_id = " . $copycheck->assign_id . " ";
	$sql .= "AND user_id = " . $copycheck->user_id . " ";
	$sql .= "AND timecreated < " . $copycheck->timecreated . " ";
	$previous_reports = $DB->get_records_sql($sql);


	if (count($previous_reports))
	{
		echo "<p>" . get_string('view_previous_reports', 'plagiarism_copycheck') . "\n";
		
		echo "<ul>\n";
		foreach ($previous_reports as $previous_report)
		{
			echo "<li>" . date("d-m-Y H:i:s", $previous_report->timecreated) . " - <a href='" . $CFG->wwwroot . "/plagiarism/copycheck/report.php?id=" . $previous_report->id . "&cmid=" . $cmid . "&previous_report=" . $copycheck->id . "'>[ " . get_string('view_report', 'plagiarism_copycheck') . " ]</a></li>\n";

		}
		echo "</ul>\n";
		echo "</p>\n";
	}
}
else
{
	echo "<p><< <a href='" . $CFG->wwwroot . "/plagiarism/copycheck/report.php?id=" . $is_previous_report . "&cmid=" . $cmid . "'>" . get_string('back_current_report', 'plagiarism_copycheck') . "</a></p>\n";
}

echo $OUTPUT->footer(); 


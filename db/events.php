<?php
$observers = array(
    array(
        'eventname'   => 'assignsubmission_file\event\submission_created',
        'callback'    => 'plagiarism_plugin_copycheck_submissions::check_and_send_submission_file_to_copycheck',
        'includefile' => 'plagiarism/copycheck/copycheck_submissions.php',
        'priority'    => 9999
    ),
    array(
        'eventname'   => 'assignsubmission_file\event\submission_updated',
        'callback'    => 'plagiarism_plugin_copycheck_submissions::check_and_send_submission_file_to_copycheck',
        'includefile' => 'plagiarism/copycheck/copycheck_submissions.php',
        'priority'    => 9999
    ),
	// Fixme - andere functie voor online text?
    array(
        'eventname'   => 'assignsubmission_onlinetext\event\submission_created',
        'callback'    => 'plagiarism_plugin_copycheck_submissions::check_and_send_submission_text_to_copycheck',
        'includefile' => 'plagiarism/copycheck/copycheck_submissions.php',
        'priority'    => 9999
    ),
    array(
        'eventname'   => 'assignsubmission_onlinetext\event\submission_updated',
        'callback'    => 'plagiarism_plugin_copycheck_submissions::check_and_send_submission_text_to_copycheck',
        'includefile' => 'plagiarism/copycheck/copycheck_submissions.php',
        'priority'    => 9999
    )
);

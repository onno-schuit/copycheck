<?php

function xmldb_plagiarism_copycheck_install() {
    global $CFG;

    set_config('enableplagiarism', 1);
    set_config('enableplagiarism', 1, 'plagiarism');
    set_config('copycheck_use', 1, 'plagiarism');  
}

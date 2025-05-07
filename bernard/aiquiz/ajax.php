<?php
require_once('../../config.php');

$attemptid = required_param('attemptid', PARAM_INT);
$slot = required_param('slot', PARAM_INT);

// Ensure the user has permission to view this data
require_login();
$attemptobj = quiz_create_attempt_handling_errors($attemptid);
$attemptobj->require_capability('mod/quiz:grade');

 
    $output .= html_writer::tag('p', get_string('no_ai_evaluation', 'local_aiquiz')); 

echo $output;
<?php
defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback' => '\local_aiquiz\observer::quiz_attempt_submitted',
    ),
    array(
        'eventname'   => '\core\event\question_updated',
        'callback'    => '\local_aiquiz\observer::quiz_question_updated',
        'priority'    => 1000, 
    ),
    array(
        'eventname' => '\local_aiquiz\event\comment_page_viewed',
        'callback' => '\local_aiquiz\observers\quiz_comment_observer::inject_content',
    ),

    /*array(
        'eventname' => '\core\event\question_updated',
        'callback' => '\local_aiquiz\observer::question_updated',        
    ),*/
     
);
<?php
defined('MOODLE_INTERNAL') || die();

$observers = array(
    array(
        'eventname' => '\mod_quiz\event\attempt_submitted',
        'callback' => '\local_aiquiz\observer::quiz_attempt_submitted',
    ),
    array(
        'eventname' => '\local_aiquiz\event\comment_page_viewed',
        'callback' => '\local_aiquiz\observers\quiz_comment_observer::inject_content',
    ),
);
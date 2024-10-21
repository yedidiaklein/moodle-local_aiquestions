<?php
namespace local_aiquiz;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../locallib.php');

class observer {
    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
        $attemptid = $event->objectid;
        aiquiz_evaluate_attempt($attemptid, true);
    }
}
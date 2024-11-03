<?php
namespace local_aiquiz;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../locallib.php');

class observer {
    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
        $attemptid = $event->objectid;
        aiquiz_evaluate_attempt($attemptid, true);
    }
     
 


   
    public static function quiz_question_updated(\core\event\question_updated $event) {
        global $DB, $USER, $CFG;
        
        // Add more detailed logging
        $logFilePath = $CFG->dataroot . '/aiquiz_debug.log';
        error_log("Observer triggered for question " . $event->objectid . " at " . date('Y-m-d H:i:s') . PHP_EOL, 3, $logFilePath);
        
        try {
            $questionid = $event->objectid;
            $result = aiquiz_question_updated($questionid);
            error_log("Question update result: " . ($result ? 'success' : 'failed') . PHP_EOL, 3, $logFilePath);
        } catch (Exception $e) {
            error_log("Error in observer: " . $e->getMessage() . PHP_EOL, 3, $logFilePath);
        }
    }
 
}
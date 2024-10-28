<?php
namespace local_aiquiz;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../locallib.php');

class observer {
    public static function quiz_attempt_submitted(\mod_quiz\event\attempt_submitted $event) {
        $attemptid = $event->objectid;
        aiquiz_evaluate_attempt($attemptid, true);
    }
    public static function question_updated(\core\event\question_updated $event) {
        global $DB, $USER, $CFG;
        
        // Initial debugging
        debugging("AIQUIZ DEBUG: Question update triggered for question " . $event->objectid, DEBUG_DEVELOPER);
        
        // Get the question details
        $questionid = $event->objectid;
        $question = $DB->get_record('question', array('id' => $questionid));
    
        // Add redirect for testing
        redirect(new \moodle_url('/local/aiquiz/test_update.php', array(
            'questionid' => $questionid,
            'userid' => $USER->id,
            'time' => time()
        )));
        
        // Rest of your existing code...
    }
    public static function question_updatedx(\core\event\question_updated $event) {
        global $DB, $USER;
        
        // Initial debugging
        debugging("AIQUIZ DEBUG: Question update triggered for question " . $event->objectid, DEBUG_DEVELOPER);
        
        // Get the question details
        $questionid = $event->objectid;
        $question = $DB->get_record('question', array('id' => $questionid));
        
        if (!$question) {
            debugging("Question not found", DEBUG_DEVELOPER);
            return false;
        }
    
        // Check if this is an AI-generated question
        if (empty($question->aiquiz_id)) {
            debugging("Not an AI question - skipping", DEBUG_DEVELOPER);
            return false;
        }
    
        // Get quiz metadata using JSON search
        $sql = "SELECT * FROM {local_aiquiz_metadata} 
                WHERE question_ids LIKE :questionpattern";
        
        // Create the pattern to search for the question ID in the JSON array
        $pattern = '%' . $questionid . '%';
        $metadata = $DB->get_record_sql($sql, array('questionpattern' => $pattern));
    
        if (!$metadata) {
            debugging("Metadata not found for question", DEBUG_DEVELOPER);
            return false;
        }
    
        try {
            // Update the question_ids array in metadata
            $question_ids = json_decode($metadata->question_ids, true); // Convert JSON string to array
            
            // Check if question ID doesn't exist in array
            if (!in_array($questionid, $question_ids)) {
                // Add the new question ID to the array
                $question_ids[] = $questionid;
                
                // Convert back to JSON and update the metadata
                $metadata->question_ids = json_encode($question_ids);
                $DB->update_record('local_aiquiz_metadata', $metadata);
                
                debugging("Added question ID {$questionid} to metadata", DEBUG_DEVELOPER);
            }
            // Create API client
            $api_client = new api_client();
    
            // Initialize the question data structure
            $updated_question  = $question->questiontext."Updated";
            $api_data = array(
                'question' => array(
                    'question' => clean_param($updated_question, PARAM_TEXT)
                )
            );
    
            // Handle different question types
            switch ($question->qtype) {
                case 'multichoice':
                    $answers = $DB->get_records('question_answers', 
                        array('question' => $questionid), 
                        'id ASC'
                    );
                    
                    if ($answers) {
                        $options = array();
                        $correct_answers = array();
                        
                        foreach ($answers as $answer) {
                            $options[] = clean_param($answer->answer, PARAM_TEXT);
                            if ($answer->fraction > 0) {
                                $correct_answers[] = clean_param($answer->answer, PARAM_TEXT);
                            }
                        }
                        
                        $api_data['question']['options'] = $options;
                        $api_data['question']['correctAnswers'] = $correct_answers;
                    }
                    break;
    
                case 'shortanswer':
                    $answers = $DB->get_records('question_answers', 
                        array('question' => $questionid)
                    );
                    
                    if ($answers) {
                        $correct_answers = array();
                        foreach ($answers as $answer) {
                            $correct_answers[] = clean_param($answer->answer, PARAM_TEXT);
                        }
                        $api_data['question']['correctAnswers'] = $correct_answers;
                    }
                    break;
    
                case 'essay':
                    $essay_options = $DB->get_record('qtype_essay_options', 
                        array('questionid' => $questionid)
                    );
                    
                    if ($essay_options) {
                        if (!empty($essay_options->graderinfo)) {
                            $api_data['question']['explanation'] = clean_param($essay_options->graderinfo, PARAM_TEXT);
                        }
                        if (!empty($question->generalfeedback)) {
                            $api_data['question']['indicator'] = clean_param($question->generalfeedback, PARAM_TEXT);
                        }
                    }
                    break;
            }
    
            debugging("Sending API request with data: " . json_encode($api_data), DEBUG_DEVELOPER);
    
            // Call API to update question
            $response = $api_client->update_question($metadata->exam_id, $question->aiquiz_id, $api_data);
            
            // Log the response
            debugging("API Response: " . json_encode($response), DEBUG_DEVELOPER);
            
            return true;
        } catch (\Exception $e) {
            debugging("API Error: " . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }
}
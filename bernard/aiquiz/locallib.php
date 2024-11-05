<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/local/aiquiz/classes/api_client.php'); 
require_once($CFG->dirroot . '/local/aiquiz/classes/api_client_student.php');
require_once($CFG->dirroot . '/mod/quiz/classes/grade_calculator.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/essay/question.php');
require_once($CFG->dirroot . '/question/engine/states.php');
require_once($CFG->dirroot . '/mod/quiz/classes/structure.php');


 
function aiquiz_get_student_answers($attemptobj) {
    global $DB;
    $answers = array();
    
    foreach ($attemptobj->get_slots() as $slot) {
        $qa = $attemptobj->get_question_attempt($slot);
        $question = $qa->get_question();
        $answer_object = $qa->get_last_qt_var('answer');
        
       // Reindex the answers array
        // Extract the actual answer text
        $answer_text = '';
        
        switch ($question->qtype->name()) {
            case 'multichoice':
                $qa_order = $qa->get_last_qt_var('_order');
                $order = explode(',', $qa_order);

                 

                if (is_array($answer_object)) {
                    // Multiple answers selected
                    $selected_answers = array();
                    foreach ($answer_object as $key) {
                        $actual_key = $order[$key];
                        if (isset($question->answers[$actual_key])) {
                            $answer = $question->answers[$actual_key]->answer;
                            $selected_answers[] = strip_tags($answer);
                             
                        }
                    }
                    $answer_text = implode(', ', $selected_answers);
                } else {
                    // Single answer selected
                    $actual_key = $order[$answer_object];
                    if (isset($question->answers[$actual_key])) {
                        $answer_text = strip_tags($question->answers[$actual_key]->answer);
                         
                    }
                }
                break;
                
            case 'match':
                if (is_array($answer_object)) {
                    $matched_answers = array();
                    foreach ($answer_object as $sub_question => $answer_id) {
                        $sub_q = $qa->get_question()->get_subquestion($sub_question);
                        $answer = $qa->get_question()->get_right_choice_for($sub_question);
                        if ($sub_q && $answer) {
                            $matched_answers[] = strip_tags($sub_q) . ' => ' . strip_tags($answer);
                        }
                    }
                    $answer_text = implode('; ', $matched_answers);
                }
                break;

            default:
                // Handle other question types (essay, short answer, etc.)
                if ($answer_object instanceof question_file_loader) {
                    $answer_text = $answer_object->__toString();
                } elseif (is_object($answer_object) && method_exists($answer_object, 'get_value')) {
                    $answer_text = $answer_object->get_value();
                } elseif (is_string($answer_object)) {
                    $answer_text = $answer_object;
                } else {
                    $answer_text = 'Unable to extract answer';
                }
                break;
        }

        $quizdata = $DB->get_record('question', array('id' => $question->id));
        if (!empty($quizdata->aiquiz_id)) {
            // Clean and format the answer text
            $clean_answer_text = trim(strip_tags($answer_text));
            
            $answers[] = array(
                'questionId' => $quizdata->aiquiz_id,
                'answer' => $clean_answer_text
            );
        }
    }
    return $answers;
}
 
 

function aiquiz_evaluate_attempt($attemptid, $auto = false) {
    global $DB, $USER;
    
    function update_progress($percentage, $message) {
        echo "<script>
            var progressBar = document.getElementById('progress-bar');
            var messageText = document.querySelector('.loader-text');
            if (progressBar) {
                progressBar.style.width = '$percentage%';
                progressBar.style.transition = 'width 0.5s ease';
            }
            if (messageText) {
                messageText.textContent = '$message';
            }
        </script>";
        flush();
        ob_flush();
    }

    try {
        $attemptobj = \mod_quiz\quiz_attempt::create($attemptid);
        $attemptobj = \mod_quiz\quiz_attempt::create($attemptid);
        $quizobj = $attemptobj->get_quizobj();
        $quiz = $attemptobj->get_quiz();
        $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);
        $uniqueid = $attemptobj->get_attempt()->uniqueid;

        // Update progress
        $progress_script = "<script>
            var loader = document.getElementById('aiquiz-loader');
            if (loader) {
                var bar = loader.querySelector('.progress-bar');
                var status = loader.querySelector('.status');
                if (bar && status) {
                    bar.style.width = '%d%%';
                    status.textContent = '%s';
                }
            }
        </script>";

        // Initialize evaluation
        update_progress(10, 'Initializing evaluation...');

        // Get the AI exam ID
        $metadata = $DB->get_record('local_aiquiz_metadata', array('quiz_id' => $quiz->id));
        if (!$metadata || empty($metadata->exam_id)) {
            echo "<script>
                var overlay = document.getElementById('loading-overlay');
                overlay.style.opacity = '0';
                setTimeout(function() { overlay.remove(); }, 500);
            </script>";
            return false;
        }
        
         
        
        // Get answers
        $answers = aiquiz_get_student_answers($attemptobj);
        if (empty($answers)) {
            return false;
        }

         

        $user = $DB->get_record('user', array('id' => $attemptobj->get_userid()), '*', MUST_EXIST);
        $student_details = [
            'fullName' => fullname($user),
            'id' => $user->id,
            'email' => $user->email
        ];

        // Call API
        $api_client = new \local_aiquiz\api_client_student();
        $api_response = $api_client->evaluate_exam($metadata->exam_id, $answers, $student_details);
        
        if (!isset($api_response['response']) || !isset($api_response['response']['answers'])) {
            throw new \moodle_exception('Invalid API response');
        }

        $evaluation_result = $api_response['response']; 

        $total_questions = count($evaluation_result['answers']);
        
        foreach ($evaluation_result['answers'] as $index => $answer) {
            $progress = 60 + (($index + 1) / $total_questions * 30);
            update_progress($progress, "Processing question " . ($index + 1) . " of $total_questions");

            $sql = "SELECT q.*, qa.id as questionattemptid
                FROM {question} q
                JOIN {question_attempts} qa ON qa.questionid = q.id
                WHERE q.aiquiz_id = :questionid 
                AND qa.questionusageid = :questionusageid";

            $params = array(
                'questionid' => $answer['question_id'],
                'questionusageid' => $uniqueid
            );

            $question = $DB->get_record_sql($sql, $params);
            if (!$question) {
                continue;
            }

            // Process the question grading
            $slot = null;
            foreach ($attemptobj->get_slots() as $qslot) {
                if ($attemptobj->get_question_attempt($qslot)->get_question()->id == $question->id) {
                    $slot = $qslot;
                    break;
                }
            }
            
            if (!$slot) {
                continue;
            }
            
            $qa = $attemptobj->get_question_attempt($slot);
            if (!$qa) {
                continue;
            }

            $grade = isset($answer['grade']) ? floatval($answer['grade']) : 0;
            $max_mark = $qa->get_max_mark();
            $grade = max(0, min($grade, $max_mark));
            $fraction = $max_mark > 0 ? $grade / $max_mark : 0;
            $fraction = max(0, min(1, $fraction));
            $comment = isset($answer['teacher_feedback']) ? $answer['teacher_feedback'] : '';

            custom_manual_grade($qa->get_database_id(), $question->id, $comment, $fraction, $max_mark, $USER->id);
        }

        // Update grades
        $quizobj = \mod_quiz\quiz_settings::create($quiz->id);
        $grade_calculator = \mod_quiz\grade_calculator::create($quizobj);
        $grade_calculator->recompute_final_grade($attemptobj->get_userid());
        quiz_update_grades($quiz, $attemptobj->get_userid());

        // Final updates
        update_progress(100, 'Evaluation completed successfully!');
        
        // Smooth removal of the overlay
        echo "<script>
            setTimeout(function() {
                var overlay = document.getElementById('loading-overlay');
                if (overlay) {
                    overlay.style.opacity = '0';
                    overlay.style.transition = 'opacity 0.5s ease';
                    setTimeout(function() { overlay.remove(); }, 500);
                }
            }, 1000);
        </script>";

        return $evaluation_result;

    } catch (\Exception $e) {
        // Handle error display
        echo "<script>
            var messageText = document.querySelector('.loader-text');
            var progressBar = document.getElementById('progress-bar');
            var spinner = document.querySelector('.loader-spinner');
            if (messageText) {
                messageText.style.animation = 'none';
                messageText.style.color = '#ff4444';
                messageText.textContent = 'Error: " . addslashes($e->getMessage()) . "';
            }
            if (progressBar) {
                progressBar.style.width = '100%';
                progressBar.style.backgroundColor = '#ff4444';
            }
            if (spinner) {
                spinner.style.borderTopColor = '#ff4444';
            }
            setTimeout(function() {
                var overlay = document.getElementById('loading-overlay');
                if (overlay) {
                    overlay.style.opacity = '0';
                    overlay.style.transition = 'opacity 0.5s ease';
                    setTimeout(function() { overlay.remove(); }, 500);
                }
            }, 3000);
        </script>";
        
        return false;
    }
}
function custom_manual_grade($attemptid, $questionid, $comment, $fraction, $max_mark, $graderid = null) {
    global $DB, $USER;

    mtrace("Starting manual grading for attempt ID: $attemptid");
    
    if ($graderid === null) {
        $graderid = $USER->id;
    }

    try {
        // Get the original answer
        $original_answer = $DB->get_field_sql(
            "SELECT qsd.value 
             FROM {question_attempt_steps} qas
             JOIN {question_attempt_step_data} qsd ON qsd.attemptstepid = qas.id
             WHERE qas.questionattemptid = ? 
             AND qsd.name = 'answer'
             ORDER BY qas.sequencenumber DESC
             LIMIT 1",
            array($attemptid)
        );

        // Get the original answer format
        $original_format = $DB->get_field_sql(
            "SELECT qsd.value 
             FROM {question_attempt_steps} qas
             JOIN {question_attempt_step_data} qsd ON qsd.attemptstepid = qas.id
             WHERE qas.questionattemptid = ? 
             AND qsd.name = 'answerformat'
             ORDER BY qas.sequencenumber DESC
             LIMIT 1",
            array($attemptid)
        );

        // Update the question_attempts table
        $DB->update_record('question_attempts', array(
            'id' => $attemptid,
            'maxmark' => $max_mark,
            'minfraction' => 0,
            'maxfraction' => 1,
            'rightanswer' => '',
            'responsesummary' => $original_answer,
            'timemodified' => time()
        ));

        // Get the latest sequence number for this attempt
        $latest_seq = $DB->get_field_sql(
            "SELECT MAX(sequencenumber) FROM {question_attempt_steps} WHERE questionattemptid = ?",
            array($attemptid)
        );
        $new_seq = $latest_seq + 1;

        // Create a new step in the question_attempt_steps table
        $stepdata = new stdClass();
        $stepdata->questionattemptid = $attemptid;
        $stepdata->sequencenumber = $new_seq;
        //$stepdata->state = 'mangrright';  // Use 'mangrright' for correct answers, 'mangrwrong' for incorrect
        if ($fraction == 1.0) {
            $stepdata->state = 'mangrright';  // Completely correct
        } elseif ($fraction == 0.0) {
            $stepdata->state = 'mangrwrong';  // Completely wrong
        } else {
            $stepdata->state = 'mangrpartial';  // Partially correct
        }
        $stepdata->fraction = $fraction;
        $stepdata->userid = $graderid;
        $stepdata->timecreated = time();
        
        $stepid = $DB->insert_record('question_attempt_steps', $stepdata);

        // Calculate the actual mark
        $actual_mark = $fraction * $max_mark;

        // Insert the step data
        $step_data_entries = array(
            // Student's answer
            array(
                'attemptstepid' => $stepid,
                'name' => 'answer',
                'value' => $original_answer
            ),
            array(
                'attemptstepid' => $stepid,
                'name' => 'answerformat',
                'value' => $original_format ? $original_format : '1'
            ),
            // Comment data
            array(
                'attemptstepid' => $stepid,
                'name' => '-comment',
                'value' => $comment
            ),
            array(
                'attemptstepid' => $stepid,
                'name' => '-commentformat',
                'value' => '1'
            ),
            // Mark data
            array(
                'attemptstepid' => $stepid,
                'name' => '-mark',
                'value' => $actual_mark
            ),
            array(
                'attemptstepid' => $stepid,
                'name' => '-maxmark',
                'value' => $max_mark
            ),
            // Additional required data
            array(
                'attemptstepid' => $stepid,
                'name' => 'finish',
                'value' => '1'
            )
        );

        // Insert all step data entries
        foreach ($step_data_entries as $entry) {
            $DB->insert_record('question_attempt_step_data', (object)$entry);
        }

        // Update the sumgrades in quiz_attempts
        $qa_record = $DB->get_record('question_attempts', ['id' => $attemptid]);
        if ($qa_record) {
            $quiz_attempt = $DB->get_record('quiz_attempts', ['uniqueid' => $qa_record->questionusageid]);
            if ($quiz_attempt) {
                // Get sum of all question grades for this attempt
                $sum_grades = $DB->get_field_sql(
                    "SELECT SUM(qas.fraction * qa.maxmark) 
                     FROM {question_attempt_steps} qas
                     JOIN {question_attempts} qa ON qa.id = qas.questionattemptid
                     WHERE qa.questionusageid = ? 
                     AND qas.state LIKE 'mangr%'
                     AND qas.sequencenumber = (
                         SELECT MAX(sequencenumber) 
                         FROM {question_attempt_steps} 
                         WHERE questionattemptid = qa.id
                     )",
                    array($qa_record->questionusageid)
                );
                
                if ($sum_grades !== false) {
                    $DB->set_field('quiz_attempts', 'sumgrades', $sum_grades, ['id' => $quiz_attempt->id]);
                    mtrace("Updated quiz attempt sumgrades to: $sum_grades");
                }
            }
        }

        mtrace("Grading completed successfully for attempt $attemptid");
        return true;

    } catch (Exception $e) {
        mtrace("Error during manual grading: " . $e->getMessage());
        return false;
    }
}

function aiquiz_question_updated($questionid) {
    global $DB, $USER, $CFG;

    $logFilePath = $CFG->dataroot . '/aiquiz_debug.log';

    $message = "question with ID {$questionid} performed an action at " . date('Y-m-d H:i:s');
    error_log($message . PHP_EOL, 3, $logFilePath);
    
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
        //return false;
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
        //$api_client = new api_client();
        $api_client = new \local_aiquiz\api_client();

        // Initialize the question data structure
        $updated_question  = $question->questiontext."Updated".$questionid;
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



function aiquiz_question_sync_or($questionid,$initialquestionid) {
    global $DB, $USER, $CFG;

    $logFilePath = $CFG->dataroot . '/aiquiz_debug.log';

    $message = "question with ID {$questionid} performed an action at " . date('Y-m-d H:i:s');
    error_log($message . PHP_EOL, 3, $logFilePath);
    
    $question = $DB->get_record('question', array('id' => $questionid));
    if (!$question) {
        //debugging("Question not found", DEBUG_DEVELOPER);
        return false;
    }

    // Check if this is an AI-generated question
    if (empty($question->aiquiz_id)) {
       // debugging("Not an AI question - skipping", DEBUG_DEVELOPER);
        return false;
    }

    // Get quiz metadata using JSON search
    $sql = "SELECT * FROM {local_aiquiz_metadata} 
            WHERE question_ids LIKE :questionpattern";
    
    // Create the pattern to search for the question ID in the JSON array
    $pattern = '%' . $initialquestionid . '%';
    $metadata = $DB->get_record_sql($sql, array('questionpattern' => $pattern));

    if (!$metadata) {
        //debugging("Metadata not found for question".$initialquestionid , DEBUG_DEVELOPER);
        //return false;
    }

    try {
        // Update the question_ids array in metadata
        $question_ids = json_decode($metadata->question_ids, true); // Convert JSON string to array
        
        // Check if question ID doesn't exist in array
        if (!in_array($questionid, $question_ids)) {
            // Add the new question ID to the array
            $question_ids[] = (int)$questionid;
            
            // Convert back to proper format with brackets and no quotes
            $metadata->question_ids = '[' . implode(',', $question_ids) . ']';
            $DB->update_record('local_aiquiz_metadata', $metadata);
            
            //debugging("Added question ID {$questionid} to metadata", DEBUG_DEVELOPER);
        }
        // Create API client
        //$api_client = new api_client();
        $api_client = new \local_aiquiz\api_client();

        // Initialize the question data structure
        $updated_question  = $question->questiontext;
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

        //debugging("Sending API request with data: " . json_encode($api_data), DEBUG_DEVELOPER);
        print_r($api_data);
        // Call API to update question
        $response = $api_client->update_question($metadata->exam_id, $question->aiquiz_id, $api_data);
        
        // Log the response
        //debugging("API Response: " . json_encode($response), DEBUG_DEVELOPER);
        
        return true;
    } catch (\Exception $e) {
        //debugging("API Error: " . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

function aiquiz_question_sync($questionid, $initialquestionid) {
    global $DB, $USER, $CFG;

    $logFilePath = $CFG->dataroot . '/aiquiz_debug.log';
    //echo $message = "question with ID {$questionid} performed an action at " . date('Y-m-d H:i:s');
    //error_log($message . PHP_EOL, 3, $logFilePath);
    
    $question = $DB->get_record('question', array('id' => $questionid));
    if (!$question) {
        return false;
    }

    if (empty($question->aiquiz_id)) {
        return false;
    }

    // Get metadata
    $sql = "SELECT * FROM {local_aiquiz_metadata} 
            WHERE question_ids LIKE :questionpattern";
    $pattern = '%' . $initialquestionid . '%';
    $metadata = $DB->get_record_sql($sql, array('questionpattern' => $pattern));
    //print_r($metadata);
    try {
        // Update metadata if needed
        if ($metadata) {
            $question_ids = json_decode($metadata->question_ids, true);
            if (!in_array($questionid, $question_ids)) {
                $question_ids[] = (int)$questionid;
                $metadata->question_ids = '[' . implode(',', $question_ids) . ']';
                $DB->update_record('local_aiquiz_metadata', $metadata);
            }
        }

        // Initialize API client
        $api_client = new \local_aiquiz\api_client();

        // Get the latest grade
        $sql = "SELECT qs.maxmark
        FROM {quiz_slots} qs
        JOIN {question_references} qr ON qr.itemid = qs.id AND qr.component = 'mod_quiz' AND qr.questionarea = 'slot'
        JOIN {question_bank_entries} qbe ON qbe.id = qr.questionbankentryid
        JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
        JOIN {question} q ON q.id = qv.questionid
        WHERE qs.quizid = :quizid 
        AND q.id = :questionid";

        $params = ['quizid' => $metadata->quiz_id, 'questionid' => $questionid];
        $maxmark = $DB->get_field_sql($sql, $params);
        
        
        //print_r($sql.$grade_record);
        // Initialize the API data structure exactly as specified
        $api_data = array(
            'question' => array(
                'question' => clean_param($question->questiontext, PARAM_TEXT)
            )
        );
       

         
        //print_r("grade_record ".$maxmark);
        // Add grade if available
        if ($maxmark) {
            
            $api_data['question']['grade'] = floatval(round($maxmark, 2)); // Convert to number
        }

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
                        $clean_answer = clean_param($answer->answer, PARAM_TEXT);
                        $options[] = $clean_answer;
                        if ($answer->fraction > 0) {
                            $correct_answers[] = $clean_answer;
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

        // Log the API data for debugging
        //error_log("API Data: " . json_encode($api_data) . PHP_EOL, 3, $logFilePath);

        //print_r($api_data);

        // Call API to update question
        $response = $api_client->update_question($metadata->exam_id, $question->aiquiz_id, $api_data);
        
        return true;
    } catch (\Exception $e) {
        //print_r($e->getMessage());
        error_log("API Error: " . $e->getMessage() . PHP_EOL, 3, $logFilePath);
        return false;
    }
}
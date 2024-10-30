<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/local/aiquiz/classes/api_client.php'); 
require_once($CFG->dirroot . '/local/aiquiz/classes/api_client_student.php');
require_once($CFG->dirroot . '/mod/quiz/classes/grade_calculator.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/essay/question.php');
require_once($CFG->dirroot . '/question/engine/states.php');
 

function aiquiz_get_student_answers($attemptobj) {
    global $DB;
    $answers = array();
    
    foreach ($attemptobj->get_slots() as $slot) {
        $qa = $attemptobj->get_question_attempt($slot);
        $question = $qa->get_question();
        $answer_object = $qa->get_last_qt_var('answer');

        // Extract the actual answer text
        $answer_text = '';
        if ($answer_object instanceof question_file_loader) {
            $answer_text = $answer_object->__toString();
        } elseif (is_object($answer_object) && method_exists($answer_object, 'get_value')) {
            $answer_text = $answer_object->get_value();
        } elseif (is_string($answer_object)) {
            $answer_text = $answer_object;
        } else {
            $answer_text = 'Unable to extract answer';
        }

        $quizdata = $DB->get_record('question', array('id' => $question->id, 'qtype' => 'essay'));
        $clean_answer_text = strip_tags($answer_text);
        if (!empty($quizdata->aiquiz_id)) {
            $answers[] = array(
                'questionId' => $quizdata->aiquiz_id,
                'answer' => $clean_answer_text
            );
        }
    }
    print_r($answers);
    return $answers;
}

 
function aiquiz_evaluate_attempt($attemptid, $auto = false) {
    global $DB, $OUTPUT, $USER, $PAGE;

    // Add required JavaScript and CSS for loader
    $PAGE->requires->js_amd_inline("
        require(['jquery'], function($) {
            // Add loader styles dynamically
            $('<style>')
                .text(`
                    #aiquiz-loader {
                        position: fixed;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        background-color: rgba(255, 255, 255, 0.95);
                        padding: 20px;
                        border-radius: 5px;
                        box-shadow: 0 0 10px rgba(0,0,0,0.2);
                        z-index: 9999;
                        min-width: 300px;
                        text-align: center;
                    }
                    #aiquiz-progress-container {
                        width: 100%;
                        margin: 10px 0;
                    }
                    #aiquiz-progress-container .progress {
                        height: 20px;
                        margin: 0 auto;
                    }
                    #aiquiz-loader-text {
                        margin: 10px 0;
                        font-weight: bold;
                    }
                `)
                .appendTo('head');

            // Create and append loader HTML
            $('body').append(`
                <div id='aiquiz-loader' style='display:none;'>
                    <div class='spinner-border text-primary' role='status'>
                        <span class='sr-only'>Loading...</span>
                    </div>
                    <p id='aiquiz-loader-text' class='mt-2'>Evaluating quiz responses...</p>
                    <div id='aiquiz-progress-container'>
                        <div class='progress'>
                            <div id='aiquiz-progress-bar' class='progress-bar' role='progressbar' style='width: 0%'></div>
                        </div>
                    </div>
                </div>
            `);

            $('#aiquiz-loader').show();
            
            window.updateAIQuizProgress = function(percentage, message) {
                $('#aiquiz-progress-bar').css('width', percentage + '%');
                if (message) {
                    $('#aiquiz-loader-text').text(message);
                }
            };
        });
    ");

    $attemptobj = \mod_quiz\quiz_attempt::create($attemptid);
    $quizobj = $attemptobj->get_quizobj();
    $quiz = $attemptobj->get_quiz();
    $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);

    // Update loader progress
    $PAGE->requires->js_amd_inline("
        require(['jquery'], function($) {
            window.updateAIQuizProgress(10, 'Initializing evaluation...');
        });
    ");

    // Get the AI exam ID
    $metadata = $DB->get_record('local_aiquiz_metadata', array('quiz_id' => $quiz->id));
    if (!$metadata || empty($metadata->exam_id)) {
        $PAGE->requires->js_amd_inline("$('#aiquiz-loader').remove();");
        return false;
    }
    $exam_id = $metadata->exam_id;

    // Get the student's answers
    $answers = aiquiz_get_student_answers($attemptobj);

    // Update loader progress
    $PAGE->requires->js_amd_inline("
        require(['jquery'], function($) {
            window.updateAIQuizProgress(20, 'Processing student answers...');
        });
    ");

    if (empty($answers)) {
        $PAGE->requires->js_amd_inline("$('#aiquiz-loader').remove();");
        return false;
    }

    $user = $DB->get_record('user', array('id' => $attemptobj->get_userid()), '*', MUST_EXIST);
    $student_details = [
        'fullName' => fullname($user),
        'id' => $user->id,
        'email' => $user->email
    ];

    // Call the API to evaluate the exam
    $api_client = new \local_aiquiz\api_client_student();
    try {
        // Update loader progress
        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                window.updateAIQuizProgress(40, 'Evaluating answers using AI...');
            });
        ");

        $api_response = $api_client->evaluate_exam($exam_id, $answers, $student_details);
        
        if (!isset($api_response['response']) || !isset($api_response['response']['answers'])) {
            $PAGE->requires->js_amd_inline("$('#aiquiz-loader').remove();");
            return false;
        }
        
        $evaluation_result = $api_response['response'];
        
        // Update loader progress
        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                window.updateAIQuizProgress(60, 'Processing evaluation results...');
            });
        ");

        $total_questions = count($evaluation_result['answers']);
        $current_question = 0;

        foreach ($evaluation_result['answers'] as $answer) {
            $current_question++;
            $progress = 60 + ($current_question / $total_questions * 30);
            
            // Update loader progress for each question
            $PAGE->requires->js_amd_inline("
                require(['jquery'], function($) {
                    window.updateAIQuizProgress($progress, 'Processing question $current_question of $total_questions...');
                });
            ");

            $question = $DB->get_record('question', array('aiquiz_id' => $answer['question_id']), '*', MUST_EXIST);
            
            // Find the slot number for this question
            $slot = null;
            foreach ($attemptobj->get_slots() as $qslot) {
                if ($attemptobj->get_question_attempt($qslot)->get_question()->id == $question->id) {
                    $slot = $qslot;
                    break;
                }
            }
            
            if (!$slot) {
                mtrace("Slot not found for question ID: {$answer['question_id']}");
                continue;
            }
            
            $qa = $attemptobj->get_question_attempt($slot);
            $questionattemptid = $qa->get_database_id();
            if (!$qa) {
                mtrace("Question attempt not found for question ID: {$answer['question_id']}");
                continue;
            }

            $grade = isset($answer['grade']) ? floatval($answer['grade']) : 0;
            $max_mark = $qa->get_max_mark();
            $grade = max(0, min($grade, $max_mark));
            $fraction = $max_mark > 0 ? $grade / $max_mark : 0;
            $fraction = max(0, min(1, $fraction));
            $comment = isset($answer['teacher_feedback']) ? $answer['teacher_feedback'] : '';

            mtrace("Updating question {$answer['question_id']}: Grade = $grade / $max_mark (Fraction: $fraction)");
            custom_manual_grade($questionattemptid, $question->id, $comment, $fraction, $max_mark, $USER->id);
        }

        // Recalculate the overall grade
        $quizobj = \mod_quiz\quiz_settings::create($quiz->id);
        $grade_calculator = \mod_quiz\grade_calculator::create($quizobj);
        $grade_calculator->recompute_final_grade($attemptobj->get_userid());

        // Update quiz grades in gradebook
        quiz_update_grades($quiz, $attemptobj->get_userid());

        // Update loader for completion
        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                window.updateAIQuizProgress(100, 'Evaluation completed successfully!');
                setTimeout(function() {
                    $('#aiquiz-loader').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 1000);
            });
        ");

        // Trigger the attempt_reviewed event
        $params = array(
            'objectid' => $attemptobj->get_attemptid(),
            'relateduserid' => $attemptobj->get_userid(),
            'courseid' => $course->id,
            'context' => \context_module::instance($cm->id),
            'other' => array(
                'quizid' => $quiz->id
            )
        );
        $event = \mod_quiz\event\attempt_reviewed::create($params);
        $event->trigger();

        return $evaluation_result;

    } catch (\Exception $e) {
        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                $('#aiquiz-loader-text').text('Error: " . addslashes($e->getMessage()) . "');
                $('#aiquiz-progress-bar').addClass('bg-danger');
                setTimeout(function() {
                    $('#aiquiz-loader').fadeOut('slow', function() {
                        $(this).remove();
                    });
                }, 3000);
            });
        ");
        mtrace("AIQuiz evaluation failed for attempt $attemptid: " . $e->getMessage());
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
        $stepdata->state = 'mangrright';  // Use 'mangrright' for correct answers, 'mangrwrong' for incorrect
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
        //return false;
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
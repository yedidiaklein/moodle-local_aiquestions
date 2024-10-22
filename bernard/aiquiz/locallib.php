<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/local/aiquiz/classes/api_client.php'); 
require_once($CFG->dirroot . '/mod/quiz/classes/grade_calculator.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/question/type/essay/question.php');
require_once($CFG->dirroot . '/question/engine/states.php');
 
function aiquiz_evaluate_attempt_original($attemptid, $auto = false) {
    global $DB, $OUTPUT, $USER;
 

    $attemptobj = \mod_quiz\quiz_attempt::create($attemptid);
    $quizobj = $attemptobj->get_quizobj();
    $quiz = $attemptobj->get_quiz();
    $course = $DB->get_record('course', array('id' => $quiz->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('quiz', $quiz->id, $course->id, false, MUST_EXIST);

    // Get the AI exam ID
    $metadata = $DB->get_record('local_aiquiz_metadata', array('quiz_id' => $quiz->id));
    if (!$metadata || empty($metadata->exam_id)) {
         
        return false;
    }
    $exam_id = $metadata->exam_id;
     

    // Get the student's answers
    $answers = aiquiz_get_student_answers($attemptobj);

    print_r($answers);

    // Check if we have any answers with AIQuiz IDs
    if (empty($answers)) {
         
        return false;
    }
     

    $user = $DB->get_record('user', array('id' => $attemptobj->get_userid()), '*', MUST_EXIST);
    $student_details = [
        'fullName' => fullname($user),
        'id' => $user->id,
        'email' => $user->email
    ];

    // Call the API to evaluate the exam
    $api_client = new \local_aiquiz\api_client();
    try {
        
        $api_response = $api_client->evaluate_exam($exam_id, $answers, $student_details);
        
        
        // Check if the api_response contains the 'response' key
        if (!isset($api_response['response']) || !isset($api_response['response']['answers'])) {
             
            return false;
        }
        
        $evaluation_result = $api_response['response'];
        
        //$transaction = $DB->start_delegated_transaction();

        foreach ($evaluation_result['answers'] as $answer) {
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

            // Ensure grade is a valid number
            $grade = isset($answer['grade']) ? floatval($answer['grade']) : 0;
            
            // Ensure grade is within the valid range (0 to max mark)
            $max_mark = $qa->get_max_mark();
            $grade = max(0, min($grade, $max_mark));

            // Convert grade to a fraction (ensure it's between 0 and 1)
            $fraction = $max_mark > 0 ? $grade / $max_mark : 0;
            $fraction = max(0, min(1, $fraction));

            $comment = isset($answer['teacher_feedback']) ? $answer['teacher_feedback'] : '';

            mtrace("Updating question {$answer['question_id']}: Grade = $grade / $max_mark (Fraction: $fraction)");
            custom_manual_grade($questionattemptid,  $question->id, $comment, $fraction,  $max_mark, $USER->id); 
            /*try {
                // Update the question attempt
                $qa->manual_grade(
                    'AI Grader',
                    $comment,
                    $fraction,
                    FORMAT_HTML
                );

                // Process the manual grading
                $attemptobj->process_submitted_actions(time());

            } catch (Exception $e) {
                mtrace("Error updating question {$answer['question_id']}: " . $e->getMessage());
                continue;
            }*/
        }

        // Recalculate the overall grade for the quiz attempt
        //quiz_save_best_grade($quiz, $attemptobj->get_userid());

        // Update the quiz_attempts table with the new total
        //$new_sum_marks = $attemptobj->get_sum_marks();
        //$DB->set_field('quiz_attempts', 'sumgrades', $new_sum_marks, array('id' => $attemptid));
       // mtrace("Updated total grade for attempt: $new_sum_marks");

        // Store general feedback if available
        //if (isset($evaluation_result['general_feedback'])) {
        //    $DB->set_field('quiz_attempts', 'feedback', $evaluation_result['general_feedback'], array('id' => $attemptid));
        ////    mtrace("Stored general feedback");
        //}

        //$transaction->allow_commit();
        
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
        mtrace("Triggered attempt_reviewed event");

        mtrace("AIQuiz evaluation completed successfully for attempt $attemptid");
        return $evaluation_result;
    } catch (\Exception $e) {
        if (isset($transaction)) {
            $transaction->rollback($e);
        }
        mtrace("AIQuiz evaluation failed for attempt $attemptid: " . $e->getMessage());
        return false;
    }
}

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
    return $answers;
}
function custom_manual_grade_original($attemptid, $questionid, $comment, $fraction,  $max_mark, $graderid = null) {
    global $DB, $USER;

    // Set the grader ID to the current user if not provided
    if ($graderid === null) {
        $graderid = $USER->id;
    }

    // Update the question_attempts table
    $DB->update_record('question_attempts', array(
        'id' => $attemptid,
        'maxmark' => $fraction,
        'manualcomment' => $comment,
        'graded' => 1
    ));

    // Create a new step in the question_attempt_steps table
    $stepdata = new stdClass();
    $stepdata->questionattemptid = $attemptid;
    $stepdata->sequencenumber = $DB->count_records('question_attempt_steps', array('questionattemptid' => $attemptid)) + 1;
    $stepdata->state = 'mangrwrong';
    $stepdata->fraction = $fraction;
    $stepdata->userid = $graderid;
    $stepdata->timecreated = time(); // Set the current time for timecreated field
    $stepid = $DB->insert_record('question_attempt_steps', $stepdata);

    // Insert the comment as step data
    $step_data_entry = new stdClass();
    $step_data_entry->attemptstepid = $stepid;
    $step_data_entry->name = '-comment';
    $step_data_entry->value = $comment;
    $DB->insert_record('question_attempt_step_data', $step_data_entry); 

    $step_data_entry = new stdClass();
    $step_data_entry->attemptstepid = $stepid;
    $step_data_entry->name = '-commentformat';
    $step_data_entry->value = '1';
    $DB->insert_record('question_attempt_step_data', $step_data_entry);

    // Insert the grade as step data
    $step_data_entry = new stdClass();
    $step_data_entry->attemptstepid = $stepid;
    $step_data_entry->name = '-mark';
    $step_data_entry->value = $fraction;
    $DB->insert_record('question_attempt_step_data', $step_data_entry); 

    $step_data_entry = new stdClass();
    $step_data_entry->attemptstepid = $stepid;
    $step_data_entry->name = '-maxmark';
    $step_data_entry->value = $max_mark;
    $DB->insert_record('question_attempt_step_data', $step_data_entry);

    return true;
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
    $api_client = new \local_aiquiz\api_client();
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
        // Update the question_attempts table
        $DB->update_record('question_attempts', array(
            'id' => $attemptid,
            'maxmark' => $max_mark,
            'minfraction' => 0,
            'maxfraction' => 1,
            'rightanswer' => '',
            'responsesummary' => '',
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
                'name' => 'answer',
                'value' => ''
            ),
            array(
                'attemptstepid' => $stepid,
                'name' => 'answerformat',
                'value' => '1'
            ),
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
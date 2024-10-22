<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
function local_aiquiz_extend_settings_navigation($settingsnav, $context) {
    global $PAGE, $CFG;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    // Only let users with the appropriate capability see this settings item.
    if (!has_capability('mod/quiz:manage', $context)) {
        return;
    }

    if ($settingnode = $settingsnav->find('modulesettings', navigation_node::TYPE_SETTING)) {
        $strgenerate = get_string('generateaiquestions', 'local_aiquiz');
        $url = new moodle_url('/local/aiquiz/generate.php', array('cmid' => $PAGE->cm->id));
        $generatenode = navigation_node::create(
            $strgenerate,
            $url,
            navigation_node::TYPE_SETTING,
            'generateaiquestions',
            'generateaiquestions',
            new pix_icon('t/add', '')
        );
        if ($PAGE->cm->modname === 'quiz') {
            $settingnode->add_node($generatenode);
        }
    }
}

function local_aiquiz_generate_questions($formdata, $quizid) {
    global $DB, $USER, $COURSE;

    $quiz = $DB->get_record('quiz', array('id' => $quizid), '*', MUST_EXIST);
    $api_client = new \local_aiquiz\api_client();
    
    $topic = '';
    //print_r("api_endpoint".$formdata);
    // Handle file upload if present
    if (!empty($formdata->uploadedfile) && $formdata->uploadedfile instanceof stored_file) {
        $file = $formdata->uploadedfile;
        $file_content = $file->get_content();
        $file_name = $file->get_filename();
        $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);        
    
        // Determine API endpoint based on file type
        $api_endpoint = '';
        switch($file_extension) {
            case 'pptx':
                $api_endpoint = '/exports/pptx/read';
                $file_param_name = 'pptxFile';
                break;
            case 'pdf':
                $api_endpoint = '/exports/pdf/read';
                $file_param_name = 'pdfFile';
                break;
            case 'docx':
                $api_endpoint = '/exports/docx/read';
                $file_param_name = 'docxFile';
                break;
            case 'txt':
            case 'vtt':
                $api_endpoint = '/exports/txt/read';
                $file_param_name = 'txtFile';
                break;
            default:
                return array('error' => get_string('unsupportedfiletype', 'local_aiquiz'));
        }
    
        // Step 1: Save the file content to a temporary location
        $tempfilepath = make_temp_directory('local_aiquiz') . '/' . $file_name;
        $file->copy_content_to($tempfilepath);

        // Step 2: Use the api_client to send the file
        try {
            $file_content_response = $api_client->read_file_content($api_endpoint, $tempfilepath, $file_name, $file_param_name);
            if (isset($file_content_response['text'])) {
                $topic = $file_content_response['text'];
            } else {
                return array('error' => get_string('filereadingerror', 'local_aiquiz', 'Invalid response format'));
            }
        } catch (Exception $e) {
            //return array('error' => get_string('filereadingerror', 'local_aiquiz', $e->getMessage()));
        } finally {
            // Clean up the temporary file
           unlink($tempfilepath);
        }
    }

    // Append or set manual topic if provided
    if (!empty($formdata->topic)) {
        $topic .= (empty($topic) ? '' : "\n\n") . $formdata->topic;
    }

    if (empty($topic)) {
        throw new \moodle_exception('notopicprovided', 'local_aiquiz');
    }

    // Process dynamic question fields
    $questions = array();
    $num_questions = isset($formdata->num_questions) ? intval($formdata->num_questions) : 10;
    
    for ($i = 1; $i <= $num_questions; $i++) {
        $question_type_key = "question_type_$i";
        $bloom_type_key = "bloom_type_$i";
        
        if (property_exists($formdata, $question_type_key) && property_exists($formdata, $bloom_type_key)) {
            $question_type = $formdata->$question_type_key;
            $bloom_type = $formdata->$bloom_type_key;
            if (!empty($question_type)) {
                $questions[] = array(
                    'type' => $question_type,
                    'bloomType' => $bloom_type
                );
            }
        } else {
            error_log("Missing data for question $i");
        }
    }
    //print_r($questions);
    if (empty($questions)) {
        error_log('No questions were added to the array');
        throw new \moodle_exception('noquestionsspecified', 'local_aiquiz');
    }
    $exam_data = $api_client->generate_exam(
        $topic,
        $questions,
        $formdata->difficulty,
        $formdata->language,
        $formdata->focus ?? '',
        $formdata->example_question ?? '',
        boolval($formdata->is_closed_content) ? true : false,
        boolval($formdata->use_indicator) ? true : false
    );
    //print_r($questions);
    if (!isset($exam_data['exam']['questions']) || !is_array($exam_data['exam']['questions'])) {
        throw new \moodle_exception('invalidapiresponse', 'local_aiquiz', '', 'Questions not found in API response');
    }

    $question_ids = local_aiquiz_save_questions_to_bank($exam_data['exam']['questions'], $quiz);
    $num_questions = count($question_ids);
    $metadata = $DB->get_record('local_aiquiz_metadata', array('quiz_id' => $quiz->id));
    if (!$metadata) {
        $metadata = new stdClass();
        $metadata->quiz_id = $quiz->id;
    }
    $metadata->exam_id = $exam_data['exam']['_id'];
    $metadata->question_ids = json_encode($question_ids);
    $metadata->topic = $topic;
    $metadata->num_questions = $num_questions;
    $metadata->difficulty = $formdata->difficulty;
    $metadata->timestamp = time();
    
    if (isset($metadata->id)) {
        $DB->update_record('local_aiquiz_metadata', $metadata);
    } else {
        $metadata->id = $DB->insert_record('local_aiquiz_metadata', $metadata);
    }
    
    
    foreach ($question_ids as $page => $question_id) {
        quiz_add_quiz_question($question_id, $quiz);
    }
    // Set the total grade for the quiz
    $total_grade = 100.00000;
    $quiz->grade = $total_grade;
    // Update the sumgrades attribute to reflect the total of the maximum marks for all questions
    $quiz->sumgrades = $total_grade;

    // Update the quiz record in the database
    $DB->update_record('quiz', $quiz);
    // Update the grades in the gradebook
    quiz_update_grades($quiz);

    // maxmarks is the maximum grade for each question
    $maxmark = $total_grade/count($question_ids);
    $DB->set_field('quiz_slots', 'maxmark', $maxmark, ['quizid' => $quiz->id]);
    return count($question_ids);
}

function local_aiquiz_save_questions_to_bank($questions, $quiz) {
    global $DB, $USER, $COURSE;

    $question_ids = array();

    $context = context_course::instance($COURSE->id);
    $top_category = question_get_top_category($context->id, true);

    // Create a new question category for this AI Quiz
    $category = new stdClass();
    $category->parent = $top_category->id;
    $category->name = 'AI Quiz: ' . $quiz->name;
    $category->contextid = $context->id;
    $category->info = 'Questions generated by AI for quiz: ' . $quiz->name;
    $category->sortorder = 999;
    $category->stamp = make_unique_id_code();
    $category->id = $DB->insert_record('question_categories', $category);

    $type_mapping = [
        'open_questions' => 'essay',
        'multiple_choice' => 'multichoice',
        'fill_in_the_blank' => 'shortanswer'
    ];

    foreach ($questions as $q) {
        if (!isset($type_mapping[$q['type']])) {
            debugging("Unsupported question type: " . $q['type'], DEBUG_DEVELOPER);
            continue;
        }
        $moodle_qtype = $type_mapping[$q['type']];

        $entry = new stdClass();
        $entry->questioncategoryid = $category->id;
        $entry->ownerid = $USER->id;
        $entry->created = time();
        $entry->modified = time();
        $entry->id = $DB->insert_record('question_bank_entries', $entry);

        $question = new stdClass();
        $question->category = $category->id;
        $question->parent = 0;
        $question->name = substr($q['question'], 0, 255);
        $question->questiontext = $q['question'];
        $question->aiquiz_id = $q['_id'];
        $question->questiontextformat = FORMAT_HTML;
        $question->generalfeedback = '';
        $question->generalfeedbackformat = FORMAT_HTML;
        $question->defaultmark = 1;
        $question->penalty = 0.3333333;
        $question->qtype = $moodle_qtype;
        $question->length = 1;
        $question->stamp = make_unique_id_code();
        $question->version = make_unique_id_code();
        $question->hidden = 0;
        $question->timecreated = time();
        $question->timemodified = time();
        $question->createdby = $USER->id;
        $question->modifiedby = $USER->id;
        
        $question->id = $DB->insert_record('question', $question);

        $version = new stdClass();
        $version->questionbankentryid = $entry->id;
        $version->questionid = $question->id;
        $version->version = 1;
        $version->status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
        $DB->insert_record('question_versions', $version);

        $question_ids[] = $question->id;

        switch ($moodle_qtype) {
            case 'multichoice':
                local_aiquiz_save_multichoice_answers($question->id, $q['options'], $q['correctAnswers']);
                break;
            case 'shortanswer':
                local_aiquiz_save_shortanswer_answers($question->id, $q['correctAnswers']);
                break;
            case 'essay':
                local_aiquiz_save_essay_options($question->id);
                break;
        }
    }

    return $question_ids;
}

function local_aiquiz_save_multichoice_answers($question_id, $options, $correct_answers) {
    global $DB;

    foreach ($options as $index => $option) {
        $answer = new stdClass();
        $answer->question = $question_id;
        $answer->answer = $option;
        $answer->answerformat = FORMAT_PLAIN;
        $answer->fraction = in_array($option, $correct_answers) ? 1 : 0;
        $answer->feedback = '';
        $answer->feedbackformat = FORMAT_PLAIN;
        
        $DB->insert_record('question_answers', $answer);
    }

    $options = new stdClass();
    $options->questionid = $question_id;
    $options->single = 1;
    $options->shuffleanswers = 1;
    $options->correctfeedback = '';
    $options->correctfeedbackformat = FORMAT_HTML;
    $options->partiallycorrectfeedback = '';
    $options->partiallycorrectfeedbackformat = FORMAT_HTML;
    $options->incorrectfeedback = '';
    $options->incorrectfeedbackformat = FORMAT_HTML;
    $options->answernumbering = 'abc';

    $DB->insert_record('qtype_multichoice_options', $options);
}

function local_aiquiz_save_shortanswer_answers($question_id, $correct_answers) {
    global $DB;

    foreach ($correct_answers as $answer) {
        $record = new stdClass();
        $record->question = $question_id;
        $record->answer = $answer;
        $record->answerformat = FORMAT_PLAIN;
        $record->fraction = 1;
        $record->feedback = '';
        $record->feedbackformat = FORMAT_HTML;
        
        $DB->insert_record('question_answers', $record);
    }

    $options = new stdClass();
    $options->questionid = $question_id;
    $options->usecase = 0;

    $DB->insert_record('qtype_shortanswer_options', $options);
}
function local_aiquiz_save_essay_options($question_id) {
    global $DB;

    $options = new stdClass();
    $options->questionid = $question_id;
    $options->responseformat = 'editor';
    $options->responserequired = 1;
    $options->responsefieldlines = 15;
    $options->attachments = 0;
    $options->attachmentsrequired = 0;
    $options->graderinfo = '';
    $options->graderinfoformat = FORMAT_HTML;
    $options->responsetemplate = '';
    $options->responsetemplateformat = FORMAT_HTML;

    $DB->insert_record('qtype_essay_options', $options);
}
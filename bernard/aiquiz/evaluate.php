<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/aiquiz/locallib.php');

$attemptid = required_param('attempt', PARAM_INT);

$attemptobj = \mod_quiz\quiz_attempt::create($attemptid);
$quiz = $attemptobj->get_quiz();
$cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, MUST_EXIST);
$course = $attemptobj->get_course();

require_login($course, false, $cm);
$context = context_module::instance($cm->id);

// Check if the user is allowed to view this attempt
if (!$attemptobj->is_own_attempt() && !has_capability('mod/quiz:viewreports', $context)) {
    throw new moodle_exception('nopermissions', 'error', '', 'view attempt');
}

$PAGE->set_url('/local/aiquiz/evaluate.php', array('attempt' => $attemptid));
$PAGE->set_title(get_string('evaluateattempt', 'local_aiquiz'));
$PAGE->set_heading($course->fullname);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('evaluateattempt', 'local_aiquiz'));

$evaluation_result = aiquiz_evaluate_attempt($attemptid);

if ($evaluation_result) {
    // Display the evaluation results
    echo html_writer::tag('h3', get_string('evaluationresults', 'local_aiquiz'));
    
    // Display overall grade
    echo html_writer::tag('h4', get_string('overallgrade', 'local_aiquiz'));
    echo html_writer::tag('p', $evaluation_result['grade'] . '/' . $quiz->grade);
    
    // Display general feedback
    if (isset($evaluation_result['general_feedback'])) {
        echo html_writer::tag('h4', get_string('generalfeedback', 'local_aiquiz'));
        echo html_writer::tag('p', $evaluation_result['general_feedback']);
    }
    
    // Display individual question results
    echo html_writer::tag('h4', get_string('questionresults', 'local_aiquiz'));
    foreach ($evaluation_result['answers'] as $answer) {
        $question = $DB->get_record('question', array('aiquiz_id' => $answer['question_id']), '*', MUST_EXIST);
        echo html_writer::start_tag('div', array('class' => 'question-container'));
        echo html_writer::tag('h5', $question->name);
        echo html_writer::tag('div', $question->questiontext, array('class' => 'question-text'));
        echo html_writer::tag('p', get_string('youranswer', 'local_aiquiz') . ': ' . $answer['answer']);
        echo html_writer::tag('p', get_string('grade', 'local_aiquiz') . ': ' . $answer['grade'] . '/' . $question->defaultmark);
        echo html_writer::tag('p', get_string('feedback', 'local_aiquiz') . ': ' . $answer['teacher_feedback']);
        echo html_writer::end_tag('div');
    }
} else {
    echo html_writer::tag('p', get_string('evaluationfailed', 'local_aiquiz'));
}

// Add Finish Review button
$finish_url = new moodle_url('/mod/quiz/view.php', array('id' => $cm->id));
echo html_writer::div(
    $OUTPUT->single_button($finish_url, get_string('finishreview', 'local_aiquiz'), 'get'),
    'finish-review-button'
);

echo $OUTPUT->footer();
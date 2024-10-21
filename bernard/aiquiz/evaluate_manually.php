<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/aiquiz/locallib.php');

require_login();

$attemptid = required_param('attemptid', PARAM_INT);

$PAGE->set_url(new moodle_url('/local/aiquiz/evaluate_manually.php', array('attemptid' => $attemptid)));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('AIQuiz Evaluation');
$PAGE->set_heading('AIQuiz Evaluation');

echo $OUTPUT->header();

// Get the attempt object
$attemptobj = \mod_quiz\quiz_attempt::create($attemptid);
$quiz = $attemptobj->get_quiz();
$cm = get_coursemodule_from_instance('quiz', $quiz->id, $attemptobj->get_courseid());
$course = get_course($cm->course);

// Check if the current user is allowed to view this attempt
$canview = $attemptobj->is_own_attempt() || has_capability('mod/quiz:grade', $attemptobj->get_quizobj()->get_context());

if (!$canview) {
    echo $OUTPUT->notification(get_string('nopermissions', 'error', get_string('viewattempt', 'quiz')), 'error');
    echo $OUTPUT->footer();
    exit;
}

// Check if the attempt is finished
if (!$attemptobj->is_finished()) {
    echo $OUTPUT->notification('This attempt is not yet finished and cannot be evaluated.', 'error');
    echo $OUTPUT->footer();
    exit;
}

// Perform the evaluation
$result = aiquiz_evaluate_attempt($attemptid, true);

if ($result) {
    echo $OUTPUT->notification('AIQuiz evaluation completed successfully.', 'success');
    
    // Display the results
    echo html_writer::start_tag('div', array('class' => 'aiquiz-results'));
    echo html_writer::tag('h3', 'Evaluation Results');
    echo html_writer::tag('p', 'Overall Grade: ' . $result['grade'] . '/' . $quiz->grade);
    
    if (!empty($result['general_feedback'])) {
        echo html_writer::tag('h4', 'General Feedback');
        echo html_writer::tag('p', $result['general_feedback']);
    }
    
    if (!empty($result['answers'])) {
        echo html_writer::tag('h4', 'Question Feedback');
        foreach ($result['answers'] as $answer) {
            echo html_writer::start_tag('div', array('class' => 'question-feedback'));
            echo html_writer::tag('h5', 'Question ' . $answer['question_id']);
            echo html_writer::tag('p', 'Grade: ' . $answer['grade']);
            echo html_writer::tag('p', 'Feedback: ' . $answer['teacher_feedback']);
            echo html_writer::end_tag('div');
        }
    }
    echo html_writer::end_tag('div');
} else {
    echo $OUTPUT->notification('AIQuiz evaluation failed. Please try again later or contact your instructor if the problem persists.', 'error');
}

// Add a button to return to the quiz
$return_url = new moodle_url('/mod/quiz/view.php', array('id' => $cm->id));
echo $OUTPUT->single_button($return_url, get_string('returnattempt', 'quiz'));

echo $OUTPUT->footer();
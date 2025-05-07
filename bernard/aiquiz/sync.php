<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot.'/lib/questionlib.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

$cmid = required_param('cmid', PARAM_INT);
$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/quiz:manage', $context);

// Set up page
$PAGE->set_url(new moodle_url('/local/aiquiz/sync.php', array('cmid' => $cmid)));
$PAGE->set_title(format_string($quiz->name));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('syncquestions', 'local_aiquiz'));

// Get initial questions
$initial_questions = local_aiquiz_get_initial_questions($cm->instance, $DB);

// Create table
$table = new html_table();
$table->head = array(
    get_string('questionid', 'local_aiquiz'),
    get_string('name', 'local_aiquiz'),
    get_string('type', 'local_aiquiz'),
    get_string('status', 'local_aiquiz') 
);

// Display questions
if (!empty($initial_questions)) {
    foreach ($initial_questions as $initial) {
        // Initial version row
        $initial_preview = new moodle_url('/question/preview.php', 
            array('id' => $initial->id, 'courseid' => $course->id));
        $initial_preview_link = html_writer::link(
            $initial_preview,
            get_string('preview'),
            array('class' => 'btn btn-secondary', 'target' => '_blank')
        );

        $table->data[] = array(
            $initial->id,
            format_string($initial->name),
            get_string('pluginname', 'qtype_' . $initial->qtype),
            html_writer::tag('span', get_string('initialversion', 'local_aiquiz'), 
                array('class' => 'badge badge-info')) 
        );

        

        // Get and display latest version
        $latest = local_aiquiz_get_latest_questions($initial->id, $DB);

        $metadata = $DB->get_records_sql(
            "SELECT m.* 
             FROM {local_aiquiz_metadata} m 
             WHERE m.quiz_id = :quizid 
             AND FIND_IN_SET(:questionid, m.question_ids)",
            ['quizid' => $cm->instance, 'questionid' => $initial ->id]
        );
        //print_r($metadata);
        // Only display if latest question is NOT in metadata
        if (empty($metadata)) {
            aiquiz_question_sync($latest->id,$initial->id);
        }
        if ($latest && $latest->id != $initial->id) {
            $latest_preview = new moodle_url('/question/preview.php', 
                array('id' => $latest->id, 'courseid' => $course->id));
            $latest_preview_link = html_writer::link(
                $latest_preview,
                get_string('preview'),
                array('class' => 'btn btn-secondary', 'target' => '_blank')
            );

            $table->data[] = array(
                $latest->id,
                format_string($latest->name),
                get_string('pluginname', 'qtype_' . $latest->qtype),
                html_writer::tag('span', 
                    get_string('latestversion', 'local_aiquiz') . ' (v' . $latest->version . ')', 
                    array('class' => 'badge badge-success')) 
            );
        }

        // Separator
        $table->data[] = array(
            html_writer::tag('div', '', array('class' => 'border-bottom my-2')),
            '', '', '', ''
        );
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();

 
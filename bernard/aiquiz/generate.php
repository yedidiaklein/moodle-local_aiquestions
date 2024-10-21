<?php
require_once('../../config.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/local/aiquiz/classes/form/generate_form.php');
require_once($CFG->dirroot . '/local/aiquiz/classes/api_client.php');
require_once($CFG->dirroot . '/local/aiquiz/lib.php');  // Add this line to include lib.php

$cmid = required_param('cmid', PARAM_INT);

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$quiz = $DB->get_record('quiz', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/quiz:manage', $context);

$PAGE->set_url('/local/aiquiz/generate.php', array('cmid' => $cm->id));
$PAGE->set_title(get_string('generateaiquestions', 'local_aiquiz'));
$PAGE->set_heading($course->fullname);
$PAGE->set_context($context);

 
$form = new \local_aiquiz\form\generate_form(null, array('cmid' => $cmid, 'default_name' => $quiz->name));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/mod/quiz/edit.php', array('cmid' => $cmid)));
} else if ($formdata = $form->get_data()) {
    // Process the form data here
    
    // Handle file upload
    $draftitemid = file_get_submitted_draft_itemid('uploadedfile');
    file_save_draft_area_files($draftitemid, $context->id, 'local_aiquiz', 'attachment', 0,
        array('subdirs' => 0, 'maxbytes' => 10485760, 'maxfiles' => 1));
    
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_aiquiz', 'attachment', 0, 'sortorder', false);
    
    if (!empty($files)) {
        $file = reset($files);
        if ($file->get_filesize() > 0) {
            $formdata->uploadedfile = $file;
        } else {
            $formdata->uploadedfile = null;
        }
    } else {
        $formdata->uploadedfile = null;
    }

    
    // Now call the function to generate questions
    $result = local_aiquiz_generate_questions($formdata, $quiz->id);

    if (is_array($result) && isset($result['error'])) {
        \core\notification::error($result['error']);
        redirect(new moodle_url('/local/aiquiz/generate.php', array('cmid' => $cm->id)));
    } else {
        \core\notification::success(get_string('questionsgenerated', 'local_aiquiz', $result));
        redirect(new moodle_url('/mod/quiz/edit.php', array('cmid' => $cm->id)));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('generateaiquestions', 'local_aiquiz'));
$form->display();
echo $OUTPUT->footer();
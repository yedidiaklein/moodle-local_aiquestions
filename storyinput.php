<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     local_aiquestions
 * @category    admin
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
defined('MOODLE_INTERNAL') || die();

// Get course id for creating the questions in it's bank.
$courseid = optional_param('courseid', 0, PARAM_INT);

if ($courseid == 0) {
    redirect(new moodle_url('/local/aiquestions/index.php'));
}

require_login($courseid);

// Check if the user has the capability to create questions.
$context = context_course::instance($courseid);
require_capability('moodle/question:add', $context);

require_once("$CFG->libdir/formslib.php");
require_once(__DIR__ . '/locallib.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_heading(get_string('pluginname', 'local_aiquestions'));
$PAGE->set_title(get_string('pluginname', 'local_aiquestions'));
$PAGE->set_url('/local/aiquestions/storyinput.php?courseid=' . $courseid);
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add(get_string('pluginname', 'local_aiquestions'), new moodle_url('/local/aiquestions/'));
$PAGE->navbar->add(get_string('story', 'local_aiquestions'),
                    new moodle_url('/local/aiquestions/storyinput.php?courseid=' . $courseid));
$PAGE->requires->js_call_amd('local_aiquestions/loading');

echo $OUTPUT->header();
/*
 * Form to get the story from the user.
 */
class storyinput_form extends moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $courseid;
        $mform = $this->_form;
        // This model's maximum context length is 4097 tokens. We limit the story to 4096 tokens.
        $mform->addElement('textarea', 'story', get_string('story', 'local_aiquestions'),
            'wrap="virtual" maxlength="16384" rows="10" cols="50"');
        $mform->setType('story', PARAM_RAW);
        $mform->setDefault('story', '' ); // Default value.

        $defaultnumofquestions = 4;
        $select = $mform->addElement('select', 'numofquestions', get_string('numofquestions', 'local_aiquestions'),
            array('1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10' => 10));
        $select->setSelected($defaultnumofquestions);
        $mform->setType('numofquestions', PARAM_INT);

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('generate', 'local_aiquestions'));
        $buttonarray[] =& $mform->createElement('cancel', 'cancel', get_string('cancel'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        $mform->addElement('html', '<img id="loading" src="pix/loading.gif"
                                     alt="aiquestions" width="100" height="100"
                                     style="display: none; margin:auto;">');

    }
    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        return array();
    }
}

$mform = new storyinput_form();

if ($mform->is_cancelled()) {
    if (empty($returnurl)) {
        redirect($CFG->wwwroot . '/local/aiquestions/');
    } else {
        redirect($returnurl);
    }
} else if ($fromform = $mform->get_data()) {
    $story = $fromform->story;
    $numofquestions = $fromform->numofquestions;

    // Try 10 times to create the questions.
    $created = false;
    $i = 0;
    // Future : move i to module settings - max number of attempts.
    while (!$created && $i < 10) {
        $questions = local_aiquestions_get_questions($courseid, $story, $numofquestions);

        // Print error message.
        if (isset($questions->error->message)) {
            echo "<br><p bgcolor='lightred'>" . $questions->error->message . "</p>";
        }
        // Check gift format.
        if (local_aiquestions_check_gift($questions->text)) {
            // Create the questions, return an array of objetcs of the created questions.
            $created = local_aiquestions_create_questions($courseid, $questions->text, $numofquestions);
            foreach ($created as $question) {
                echo "<p class='local_aiquestions_created'>" .
                    get_string('createdquestionwithid', 'local_aiquestions') . " : " . $question->id . "<br>";
                echo $question->name . "</p>";
            }
        }
        $i++;
    }
    // Show the link to the question bank.
    $datafortemplate = [
        'courseid' => $courseid,
        'attempts' => $i,
        'wwwroot' => $CFG->wwwroot,
    ];
    // Load the ready template.
    echo $OUTPUT->render_from_template('local_aiquestions/ready', $datafortemplate);
} else {
    $mform->display();
}

echo $OUTPUT->footer();

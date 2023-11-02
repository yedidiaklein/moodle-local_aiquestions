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
 * Story Form Class is defined here.
 *
 * @package     local_aiquestions
 * @category    admin
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

/**
 * Form to get the story from the user.
 *
 * @package     local_aiquestions
 * @category    admin
 */
class local_aiquestions_story_form extends moodleform {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $courseid;
        $mform = $this->_form;

        // Question category.
        $contexts = [context_course::instance($courseid)];
        $mform->addElement('questioncategory', 'category', get_string('category', 'question'),
            array('contexts'=>$contexts));

        // Number of questions.
        $defaultnumofquestions = 4;
        $select = $mform->addElement('select', 'numofquestions', get_string('numofquestions', 'local_aiquestions'),
            array('1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10' => 10));
        $select->setSelected($defaultnumofquestions);
        $mform->setType('numofquestions', PARAM_INT);

        // Story.
        $mform->addElement('textarea', 'story', get_string('story', 'local_aiquestions'),
            'wrap="virtual" maxlength="16384" rows="10" cols="50"'); // This model's maximum context length is 4097 tokens. We limit the story to 4096 tokens.
        $mform->setType('story', PARAM_RAW);
        $mform->addHelpButton('story', 'story', 'local_aiquestions');        

        // Preset.
        $presets = array();
        for ($i = 1; $i <= 10; $i++) {
            if ($presetname = get_config('local_aiquestions', 'presetname' . $i)) {
                $presets[] = $presetname;
            }
        }        
        $mform->addElement('select', 'preset', get_string('preset', 'local_aiquestions'), $presets);

        // Edit preset.
        $mform->addElement('checkbox', 'editpreset', get_string('editpreset', 'local_aiquestions'));        
        $mform->addElement('html', get_string('shareyourprompts', 'local_aiquestions'));        

        // Primer.
        $mform->addElement('textarea', 'primer', get_string('primer', 'local_aiquestions'),
            'wrap="virtual" maxlength="16384" rows="10" cols="50"');
        $mform->setType('primer', PARAM_RAW);
        $mform->setDefault('primer', get_config('local_aiquestions', 'defaultprimer'));
        $mform->addHelpButton('primer', 'primer', 'local_aiquestions');
        $mform->hideif('primer', 'editpreset');

        // Instructions.
        $mform->addElement('textarea', 'instructions', get_string('instructions', 'local_aiquestions'),
        'wrap="virtual" maxlength="16384" rows="10" cols="50"');
        $mform->setType('instructions', PARAM_RAW);
        $mform->setDefault('instructions', get_config('local_aiquestions', 'defaultinstructions'));
        $mform->addHelpButton('instructions', 'instructions', 'local_aiquestions');
        $mform->hideif('instructions', 'editpreset');

        // Example.
        $mform->addElement('textarea', 'example', get_string('example', 'local_aiquestions'),
        'wrap="virtual" maxlength="16384" rows="10" cols="50"');
        $mform->setType('example', PARAM_RAW);
        $mform->setDefault('example', get_config('local_aiquestions', 'defaultexample'));
        $mform->addHelpButton('example', 'example', 'local_aiquestions');
        $mform->hideif('example', 'editpreset');        

        // Courseid.
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $buttonarray = array();
        $buttonarray[] =& $mform->createElement('submit', 'submitbutton', get_string('generate', 'local_aiquestions'));
        $buttonarray[] =& $mform->createElement('cancel', 'cancel', get_string('backtocourse', 'local_aiquestions'));
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
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

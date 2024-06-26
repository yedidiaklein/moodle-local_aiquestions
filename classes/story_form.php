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
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com>, 
 *              Yedidia Klein <yedidia@openapp.co.il>
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
class local_aiquestions_story_form extends moodleform
{
    /**
     * Defines forms elements
     */
    public function definition()
    {
        global $courseid;
        $mform = $this->_form;

        // Question category.
        $contexts = [context_course::instance($courseid)];
        $mform->addElement(
            'questioncategory',
            'category',
            get_string('category', 'question'),
            ['contexts' => $contexts]
        );
        $mform->addHelpButton('category', 'category', 'local_aiquestions');

        // Number of questions.
        $defaultnumofquestions = 4;
        $select = $mform->addElement(
            'select',
            'numofquestions',
            get_string('numofquestions', 'local_aiquestions'),
            ['1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10' => 10]
        );
        $select->setSelected($defaultnumofquestions);
        $mform->setType('numofquestions', PARAM_INT);

        // Exam focus.
        $mform->addElement(
            'textarea',
            'examFocus',
            get_string('focus', 'local_aiquestions'),
            'wrap="virtual" rows="6" cols="10"'
        );
        $mform->setType('examFocus', PARAM_RAW);

        // Language.
        $defaultlanguage = "English";
        $select = $mform->addElement(
            'select',
            'examLanguage',
            get_string('languagedesc', 'local_aiquestions'),
            ['English' => "English", 'Hebrew' => "Hebrew", 'Hindi' => "Hindi", 'Spanish' => 'Spanish', 'German' => "German", 'French' => "French", 'Russian' => "Russian", 'Arabic' => "Arabic"]
        );
        $select->setSelected($defaultlanguage);
        $mform->setType('examLanguage', PARAM_RAW);

        // Text.
        $mform->addElement(
            'text',
            'text',
            get_string('text', 'local_aiquestions'),
            'wrap="virtual" rows="10" cols="10"'
        );
        $mform->setType('text', PARAM_RAW);

        // Field (exam type).
        $select = $mform->addElement(
            'select',
            'field',
            get_string('field', 'local_aiquestions'), // Move this to lang file later.
            ["Topic" => "topic", "Text" => "text", "Based On" => "based", "URL" => "url", "Math" => "math"] // Move the array to static config.
        );
        $select->setSelected("Topic");
        $mform->setType('field', PARAM_RAW);

        // Question level.
        $select = $mform->addElement(
            'select',
            'questionLevel',
            get_string('questionLevel', 'local_aiquestions'), // Move this to lang file later.
            ["Academic" => "Academic"] // Move the array to static config.
        );
        $select->setSelected("Academic");
        $mform->setType('questionLevel', PARAM_RAW);

        // Exam tags.
        $select = $mform->addElement(
            'select',
            'examTags',
            get_string('examTags', 'local_aiquestions'), // Move this to lang file later.
            ["Cognitive literacy" => "Cognitive literacy", "Mathematical literacy" => "Mathematical literacy", "Scientific literacy" => "Scientific literacy", "Critical Thinking" => "Critical Thinking"] // Move the array to static config.
        );
        $select->setMultiple(true);
        $select->setSelected(["Cognitive literacy"]);
        $mform->setType('examTags', PARAM_RAW);

        // Story.
        $mform->addElement(
            'textarea',
            'story',
            get_string('story', 'local_aiquestions'),
            'wrap="virtual" rows="10" cols="50"'
        ); // This model's maximum context length is 4097 tokens. We limit the story to 4096 tokens.
        $mform->setType('story', PARAM_RAW);
        $mform->addHelpButton('story', 'story', 'local_aiquestions');

        // Preset.
        $presets = [];
        for ($i = 0; $i < 10; $i++) {
            if ($presetname = get_config('local_aiquestions', 'presetname' . $i)) {
                $presets[] = $presetname;
            }
        }
        $mform->addElement('select', 'preset', get_string('preset', 'local_aiquestions'), $presets);

        // Edit preset.
        $mform->addElement('checkbox', 'editpreset', get_string('editpreset', 'local_aiquestions'));
        $mform->addElement('html', get_string('shareyourprompts', 'local_aiquestions'));

        // Create elements for all presets.
        for ($i = 0; $i < 10; $i++) {

            $primer = $i + 1;

            // Primer.
            $mform->addElement(
                'textarea',
                'primer' . $i,
                get_string('primer', 'local_aiquestions'),
                'wrap="virtual" rows="10" cols="50"'
            );
            $mform->setType('primer' . $i, PARAM_RAW);
            $mform->setDefault('primer' . $i, get_config('local_aiquestions', 'presettprimer' . $primer));
            $mform->addHelpButton('primer' . $i, 'primer', 'local_aiquestions');
            $mform->hideif('primer' . $i, 'editpreset');
            $mform->hideif('primer' . $i, 'preset', 'neq', $i);

            // Instructions.
            $mform->addElement(
                'textarea',
                'instructions' . $i,
                get_string('instructions', 'local_aiquestions'),
                'wrap="virtual" rows="10" cols="50"'
            );
            $mform->setType('instructions' . $i, PARAM_RAW);
            $mform->setDefault('instructions' . $i, get_config('local_aiquestions', 'presetinstructions' . $primer));
            $mform->addHelpButton('instructions' . $i, 'instructions', 'local_aiquestions');
            $mform->hideif('instructions' . $i, 'editpreset');
            $mform->hideif('instructions' . $i, 'preset', 'neq', $i);

            // Example.
            $mform->addElement(
                'textarea',
                'example' . $i,
                get_string('example', 'local_aiquestions'),
                'wrap="virtual" rows="10" cols="50"'
            );
            $mform->setType('example' . $i, PARAM_RAW);
            $mform->setDefault('example' . $i, get_config('local_aiquestions', 'presetexample' . $primer));
            $mform->addHelpButton('example' . $i, 'example', 'local_aiquestions');
            $mform->hideif('example' . $i, 'editpreset');
            $mform->hideif('example' . $i, 'preset', 'neq', $i);
        }

        // Courseid.
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $buttonarray = [];
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('generate', 'local_aiquestions'));
        $buttonarray[] = &$mform->createElement('cancel', 'cancel', get_string('backtocourse', 'local_aiquestions'));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files)
    {
        return [];
    }
}
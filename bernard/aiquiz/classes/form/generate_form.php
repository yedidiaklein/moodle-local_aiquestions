<?php
namespace local_aiquiz\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class generate_form extends \moodleform {
    public function definition() {
        global $CFG, $PAGE;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('exam_params', 'local_aiquiz'));

        // Adding the standard "name" field.
        // $mform->addElement('text', 'name', get_string('aiquizname', 'local_aiquiz'), array('size' => '64'));
        // if (!empty($CFG->formatstringstriptags)) {
        //     $mform->setType('name', PARAM_TEXT);
        // } else {
        //     $mform->setType('name', PARAM_CLEANHTML);
        // }
        // if (isset($this->_customdata['default_name'])) {
        //     $mform->setDefault('name', $this->_customdata['default_name']);
        // }
        // $mform->addRule('name', null, 'required', null, 'client');
        // $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Adding the "topic" field.
        $mform->addElement('textarea', 'topic', get_string('aiquiztopic', 'local_aiquiz'), 
                           array('rows' => 5, 'cols' => 60));
        $mform->setType('topic', PARAM_TEXT);
        //$mform->addRule('topic', null, 'required', null, 'client');
        $mform->addHelpButton('topic', 'aiquiztopic', 'local_aiquiz');

        // Adding file upload field
        /*$mform->addElement('filepicker', 'uploadedfile', get_string('uploadfile', 'local_aiquiz'), null, array(
            'maxbytes' => 10485760, // 10MB max
            'accepted_types' => array('.pptx', '.pdf', '.docx', '.txt', '.vtt')
        ));*/

        $mform->addElement('filemanager', 'uploadedfile', get_string('uploadfile', 'local_aiquiz'), null, array(
            'maxbytes' => 10485760, // 10MB max
            'accepted_types' => array('.pptx', '.pdf', '.docx', '.txt', '.vtt'),
            'maxfiles' => 1,
            'subdirs' => 0
        ));
        $mform->addHelpButton('uploadedfile', 'uploadfile', 'local_aiquiz');

        

        

        // Adding the "difficulty" field.
        $difficulties = array(
            '1st Grade' => '1st Grade',
            '2nd Grade' => '2nd Grade',
            '3rd Grade' => '3rd Grade',
            '4th Grade' => '4th Grade',
            '5th Grade' => '5th Grade',
            '6th Grade' => '6th Grade',
            '7th Grade' => '7th Grade',
            '8th Grade' => '8th Grade',
            '9th Grade' => '9th Grade',
            '10th Grade' => '10th Grade',
            '11th Grade' => '11th Grade',
            '12th Grade' => '12th Grade',
            'Academic' => 'Academic'
        );
        $mform->addElement('select', 'difficulty', get_string('difficulty', 'local_aiquiz'), $difficulties);
        $mform->setDefault('difficulty', '10th Grade');
        $mform->addRule('difficulty', null, 'required', null, 'client');

        // Adding the "language" field.
        $languages = array(
            'English' => 'English',
            'Hebrew' => 'Hebrew',
            'Hindi' => 'Hindi',
            'Spanish' => 'Spanish',
            'German' => 'German',
            'French' => 'French',
            'Russian' => 'Russian',
            'Italian' => 'Italian',
            'Dutch' => 'Dutch',
            'Arabic' => 'Arabic'
        );
        $mform->addElement('select', 'language', get_string('language', 'local_aiquiz'), $languages);
        $mform->setDefault('language', 'English');
        $mform->addRule('language', null, 'required', null, 'client');

        // Adding the "focus" field.
        $mform->addElement('textarea', 'focus', get_string('focus', 'local_aiquiz'), array('rows' => 3, 'cols' => 60));
        $mform->setType('focus', PARAM_TEXT);

        // Adding the "example_question" field.
        $mform->addElement('textarea', 'example_question', get_string('examplequestion', 'local_aiquiz'), 
                           array('rows' => 3, 'cols' => 60));
        $mform->setType('example_question', PARAM_TEXT);

        // Adding the "is_closed_content" field.
        $mform->addElement('advcheckbox', 'is_closed_content', get_string('isclosedcontent', 'local_aiquiz'), 
                           get_string('isclosedcontentdesc', 'local_aiquiz'), array(), array(0, 1));
        $mform->setDefault('is_closed_content', 0);

        // Adding the "use_indicator" field.
        $mform->addElement('advcheckbox', 'use_indicator', get_string('use_indicator', 'local_aiquiz'), 
                           get_string('use_indicator_desc', 'local_aiquiz'), array(), array(0, 1));
        $mform->setDefault('use_indicator', 1);

        // Adding question specification fields
        $mform->addElement('header', 'questionchoice', get_string('questionchoice', 'local_aiquiz')); 

        $question_types = array(
            '' => '--',
            'open_questions' => get_string('openquestions', 'local_aiquiz'),
            'multiple_choice' => get_string('multiplechoice', 'local_aiquiz'),
            'fill_in_the_blank' => get_string('fillintheblank', 'local_aiquiz')
        );

        $bloom_types = array(
            'knowledge' => get_string('knowledge', 'local_aiquiz'),
            'comprehension' => get_string('comprehension', 'local_aiquiz'),
            'application' => get_string('application', 'local_aiquiz'),
            'analysis' => get_string('analysis', 'local_aiquiz'),
            'evaluation' => get_string('evaluation', 'local_aiquiz'),
            'creation' => get_string('creation', 'local_aiquiz')
        );

        for ($i = 1; $i <= 10; $i++) {
            $question_group = array();
            $question_group[] = $mform->createElement('select', "question_type_$i", get_string('questiontype', 'local_aiquiz'), $question_types);
            $question_group[] = $mform->createElement('select', "bloom_type_$i", get_string('bloomtype', 'local_aiquiz'), $bloom_types);
            $mform->addGroup($question_group, "question_group_$i", get_string('questionnumber', 'local_aiquiz', $i), array(' '), false);
            
            // Make only the first question required
            if ($i == 1) {
                $mform->addRule("question_group_$i", null, 'required', null, 'client');
            }
        }

        $mform->addElement('text', 'numoftries', get_string('numoftries', 'local_aiquiz'));
        $mform->setType('numoftries', PARAM_INT);
        $mform->addRule('numoftries', null, 'required', null, 'client');
        $mform->addRule('numoftries', get_string('numericalvalue', 'local_aiquiz'), 'numeric', null, 'client');
        $mform->setDefault('numoftries', 1);

        $mform->addElement('hidden', 'cmid', $this->_customdata['cmid']);
        $mform->setType('cmid', PARAM_INT);

        $this->add_action_buttons();

        // Add JavaScript for loading overlay
        $PAGE->requires->js_amd_inline($this->get_js_code());
    }
    
    protected function get_js_code() {
        return "
            require(['jquery'], function($) {
                $('#id_submitbutton').click(function() {
                    if ($('#id_topic').val()) {
                        // Add a loading overlay with a progress bar
                        $('body').append(`
                            <div id=\"loading-overlay\" style=\"position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 9999;\">
                                <div style=\"position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; color: white;\">
                                    <p style=\"font-size: 18px;\">Please wait. Quiz generating...</p>
                                    <div style=\"width: 100%; background-color: #ccc; border-radius: 10px; overflow: hidden; margin-top: 10px;\">
                                        <div id=\"progress-bar\" style=\"width: 0%; height: 20px; background-color: #4caf50; border-radius: 10px;\"></div>
                                    </div>
                                </div>
                            </div>
                        `);

                        // Animate the progress bar
                        var progress = 0;
                        var interval = setInterval(function() {
                            if (progress < 100) {
                                progress += 5;  // Increment the progress by 5%
                                $('#progress-bar').css('width', progress + '%');
                            } else {
                                clearInterval(interval);  // Stop the animation at 100%
                            }
                        }, 2000);  // Update every 2000ms
                    }
                });
            });
        ";
    }

    public function definition_after_data() {
        global $USER;
        
        $mform = $this->_form;
        
        // Prepare the draft file area
        $draftitemid = file_get_submitted_draft_itemid('uploadedfile');
        file_prepare_draft_area($draftitemid, $this->_customdata['context']->id, 'local_aiquiz', 'attachment', 
            0, array('subdirs' => 0, 'maxfiles' => 1));
        $mform->setDefault('uploadedfile', $draftitemid);
    }
}
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

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aiquestions_settings', new lang_string('pluginname', 'local_aiquestions'));

    // OpenAI key.
    $settings->add( new admin_setting_configpasswordunmask(
        'local_aiquestions/key',
        get_string('openaikey', 'local_aiquestions'),
        get_string('openaikeydesc', 'local_aiquestions'),
        '', PARAM_TEXT, 50
    ));

    // Default primer.
    $default = "You are a helpful teacher's assistant that creates multiple choice questions based on the topics given by the user.";
    $settings->add( new admin_setting_configtextarea(
        'local_aiquestions/defaultprimer',
        get_string('defaultprimer', 'local_aiquestions'),
        get_string('defaultprimerdesc', 'local_aiquestions'),
        $default, PARAM_TEXT, 4000
    ));

    // Default instructions.
    $default = "Please write a multiple choice question in English language";
    $default .= " in GIFT format on a topic I will specify to you separately";
    $default .= " GIFT format use equal sign for right answer and tilde sign for wrong answer at the beginning of answers.";
    $default .= " For example: '::Question title:: Question text { =right answer#feedback ~wrong answer#feedback ~wrong answer#feedback ~wrong answer#feedback }' ";
    $default .= " Please have a blank line between questions. ";
    $default .= " Do not include the question title in the beginning of the question text. ";
    $settings->add( new admin_setting_configtextarea(
        'local_aiquestions/defaultinstructions',
        get_string('defaultinstructions', 'local_aiquestions'),
        get_string('defaultinstructionsdesc', 'local_aiquestions'),
        $default, PARAM_TEXT, 4000
    ));

    // Default example.
    $default = "::Indexicality and iconicity 1:: Imagine that you are standing on a lake shore. A wind rises, creating waves on the lake surface. According to C.S. Peirce, in what way the waves signify the wind? { =The relationship is both indexical and iconical.#Correct. There is a connection of spatio-temporal contiguity between the wind and the waves, which is a determining feature of indexicality. There is also a formal resemblance between wind direction and the direction of the waves, which is a determining feature of iconicity.  ~The relationship is indexical.#Almost correct. There is a connection of spatio-temporal contiguity between the wind and the waves, which, according to Peirce, is a determining feature of indexicality. However, there is additional signification taking place as well. ~There is no sign phenomena betweem the wind and the waves, they are two separate signs.#Incorrect. The movement of the waves is determined by the wind. ~The relationship between the wind and the waves is symbolic.#Incorrect. The movement of the waves is not arbitrary, which would be the case if the relationship was symbolic. }";
    $settings->add( new admin_setting_configtextarea(
        'local_aiquestions/defaultexample',
        get_string('defaultexample', 'local_aiquestions'),
        get_string('defaultexampledesc', 'local_aiquestions'),
        $default, PARAM_TEXT, 4000
    ));

    // Number of tries.
    $settings->add( new admin_setting_configtext(
        'local_aiquestions/numoftries',
        get_string('numoftriesset', 'local_aiquestions'),
        get_string('numoftriesdesc', 'local_aiquestions'),
        10, PARAM_INT, 10
    ));

    // Language.
    $languages = get_string_manager()->get_list_of_languages();
    asort($languages);
    $settings->add(new admin_setting_configselect(
        'local_aiquestions/language',
        get_string('language', 'local_aiquestions'),
        get_string('languagedesc', 'local_aiquestions'),
        'en', $languages
    ));


    $ADMIN->add('localplugins', $settings);

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // TODO: Define actual plugin settings page and add it to the tree - {@link https://docs.moodle.org/dev/Admin_settings}.
    }
}

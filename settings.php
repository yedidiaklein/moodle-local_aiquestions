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

    // Model.
    $options = ['gpt-3.5-turbo' => 'gpt-3.5-turbo',
                'gpt-4' => 'gpt-4'
                ];
    $settings->add( new admin_setting_configselect(
        'local_aiquestions/model',
        get_string('model', 'local_aiquestions'),
        get_string('openaikeydesc', 'local_aiquestions'),
        'gpt-3.5-turbo',
        $options,
    ));

    // Number of tries.
    $settings->add( new admin_setting_configtext(
        'local_aiquestions/numoftries',
        get_string('numoftriesset', 'local_aiquestions'),
        get_string('numoftriesdesc', 'local_aiquestions'),
        10, PARAM_INT, 10
    ));

    // Presets
    $settings->add( new admin_setting_heading(
        'local_aiquestions/presets',
        get_string('presets', 'local_aiquestions'),
        get_string('presetsdesc', 'local_aiquestions') .
        get_string('shareyourprompts', 'local_aiquestions'),
    ));

    for ($i = 1; $i <= 10; $i++) {

        // Preset header.
        $settings->add( new admin_setting_heading(
            'local_aiquestions/preset' . $i,
            get_string('preset', 'local_aiquestions') . " $i",
            null
        ));

        // Preset name.
        $settings->add( new admin_setting_configtext(
            'local_aiquestions/presetname' . $i,
            get_string('presetname', 'local_aiquestions'),
            get_string('presetnamedesc', 'local_aiquestions'),
            get_string('presetnamedefault' . $i, 'local_aiquestions'),
        ));

        // Preset primer.
        $settings->add( new admin_setting_configtextarea(
            'local_aiquestions/presettprimer' . $i,
            get_string('presetprimer', 'local_aiquestions'),
            get_string('primer_help', 'local_aiquestions'),
            get_string('presetprimerdefault' . $i, 'local_aiquestions'),
            PARAM_TEXT, 4000
        ));

        // Preset instructions.
        $settings->add( new admin_setting_configtextarea(
            'local_aiquestions/presetinstructions' . $i,
            get_string('presetinstructions', 'local_aiquestions'),
            get_string('instructions_help', 'local_aiquestions'),
            get_string('presetinstructionsdefault' . $i, 'local_aiquestions'),
            PARAM_TEXT, 4000
        ));

        // Preset example.
        $settings->add( new admin_setting_configtextarea(
            'local_aiquestions/presetexample' . $i,
            get_string('presetexample', 'local_aiquestions'),
            get_string('example_help', 'local_aiquestions'),
            get_string('presetexampledefault' . $i, 'local_aiquestions'),
            PARAM_TEXT, 4000
        ));

    }

    $ADMIN->add('localplugins', $settings);

    // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
    if ($ADMIN->fulltree) {
        // TODO: Define actual plugin settings page and add it to the tree - {@link https://docs.moodle.org/dev/Admin_settings}.
    }
}

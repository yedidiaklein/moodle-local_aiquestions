<?php
defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aiquiz', get_string('pluginname', 'local_aiquiz'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_aiquiz/apikey',
        get_string('apikey', 'local_aiquiz'),
        get_string('apikeydesc', 'local_aiquiz'),
        '',
        PARAM_TEXT
    ));

    /*$settings->add(new admin_setting_configtext(
        'local_aiquiz/email',
        get_string('email', 'local_aiquiz'),
        get_string('emaildesc', 'local_aiquiz'),
        '',
        PARAM_EMAIL
    ));*/
}
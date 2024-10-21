<?php
defined('MOODLE_INTERNAL') || die();

$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->csspostprocess = 'local_aiquiz_process_css';

function local_aiquiz_before_standard_html_head() {
    return \local_aiquiz\output\modifier::before_standard_html_head();
}
<?php
defined('MOODLE_INTERNAL') || die();

class local_aiquiz_renderer extends plugin_renderer_base {
    public function render_evaluation_result($attemptid, $slot) {
        global $DB;

        //debugging('AIQuiz: render_evaluation_result method called for attempt ' . $attemptid . ', slot ' . $slot, DEBUG_DEVELOPER);

        $output = '';

        // Fetch AI evaluation data
        /*$evaluation = $DB->get_record('local_aiquiz_evaluations', ['attemptid' => $attemptid, 'slot' => $slot]);

        if ($evaluation) {
            debugging('AIQuiz: Evaluation found for attempt ' . $attemptid . ', slot ' . $slot, DEBUG_DEVELOPER);
            $output .= html_writer::start_div('aiquiz-evaluation');
            $output .= html_writer::tag('h4', get_string('ai_evaluation', 'local_aiquiz'));
            $output .= html_writer::tag('p', get_string('ai_grade', 'local_aiquiz') . ': ' . $evaluation->grade);
            $output .= html_writer::tag('p', get_string('ai_feedback', 'local_aiquiz') . ': ' . $evaluation->feedback);
            $output .= html_writer::end_div();
        } else {*/
            //debugging('AIQuiz: No evaluation found for attempt ' . $attemptid . ', slot ' . $slot, DEBUG_DEVELOPER);
            $output .= html_writer::tag('p', get_string('no_ai_evaluation', 'local_aiquiz'));
        //}

        //debugging('AIQuiz: Generated output: ' . substr($output, 0, 100) . '...', DEBUG_DEVELOPER);
        return $output;
    }
}
<?php
namespace local_aiquiz\observers;

defined('MOODLE_INTERNAL') || die();

class quiz_comment_observer {
    public static function inject_content($event) {
        global $PAGE, $OUTPUT;

        //debugging('AIQuiz: inject_content method called', DEBUG_DEVELOPER);

        $attemptid = $event->other['attemptid'];
        $slot = $event->other['slot'];
        //debugging('AIQuiz: Attempt ID: ' . $attemptid . ', Slot: ' . $slot, DEBUG_DEVELOPER);

        // Get our custom renderer
        $renderer = $PAGE->get_renderer('local_aiquiz');
        //debugging('AIQuiz: Renderer obtained', DEBUG_DEVELOPER);

        $additional_content = $renderer->render_evaluation_result($attemptid, $slot);
        //debugging('AIQuiz: Additional content generated: ' . substr($additional_content, 0, 100) . '...', DEBUG_DEVELOPER);

        // Inject our content using JavaScript
        $PAGE->requires->js_init_code("
            Y.on('domready', function() {
                var commentForm = Y.one('#manualgradingform');
                if (commentForm) {
                    commentForm.insert('" . json_encode($additional_content) . "', 'before');
                    console.log('AIQuiz: Content injected');
                } else {
                    console.log('AIQuiz: #manualgradingform element not found');
                }
            });
        ");
        //debugging('AIQuiz: JavaScript injection code added', DEBUG_DEVELOPER);
    }
}
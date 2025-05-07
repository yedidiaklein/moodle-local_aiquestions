<?php
namespace local_aiquiz\output;

defined('MOODLE_INTERNAL') || die();

class modifier {
    public static function before_standard_html_head() {
        global $PAGE, $CFG;

        if ($PAGE->pagetype === 'mod-quiz-comment') {
            $attemptid = optional_param('attempt', 0, PARAM_INT);
            $slot = optional_param('slot', 0, PARAM_INT);

            if ($attemptid && $slot) {
                $js = "
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        console.log('AIQuiz: DOM loaded');
                        var xhr = new XMLHttpRequest();
                        xhr.open('GET', '{$CFG->wwwroot}/local/aiquiz/ajax.php?attemptid={$attemptid}&slot={$slot}', true);
                        xhr.onload = function() {
                            if (xhr.status === 200) {
                                console.log('AIQuiz: AJAX response received');
                                var form = document.getElementById('manualgradingform');
                                if (form) {
                                    var div = document.createElement('div');
                                    div.innerHTML = xhr.responseText;
                                    form.parentNode.insertBefore(div, form);
                                    console.log('AIQuiz: Content injected');
                                } else {
                                    console.log('AIQuiz: #manualgradingform not found');
                                }
                            } else {
                                console.error('AIQuiz: AJAX request failed');
                            }
                        };
                        xhr.onerror = function() {
                            console.error('AIQuiz: AJAX request failed');
                        };
                        xhr.send();
                    });
                </script>
                ";
                return $js;
            }
        }
        return '';
    }
}
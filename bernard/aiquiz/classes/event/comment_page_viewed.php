<?php
namespace local_aiquiz\event;

defined('MOODLE_INTERNAL') || die();

class comment_page_viewed extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    public static function create_from_attempt($attemptobj, $slot) {
        $event = self::create([
            'context' => $attemptobj->get_quizobj()->get_context(),
            'other' => [
                'attemptid' => $attemptobj->get_attemptid(),
                'slot' => $slot
            ]
        ]);
        return $event;
    }

    public function get_description() {
        return "The user with id '{$this->userid}' viewed the comment page for attempt '{$this->other['attemptid']}' and slot '{$this->other['slot']}'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/quiz/comment.php', [
            'attempt' => $this->other['attemptid'],
            'slot' => $this->other['slot']
        ]);
    }
}
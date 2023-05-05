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
 * Adhoc task for questions generation.
 *
 * @package     local_aiquestions
 * @category    admin
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiquestions\task;

defined('MOODLE_INTERNAL') || die();

class questions extends \core\task\adhoc_task
{
    public function execute() {
        global $DB;
        require_once(__DIR__ . '/../../locallib.php');
        $data = $this->get_custom_data();
        $courseid = $data->courseid;
        $story = $data->story;
        $numofquestions = $data->numofquestions;
        $userid = $data->userid;
        $uniqid = $data->uniqid;

        // Create the DB entry.
        $dbrecord = new \stdClass();
        $dbrecord->course = $courseid;
        $dbrecord->numofquestions = $numofquestions;
        $dbrecord->user = $userid;
        $dbrecord->datecreated = time();
        $dbrecord->datemodified = time();
        $dbrecord->tries = 0;
        $dbrecord->uniqid = $uniqid;
        $inserted = $DB->insert_record('local_aiquestions', $dbrecord);
        // Try 10 times to create the questions.
        $created = false;
        $i = 0;
        $success = ''; // Success message.
        $error = ''; // Error message.
        // Future : move i to module settings - max number of attempts.
        $update = new \stdClass();
        while (!$created && $i < 10) {
            // First update DB on tries.
            $update->id = $inserted;
            $update->tries = $i;
            $update->datemodified = time();
            $DB->update_record('local_aiquestions', $update);
            // Get questions from ChatGPT API.
            $questions = \local_aiquestions_get_questions($courseid, $story, $numofquestions);
            // Print error message of ChatGPT API (if there are).
            if (isset($questions->error->message)) {
                $error .= $questions->error->message;
            }
            // Check gift format.
            if (\local_aiquestions_check_gift($questions->text)) {
                // Create the questions, return an array of objetcs of the created questions.
                $created = \local_aiquestions_create_questions($courseid, $questions->text, $numofquestions, $userid);
                foreach ($created as $question) {
                    $success .= get_string('createdquestionwithid', 'local_aiquestions') . " : " . $question->id . "<br>";
                    $success .= $question->name . "<br>";
                }
                // Insert success creation info to DB.
                $update->id = $inserted;
                $update->gift = $questions->text;
                $update->tries = $i;
                $update->success = $success;
                $update->datemodified = time();
                $DB->update_record('local_aiquestions', $update);
            }
            $i++;
        }
        // If questions were not created.
        if (!$created) {
            // Insert error info to DB.
            $update = new \stdClass();
            $update->id = $inserted;
            $update->tries = $i;
            $update->timemodified = time();
            $update->success = get_string("generationfailed", "local_aiquestions", $i);
            $DB->update_record('local_aiquestions', $update);
        }
    }
}

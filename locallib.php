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


/**
 * Get questions from the API using Moodle's AI subsystem.
 *
 * @param object data data to create questions from
 * @return object questions of generated questions
 */
function local_aiquestions_get_questions($data) {

    global $CFG, $USER;

    // Build primer.
    $primer = $data->primer;
    $primer .= "Write $data->numofquestions questions.";

    // Remove new lines and carriage returns.
    $story = str_replace("\n", " ", $data->story);
    $story = str_replace("\r", " ", $story);
    $instructions = str_replace("\n", " ", $data->instructions);
    $instructions = str_replace("\r", " ", $instructions);
    $example = str_replace("\n", " ", $data->example);
    $example = str_replace("\r", " ", $example);

    // Build the complete prompt for Moodle's AI subsystem.
    $prompttext = $primer . "\n\n";
    $prompttext .= "Instructions: " . $instructions . "\n\n";
    $prompttext .= "Example: " . $example . "\n\n";
    $prompttext .= "Now, create " . $data->numofquestions . " questions for me based on this topic: " . $story;

    // Get the context - use system context if no specific context is available.
    $contextid = context_system::instance()->id;

    // Use Moodle's AI subsystem to generate questions.
    $aianswer = new \local_aiquestions\sendtoai();
    $result = $aianswer->execute($contextid, $prompttext);

    $questions = new stdClass(); // The questions object.
    if ($result['success']) {
        $questions->text = $result['generatedcontent'];
        $questions->prompt = $story;
    } else {
        // Handle error case.
        $questions = new stdClass();
        $questions->error = $result['error'] ?? 'Unknown error occurred';
        $questions->errorcode = $result['errorcode'] ?? 0;
        $questions->prompt = $story;
    }
    return $questions;
}
/**
 * Create questions from data got from ChatGPT output.
 *
 * @param int $courseid course id
 * @param int $category course category
 * @param string $gift questions in GIFT format
 * @param int $numofquestions number of questions to generate
 * @param int $userid user id
 * @param bool $addidentifier add an GPT prefix to question names.
 * @return array of objects of created questions
 */
function local_aiquestions_create_questions($courseid, $category, $gift, $numofquestions, $userid, $addidentifier) {
    global $CFG, $USER, $DB;

    require_once($CFG->libdir . '/questionlib.php');
    require_once($CFG->dirroot . '/question/format.php');
    require_once($CFG->dirroot . '/question/format/gift/format.php');

    $qformat = new \qformat_gift();

    $coursecontext = \context_course::instance($courseid);

    // Get question category TODO: MDL-12345 There is probably a better way to do this.
    if ($category) {
        $categoryids = explode(',', $category);
        $categoryid = $categoryids[0];
        $categorycontextid = $categoryids[1];
        $category = $DB->get_record('question_categories', ['id' => $categoryid, 'contextid' => $categorycontextid]);
    }

    // Use existing questions category for quiz or create the defaults.
    if (!$category) {
        $contexts = new core_question\local\bank\question_edit_contexts($coursecontext);
        if (!$category = $DB->get_record('question_categories', ['contextid' => $coursecontext->id, 'sortorder' => 999])) {
            $category = question_make_default_categories($contexts->all());
        }
    }

    // Split questions based on blank lines.
    // Then loop through each question and create it.
    $questions = explode("\n\n", $gift);

    if (count($questions) != $numofquestions) {
        return false;
    }
    $createdquestions = []; // Array of objects of created questions.
    foreach ($questions as $question) {
        $singlequestion = explode("\n", $question);

        // Manipulating question text manually for question text field.
        $questiontext = explode('{', $singlequestion[0]);
        $questiontext = trim(preg_replace('/^.*::/', '', $questiontext[0]));
        $qtype = 'multichoice';
        $q = $qformat->readquestion($singlequestion);

        // Check if question is valid.
        if (!$q) {
            return false;
        }
        $q->category = $category->id;
        $q->createdby = $userid;
        $q->modifiedby = $userid;
        $q->timecreated = time();
        $q->timemodified = time();
        $q->questiontext = ['text' => "<p>" . $questiontext . "</p>"];
        $q->questiontextformat = 1;
        if ($addidentifier == 1) {
            $q->name = "GPT-created: " . $q->name; // Adds a "watermark" to the question.
        }
        $created = question_bank::get_qtype($qtype)->save_question($q, $q);
        $createdquestions[] = $created;
    }
    if ($created) {
        return $createdquestions;
    } else {
        return false;
    }
}
/**
 * Escape json.
 *
 * @param string $value json to escape
 * @return string result escaped json
 */
function local_aiquestions_escape_json($value) {
    $escapers = ["\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c"];
    $replacements = ["\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b"];
    $result = str_replace($escapers, $replacements, $value);
    return $result;
}

/**
 * Check if the gift format is valid.
 *
 * @param string $gift questions in GIFT format
 * @return bool true if valid, false if not
 */
function local_aiquestions_check_gift($gift) {
    $questions = explode("\n\n", $gift);

    foreach ($questions as $question) {
        $qa = str_replace("\n", "", $question);
        preg_match('/::(.*)\{/', $qa, $matches);
        if (isset($matches[1])) {
            $qlength = strlen($matches[1]);
        } else {
            return false;
            // Error : Question title not found.
        }
        if ($qlength < 10) {
            return false;
            // Error : Question length too short.
        }
        preg_match('/\{(.*)\}/', $qa, $matches);
        if (isset($matches[1])) {
            $wrongs = substr_count($matches[1], "~");
            $right = substr_count($matches[1], "=");
        } else {
            return false;
            // Error : Answers not found.
        }
        if ($wrongs != 3 || $right != 1) {
            return false;
            // Error : There is no single right answers or no 3 wrong answers.
        }
    }
    return true;
}


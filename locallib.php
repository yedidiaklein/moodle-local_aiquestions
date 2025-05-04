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
 * Get questions from the API.
 *
 * @param object data data to create questions from
 * @return object questions of generated questions
 */
function local_aiquestions_get_questions($data) {

    global $CFG;

    // Build primer.
    $primer = $data->primer;
    $primer .= "Write $data->numofquestions questions.";

    $key = get_config('local_aiquestions', 'key');
    $model = get_config('local_aiquestions', 'model');
    $provider = get_config('local_aiquestions', 'provider'); // OpenAI (default) or Azure
    
    if ($provider === 'Azure') {
    // If the provider is Azure, use the Azure API endpoint and Azure-specific HTTP header
    $url = get_config('local_aiquestions', 'azure_api_endpoint'); // Use the Azure API endpoint from settings
    $authorization = "api-key: " . $key;
} else {
    // If the provider is not Azure, use the OpenAI API URL and OpenAI style HTTP header
    $url = 'https://api.openai.com/v1/chat/completions';
    $authorization = "Authorization: Bearer " . $key;
}
    // Remove new lines and carriage returns.
    $story = str_replace("\n", " ", $data->story);
    $story = str_replace("\r", " ", $story);
    $instructions = str_replace("\n", " ", $data->instructions);
    $instructions = str_replace("\r", " ", $instructions);
    $example = str_replace("\n", " ", $data->example);
    $example = str_replace("\r", " ", $example);

    $data = '{
        "model": "' . $model . '",
        "messages": [
            {"role": "system", "content": "' . $primer . '"},
            {"role": "system", "name":"example_user", "content": "' . $instructions . '"},
            {"role": "system", "name": "example_assistant", "content": "' . $example . '"},
            {"role": "user", "content": "Now, create ' . $data->numofquestions . ' questions for me based on this topic: ' . local_aiquestions_escape_json($story) . '"}
	    ]}';

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2000);
    $result = json_decode(curl_exec($ch));
    curl_close($ch);

    $questions = new stdClass(); // The questions object.
    if (isset($result->choices[0]->message->content)) {
        $questions->text = $result->choices[0]->message->content;
        $questions->prompt = $story;
    } else {
        $questions = $result;
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
 * @param bool $addidentifier add an GPT prefix to question names 
 * @return array of objects of created questions
 */
function local_aiquestions_create_questions($courseid, $category, $gift, $numofquestions, $userid, $addidentifier) {
    global $CFG, $USER, $DB;

    require_once($CFG->libdir . '/questionlib.php');
    require_once($CFG->dirroot . '/question/format.php');
    require_once($CFG->dirroot . '/question/format/gift/format.php');

    $qformat = new \qformat_gift();

    $coursecontext = \context_course::instance($courseid);

    // Get question category TODO: there is probably a better way to do this.
    if ($category) {
        $categoryids = explode(',',$category);
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
            $q->name = "GPT-created: " . $q->name; // Adds a "watermark" to the question
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
    $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
    $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
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


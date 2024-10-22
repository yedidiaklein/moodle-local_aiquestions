<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Manual graded step for question attempts.
 *
 * @package    local_aiquiz
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/engine/lib.php');

/**
 * Class for manual graded question attempt steps.
 */
class question_attempt_step_manual_graded extends question_attempt_step {
    /**
     * Constructor.
     *
     * @param object $data The step data
     * @param int $timestamp The time this step was created
     * @param int $userid The ID of the user who created this step
     */
    public function __construct($data, $timestamp, $userid) {
        parent::__construct($data, $timestamp, $userid);
        $this->set_state(question_state::$mangrright);
    }
}
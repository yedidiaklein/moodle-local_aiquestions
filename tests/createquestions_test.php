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

namespace local_aiquestions;

/**
 * The createquestions test class.
 *
 * @package     local_aiquestions
 * @category    test
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class createquestions_test extends \advanced_testcase {

    // Write the tests here as public funcions.
    // Please refer to {@link https://docs.moodle.org/dev/PHPUnit} for more details on PHPUnit tests in Moodle.

    /**
     * Dummy test.
     *
     * This is to be replaced by some actually usefule test.
     *
     * @coversNothing
     */
    public function test_dummy() {
        $this->assertTrue(false);
    }

    /**
     * Test local_aiquestions_create_questions.
     * @covers local_aiquestions_create_questions
     */
    public function test_create() {
        require_once(__DIR__ . '/../locallib.php');
        $gift = "
            ::Question1:: My interesting questionText
            {
                = right answer
                ~ wrong1
                ~ wrong2
                ~ wrong3
                ~ wrong4
            }";
        $question = \local_aiquestions_create_questions(2, $gift, 1, 3);
        $this->assertEquals($question->name, 'Question1');
        $this->assertEquals($question->questiontext, 'My interesting questionText');
        $this->assertEquals($question->qtype, 'multichoice');
        $this->assertEquals($question->fraction, 1);
        $this->assertEquals($question->single, 1);
        $this->assertEquals($question->answernumbering, 'abc');
        $this->assertEquals($question->shuffleanswers, 1);
    }
}

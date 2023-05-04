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
 * Service for checking state of question generation.
 *
 * @package     local_aiquestions
 * @category    admin
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiquestions\external;

use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;

class check_state extends \external_api
{
    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'User id'),
            'uniqid' => new external_value(PARAM_TEXT, 'Unique id'),
        ]);
    }
    /**
     * Returns description of method result value
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'state' => new external_value(PARAM_INT, 'State of question generation, 0 in work, 1 done'),
                'success' => new external_value(PARAM_RAW, 'Success generation message'),
                'tries' => new external_value(PARAM_INT, 'Number of tries'),
            ])
        );
    }
    /**
     * Check state
     * @param int $userid string $uniqid
     * @return array of state and success message
     */
    public static function execute($userid, $uniqid) {
        global $CFG, $DB, $USER;

        // Validate Params.
        $params = self::validate_parameters(self::execute_parameters(), [
            'userid' => $userid,
            'uniqid' => $uniqid,
        ]);

        $userid = $params['userid'];
        $uniqid = $params['uniqid'];

        // Perform security checks.
        // Allow only the user to check his own state.
        if ($USER->id != $userid) {
            throw new \moodle_exception('invaliduserid');
        }

        $state = $DB->get_record('local_aiquestions', ['user' => $userid, 'uniqid' => $uniqid]);
        $info = [];
        $info['tries'] = $state->tries;
        if ($state->success != '') {
            $info['state'] = 1;
            $info['success'] = $state->success;
        } else {
            $info['state'] = 0;
            $info['success'] = '';
        }
        $data[] = $info;
        return $data;
    }
}


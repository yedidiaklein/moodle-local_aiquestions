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
 * CLI utility to check cron state.
 *
 * @package     local_aiquestions
 * @category    admin
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/../locallib.php');

// Check if the cron is overdue.
$lastcron = get_config('tool_task', 'lastcronstart');
echo "LastCron: $lastcron \n";

$cronoverdue = ($lastcron < time() - 3600 * 24);
echo "CronOverdue: $cronoverdue \n";

$lastcroninterval = get_config('tool_task', 'lastcroninterval');
echo "LastCronInterval: $lastcroninterval \n";

$expectedfrequency = $CFG->expectedcronfrequency ?? MINSECS;
echo "ExpectedFrequency: $expectedfrequency \n";

$croninfrequent = !$cronoverdue && ($lastcroninterval > ($expectedfrequency + MINSECS) || $lastcron < time() - $expectedfrequency);
echo "CronInfrequent: $croninfrequent \n";


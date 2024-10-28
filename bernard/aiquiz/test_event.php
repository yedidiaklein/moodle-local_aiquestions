<?php
// Save this as local/aiquiz/test_event.php

require_once(__DIR__ . '/../../config.php');
require_login();

$questionid = optional_param('questionid', 0, PARAM_INT);

if (!$questionid) {
    echo "Please provide a question ID in the URL (e.g., ?questionid=123)";
    die;
}

// Get the question category ID through the question bank entry
global $DB;

// Get the category ID from the question bank entries and versions tables
$sql = "SELECT qc.id as categoryid
        FROM {question} q
        JOIN {question_versions} qv ON qv.questionid = q.id
        JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
        JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
        WHERE q.id = :questionid";

$params = array('questionid' => $questionid);
$category = $DB->get_record_sql($sql, $params);

if (!$category) {
    echo "Could not find category for question ID: $questionid";
    die;
}

// Create and trigger a test event
$context = context_system::instance();
$event = \core\event\question_updated::create(array(
    'objectid' => $questionid,
    'context' => $context,
    'other' => array(
        'questionid' => $questionid,
        'version' => 1,
        'categoryid' => $category->categoryid  // Use the correct category ID
    )
));

// Trigger the event
$event->trigger();

echo "<pre>";
echo "Test event triggered for question ID: $questionid\n";
echo "Question category ID: " . $category->categoryid . "\n";
echo "Check these locations for logs:\n";
echo "1. " . $CFG->dataroot . "/aiquiz_debug.log\n";
echo "2. C:/wamp64/logs/php_error.log\n";
echo "3. Moodle debug output on this page\n";

// Debug registered events
$events = $DB->get_records('events_handlers', array('component' => 'local_aiquiz'));
echo "\nRegistered events:\n";
print_r($events);
echo "</pre>";
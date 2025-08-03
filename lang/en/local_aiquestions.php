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
 * Plugin strings are defined here.
 *
 * @package     local_aiquestions
 * @category    string
 * @copyright   2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addidentifier'] = 'Add a "AI-created: " prefix to the question name';
$string['aiquestions'] = 'AI Questions';
$string['aisettingsdesc'] = 'This plugin now uses Moodle\'s built-in AI subsystem. To configure AI providers (OpenAI, Azure, etc.), please go to Site administration > Plugins > AI > AI subsystem management. The AI provider settings have been moved there for centralized management across all AI-enabled plugins.';
$string['aisettingsheader'] = 'AI Configuration';
$string['azureapiendpoint'] = 'Azure API Endpoint';
$string['azureapiendpointdesc'] = 'Enter the Azure API endpoint URL here';
$string['backtocourse'] = 'Back to course';
$string['category'] = 'Question category';
$string['category_help'] = 'If the category selection is empty, open the question bank for this course once.';
$string['createdquestionssuccess'] = 'Created questions successfully';
$string['createdquestionsuccess'] = 'Created question successfully';
$string['createdquestionwithid'] = 'Created question with id ';
$string['cronoverdue'] = 'The cron task seems not to run,
questions generation rely on AdHoc Tasks that are created by the cron task, please check your cron settings.
See <a href="https://docs.moodle.org/en/Cron#Setting_up_cron_on_your_system">
https://docs.moodle.org/en/Cron#Setting_up_cron_on_your_system
</a> for more information.';
$string['editpreset'] = 'Edit the preset before sending it to the AI';
$string['errornotcreated'] = 'Error: questions were not created';
$string['example'] = 'Example';
$string['example_help'] = 'The example shows the AI an example output, to clarify the formatting.';
$string['generate'] = 'Generate questions';
$string['generatemore'] = 'Generate more questions';
$string['generating'] = 'Generating your questions... (You can safely leave this page, and check later on the question bank)';
$string['generationfailed'] = 'The question generation failed after {$a} tries';
$string['generationtries'] = 'Number of tries sent to OpenAI: <b>{$a}</b>';
$string['gotoquestionbank'] = 'Go to question bank';
$string['instructions'] = 'Instructions';
$string['instructions_help'] = 'The instructions tell the AI what to do.';
$string['model'] = 'Model';
$string['model_desc'] = 'Language model to use. <a href="https://platform.openai.com/docs/models/">More info</a>.';
$string['nopdfselected'] = 'No file selected';
$string['numofquestions'] = 'Number of questions to generate';
$string['numoftries'] = '<b>{$a}</b> tries';
$string['numoftriesdesc'] = 'Number of tries to send to OpenAI';
$string['numoftriesset'] = 'Number of Tries';
$string['openaikey'] = 'OpenAI or Azure API key';
$string['openaikeydesc'] = 'You can get an OpenAI API key from <a href="https://platform.openai.com/account/api-keys">https://platform.openai.com/account/api-keys</a><br>
Select the "+ Create New Secret Key" button and copy the key to this field.<br>
Note that you need to have an OpenAI account that includes billing settings to get an API key.';
$string['outof'] = 'out of';
$string['pdf'] = 'File to use';
$string['pdf_help'] = 'Select a file from the course to extract the text from. If you select a file, it will override the text above.';
$string['pluginname'] = 'AI text to questions generator';
$string['pluginname_desc'] = 'This plugin allows you to automatically generate questions from a text using a language AI (eg chatGPT).';
$string['pluginname_help'] = 'Use this plugin from the course administration menu or the question bank.';
$string['preset'] = 'Preset';
$string['presetexample'] = 'Preset example';
$string['presetexampledefault1'] = "::Indexicality and iconicity 1:: Imagine that you are standing on a lake shore. A wind rises, creating waves on the lake surface. According to C.S. Peirce, in what way the waves signify the wind? { =The relationship is both indexical and iconical.#Correct. There is a connection of spatio-temporal contiguity between the wind and the waves, which is a determining feature of indexicality. There is also a formal resemblance between wind direction and the direction of the waves, which is a determining feature of iconicity.  ~The relationship is indexical.#Almost correct. There is a connection of spatio-temporal contiguity between the wind and the waves, which, according to Peirce, is a determining feature of indexicality. However, there is additional signification taking place as well. ~There is no sign phenomena betweem the wind and the waves, they are two separate signs.#Incorrect. The movement of the waves is determined by the wind. ~The relationship between the wind and the waves is symbolic.#Incorrect. The movement of the waves is not arbitrary, which would be the case if the relationship was symbolic. }";
$string['presetexampledefault10'] = '';
$string['presetexampledefault2'] = '';
$string['presetexampledefault3'] = '';
$string['presetexampledefault4'] = '';
$string['presetexampledefault5'] = '';
$string['presetexampledefault6'] = '';
$string['presetexampledefault7'] = '';
$string['presetexampledefault8'] = '';
$string['presetexampledefault9'] = '';
$string['presetinstructions'] = 'Preset instructions';
$string['presetinstructionsdefault1'] = "Please write a multiple choice question in English language in GIFT format on a topic I will specify to you separately GIFT format use equal sign for right answer and tilde sign for wrong answer at the beginning of answers. For example: '::Question title:: Question text { =right answer#feedback ~wrong answer#feedback ~wrong answer#feedback ~wrong answer#feedback }' Please have a blank line between questions. Do not include the question title in the beginning of the question text.";
$string['presetinstructionsdefault10'] = '';
$string['presetinstructionsdefault2'] = '';
$string['presetinstructionsdefault3'] = '';
$string['presetinstructionsdefault4'] = '';
$string['presetinstructionsdefault5'] = '';
$string['presetinstructionsdefault6'] = '';
$string['presetinstructionsdefault7'] = '';
$string['presetinstructionsdefault8'] = '';
$string['presetinstructionsdefault9'] = '';
$string['presetname'] = 'Preset name';
$string['presetnamedefault1'] = "Multiple choice question (english)";
$string['presetnamedefault10'] = '';
$string['presetnamedefault2'] = '';
$string['presetnamedefault3'] = '';
$string['presetnamedefault4'] = '';
$string['presetnamedefault5'] = '';
$string['presetnamedefault6'] = '';
$string['presetnamedefault7'] = '';
$string['presetnamedefault8'] = '';
$string['presetnamedefault9'] = '';
$string['presetnamedesc'] = 'Name that will be shown to the user';
$string['presetprimer'] = 'Preset primer';
$string['presetprimerdefault1'] = "You are a helpful teacher's assistant that creates multiple choice questions based on the topics given by the user.";
$string['presetprimerdefault10'] = '';
$string['presetprimerdefault2'] = '';
$string['presetprimerdefault3'] = '';
$string['presetprimerdefault4'] = '';
$string['presetprimerdefault5'] = '';
$string['presetprimerdefault6'] = '';
$string['presetprimerdefault7'] = '';
$string['presetprimerdefault8'] = '';
$string['presetprimerdefault9'] = '';
$string['presets'] = 'Presets';
$string['presetsdesc'] = 'You can specify up to 10 presets, which users will be able to select in their courses. Users will still be able to edit the presets before sending them to OpenAI.';
$string['preview'] = 'Preview question in new tab';
$string['primer'] = 'Primer';
$string['primer_help'] = 'The primer is the first information to be sent to the AI, priming it for its task.';
$string['privacy:metadata'] = 'AI text to questions generator does not store any personal data.';
$string['provider'] = 'GPT provider';
$string['providerdesc'] = 'Select if you are using Azure of OpenAI';
$string['shareyourprompts'] = 'You can find more prompt ideas or share yours at <a target="_blank" href="https://docs.moodle.org/402/en/AI_Text_to_questions_generator">the Moodle Docs page for this plugin</a>.';
$string['story'] = 'Topic';
$string['story_help'] = 'The topic of your questions. You can also copy/paste whole articles, eg from wikipedia.';
$string['tasksuccess'] = 'The question generation task was successfully created';

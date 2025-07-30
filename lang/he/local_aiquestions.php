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

$string['addidentifier'] = 'הוסף קידומת "GPT-created: " לשם השאלה';
$string['aiquestions'] = 'שאלות AI';
$string['aisettingsdesc'] = 'התוסף הזה משתמש כעת בתת-מערכת ה-AI המובנית של Moodle. להגדרת ספקי AI (OpenAI, Azure וכו\'), עבור אל ניהול אתר > תוספים > AI > ניהול תת-מערכת AI. הגדרות ספק ה-AI הועברו לשם לניהול מרכזי של כל התוספים התומכים ב-AI.';
$string['aisettingsheader'] = 'תצורת AI';
$string['azureapiendpoint'] = 'נקודת קצה של Azure API';
$string['azureapiendpointdesc'] = 'הזן כאן את כתובת ה-URL של נקודת הקצה של Azure API';
$string['backtocourse'] = 'חזרה לקורס';
$string['category'] = 'קטגוריית שאלה';
$string['category_help'] = 'אם הבחירה בקטגוריה ריקה, פתח פעם אחת את בנק השאלות עבור הקורס הזה.';
$string['createdquestionssuccess'] = 'השאלות נוצרו בהצלחה';
$string['createdquestionsuccess'] = 'השאלה נוצרה בהצלחה';
$string['createdquestionwithid'] = 'נוצרה שאלה עם מזהה ';
$string['cronoverdue'] = 'נראה שהמשימה cron אינה פועלת,
יצירת השאלות תלויה במשימות AdHoc שנוצרות על ידי המשימה cron, נא לבדוק את הגדרות ה-cron שלך.
ראה <a href="https://docs.moodle.org/en/Cron#Setting_up_cron_on_your_system">
https://docs.moodle.org/en/Cron#Setting_up_cron_on_your_system
</a> לקבלת מידע נוסף.';
$string['editpreset'] = 'ערוך את הפרה-הרשאה לפני השליחה ל-AI';
$string['errornotcreated'] = 'שגיאה : השאלות לא נוצרו';
$string['example'] = 'דוגמה';
$string['example_help'] = 'הדוגמה מציגה ל-AI פלט לדוגמה כדי להבהיר את הפורמט.';
$string['generate'] = 'יצירת שאלות';
$string['generatemore'] = 'יצירת שאלות נוספות';
$string['generating'] = 'יוצר את השאלות שלך... (ניתן לעזוב דף זה, ולבדוק מאוחר יותר בבנק השאלות)';
$string['generationfailed'] = 'נכשל ביצירת השאלות לאחר {$a} ניסיונות';
$string['generationtries'] = 'מספר הניסיונות שנשלחו ל- OpenAI: <b>{$a}</b>';
$string['gotoquestionbank'] = 'עברו לבנק השאלות';
$string['instructions'] = 'הוראות';
$string['instructions_help'] = 'ההוראות אומרות ל-AI מה לעשות.';
$string['language'] = 'שפה';
$string['languagedesc'] = 'נא לבחור כאן את השפה שבה ברצונך להשתמש ביצירת השאלות.<br>
יש לשים לב שישנן שפות שנתמכות פחות מאחרות על ידי ChatGPT.';
$string['model'] = 'דגם';
$string['model_desc'] = 'דגם שפה לשימוש. <a href="https://platform.openai.com/docs/models/">מידע נוסף</a>.';
$string['numofquestions'] = 'מספר השאלות';
$string['numofquestionsdesc'] = 'נא לבחור כאן את מספר השאלות שברצונך ליצור.';
$string['numoftries'] = '<b>{$a}</b> ניסיונות';
$string['numoftriesdesc'] = 'נא לכתוב כאן את מספר הניסיונות שברצונך לשלוח ל- OpenAI';
$string['numoftriesset'] = 'מספר הניסיונות';
$string['openaikey'] = 'מפתח API של OpenAI';
$string['openaikeydesc'] = 'נא להקליד כאן את מפתח ה- API של OpenAI שלך.<br>
ניתן לקבל את מפתח ה- API שלך מ- <a href="https://platform.openai.com/account/api-keys">https://platform.openai.com/account/api-keys</a><br>
יש לבחור בכפתור "+ Create New Secret Key" ולהעתיק את המפתח לשדה זה.<br>
יש לציין שנדרש חשבון של OpenAI שכולל הגדרות חיוב כדי לקבל מפתח API.';
$string['outof'] = 'מתוך';
$string['personalprompt'] = 'הנחיה אישית';
$string['personalpromptdesc'] = 'נא להקליד כאן את ההנחיה האישית שלך.<br>
ההנחיה היא ההסבר ל-ChatGPT כיצד ליצור את השאלות.<br>
יש לכלול בה שני משתני מקום: {{numofquestions}} ו-{{language}}.';
$string['pluginname'] = 'מחולל שאלות טקסט לשאלות באמצעות AI';
$string['pluginname_desc'] = 'תוסף זה מאפשר לך ליצור שאלות מתוך טקסט.';
$string['pluginname_help'] = 'השתמש בתוסף זה מתפריט הניהול של הקורס.';
$string['preset'] = 'פרה-הרשאה';
$string['presetexample'] = 'דוגמה לפרה-הרשאה';
$string['presetexampledefault1'] = '';
$string['presetexampledefault10'] = '';
$string['presetinstructions'] = 'הוראות פרה-הרשאה';
$string['presetinstructionsdefault1'] = '';
$string['presetinstructionsdefault10'] = '';
$string['presetname'] = 'שם הפרה-הרשאה';
$string['presetnamedefault1'] = '';
$string['presetnamedefault10'] = '';
$string['presetnamedesc'] = 'השם שיוצג למשתמש';
$string['presetprimer'] = 'פרימר לפרה-הרשאה';
$string['presetprimerdefault1'] = '';
$string['presetprimerdefault10'] = '';
$string['presets'] = 'פרה-הרשאות';
$string['presetsdesc'] = 'ניתן לציין עד 10 פרה-הרשאות, שמשתמשים יוכלו לבחור בקורס שלהם. הם עדיין יוכלו לערוך את הפרה-הרשאות לפני השליחה.';
$string['privacy:metadata'] = 'מחולל שאלות טקסט לשאלות אינו מאחסן נתונים אישיים.';
$string['provider'] = 'ספק GPT';
$string['providerdesc'] = 'בחר אם אתה משתמש ב-Azure או ב-OpenAI';
$string['shareyourprompts'] = 'ניתן למצוא עוד רעיונות ל-prompts או לשתף את שלך בדף התיעוד של התוסף במודל.';
$string['story'] = 'מלל לשאלות';
$string['story_help'] = 'המלל שישמש כבסיס ליצירת השאלות על ידי ה-AI.';
$string['storydesc'] = 'נא להקליד כאן את המלל שלך.';
$string['tasksuccess'] = 'משימת יצירת השאלות נוצרה בהצלחה';
$string['usepersonalprompt'] = 'שימוש בהנחיה מותאמת אישית';
$string['usepersonalpromptdesc'] = 'נא לבחור כאן אם ברצונך להשתמש בהנחיה מותאמת אישית.';

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

$string['aiquestions'] = 'Questions en IA';
$string['backtocourse'] = 'Retour au cours';
$string['createdquestionssuccess'] = 'Questions créées avec succès.';
$string['createdquestionsuccess'] = 'Question créée avec succès.';
$string['createdquestionwithid'] = 'Question créée avec l\'identifiant ';
$string['cronoverdue'] = 'La tâche cron semble ne pas s\'exécuter,
la génération des questions dépend des tâches ad hoc créées par la tâche cron, veuillez vérifier vos paramètres cron.
Consultez <a href="https://docs.moodle.org/en/Cron#Setting_up_cron_on_your_system">
https://docs.moodle.org/en/Cron#Setting_up_cron_on_your_system
</a> pour plus d\'informations.';
$string['errornotcreated'] = 'Erreur : les questions n\'ont pas été créées.';
$string['generate'] = 'Générer des questions';
$string['generatemore'] = 'Générer plus de questions';
$string['generating'] = 'Génération de vos questions en cours... (Vous pouvez quitter cette page en toute sécurité et vérifier ultérieurement dans la banque de questions)';
$string['generationfailed'] = 'La génération des questions a échoué après {$a} tentatives.';
$string['generationtries'] = 'Nombre de tentatives envoyées à OpenAI : <b>{$a}</b>.';
$string['gotoquestionbank'] = 'Accéder à la banque de questions';
$string['language'] = 'Langue';
$string['languagedesc'] = 'Veuillez sélectionner ici la langue que vous souhaitez utiliser pour la génération des questions.<br>
Notez que certaines langues sont moins bien prises en charge que d\'autres sur ChatGPT.';
$string['numofquestions'] = 'Nombre de questions';
$string['numofquestionsdesc'] = 'Veuillez sélectionner ici le nombre de questions que vous souhaitez générer.';
$string['numoftries'] = '<b>{$a}</b> tentatives.';
$string['numoftriesdesc'] = 'Veuillez indiquer ici le nombre de tentatives que vous souhaitez envoyer à OpenAI.';
$string['numoftriesset'] = 'Nombre de tentatives';
$string['openaikey'] = 'Clé d\'API OpenAI';
$string['openaikeydesc'] = 'Veuillez saisir ici votre clé d\'API OpenAI<br>
Vous pouvez obtenir votre clé d\'API sur <a href="https://platform.openai.com/account/api-keys">https://platform.openai.com/account/api-keys</a><br>
Sélectionnez le bouton "+ Créer une nouvelle clé secrète" et copiez la clé dans ce champ.<br>
Notez que vous devez disposer d\'un compte OpenAI avec des paramètres de facturation pour obtenir une clé d\'API.';
$string['outof'] = 'sur';
$string['personalprompt'] = 'Instruction personnelle';
$string['personalpromptdesc'] = "Veuillez saisir ici votre instruction personnelle.
L'instruction est l'explication donnée à ChatGPT sur la manière de générer les questions.
<br> Vous devez inclure ces deux paramètres : {{numofquestions}} et {{language}}.";
$string['pluginname'] = 'Générateur de questions à partir de texte en IA';
$string['pluginname_desc'] = 'Ce plugin vous permet de générer des questions à partir d\'un texte.';
$string['pluginname_help'] = 'Utilisez ce plugin depuis le menu d\'administration du cours.';
$string['preview'] = 'Aperçu de la question dans un nouvel onglet';
$string['privacy:metadata'] = 'Le générateur de questions à partir de texte en IA ne stocke aucune donnée personnelle.';
$string['story'] = 'Texte';
$string['storydesc'] = 'Veuillez saisir ici votre texte.';
$string['tasksuccess'] = 'La tâche de génération des questions a été créée avec succès.';
$string['usepersonalprompt'] = 'Utiliser une instruction personnelle';
$string['usepersonalpromptdesc'] = 'Veuillez sélectionner ici si vous souhaitez utiliser une instruction personnelle.';
$string['addidentifier'] = 'Ajouter le préfixe “GPT-created : ” au nom de la question';
$string['aisettingsdesc'] = 'Ce plugin utilise désormais le sous-système IA intégré de Moodle. Pour configurer les fournisseurs d\'IA (OpenAI, Azure, etc.), allez dans Administration du site > Plugins > IA > Gestion du sous-système IA. Les paramètres des fournisseurs d\'IA ont été déplacés là pour une gestion centralisée de tous les plugins compatibles IA.';
$string['aisettingsheader'] = 'Configuration IA';
$string['azureapiendpoint'] = 'Point de terminaison de l\'API Azure';
$string['azureapiendpointdesc'] = 'Entrez ici l\'URL du point de terminaison de l\'API Azure';
$string['category'] = 'Catégorie de question';
$string['category_help'] = 'Si la sélection de catégorie est vide, ouvrez une fois la banque de questions pour ce cours.';
$string['editpreset'] = 'Modifier le preset avant de l\'envoyer à l\'IA';
$string['example'] = 'Exemple';
$string['example_help'] = 'L\'exemple montre à l\'IA une sortie type pour clarifier le formatage.';
$string['instructions'] = 'Instructions';
$string['instructions_help'] = 'Les instructions indiquent à l\'IA ce qu\'elle doit faire.';
$string['model'] = 'Modèle';
$string['model_desc'] = 'Modèle linguistique à utiliser. <a href="https://platform.openai.com/docs/models/">Plus d\'infos</a>.';
$string['preset'] = 'Préréglage';
$string['presetexample'] = 'Exemple de préréglage';
$string['presetname'] = 'Nom du préréglage';
$string['presetnamedesc'] = 'Nom affiché à l\'utilisateur';
$string['presets'] = 'Préréglages';
$string['presetsdesc'] = 'Vous pouvez spécifier jusqu\'à 10 préréglages, que les utilisateurs pourront sélectionner dans leurs cours. Ils pourront toujours modifier les préréglages avant envoi.';
$string['shareyourprompts'] = 'Vous pouvez trouver plus d\'idées de prompts ou partager les vôtres sur la page de documentation du plugin Moodle.';
$string['story_help'] = 'Le texte des questions tel qu\'il sera envoyé à l\'IA pour génération.';
$string['presetinstructions'] = 'Instructions du préréglage';
$string['presetprimer'] = 'Amorce du préréglage';
$string['provider'] = 'Fournisseur GPT';
$string['providerdesc'] = 'Sélectionnez si vous utilisez Azure ou OpenAI';
$string['presetexampledefault1'] = '';
$string['presetexampledefault2'] = '';
$string['presetexampledefault3'] = '';
$string['presetexampledefault4'] = '';
$string['presetexampledefault5'] = '';
$string['presetexampledefault6'] = '';
$string['presetexampledefault7'] = '';
$string['presetexampledefault8'] = '';
$string['presetexampledefault9'] = '';
$string['presetexampledefault10'] = '';
$string['presetinstructionsdefault1'] = '';
$string['presetinstructionsdefault2'] = '';
$string['presetinstructionsdefault3'] = '';
$string['presetinstructionsdefault4'] = '';
$string['presetinstructionsdefault5'] = '';
$string['presetinstructionsdefault6'] = '';
$string['presetinstructionsdefault7'] = '';
$string['presetinstructionsdefault8'] = '';
$string['presetinstructionsdefault9'] = '';
$string['presetinstructionsdefault10'] = '';
$string['presetnamedefault1'] = '';
$string['presetnamedefault2'] = '';
$string['presetnamedefault3'] = '';
$string['presetnamedefault4'] = '';
$string['presetnamedefault5'] = '';
$string['presetnamedefault6'] = '';
$string['presetnamedefault7'] = '';
$string['presetnamedefault8'] = '';
$string['presetnamedefault9'] = '';
$string['presetnamedefault10'] = '';
$string['presetprimerdefault1'] = '';
$string['presetprimerdefault2'] = '';
$string['presetprimerdefault3'] = '';
$string['presetprimerdefault4'] = '';
$string['presetprimerdefault5'] = '';
$string['presetprimerdefault6'] = '';
$string['presetprimerdefault7'] = '';
$string['presetprimerdefault8'] = '';
$string['presetprimerdefault9'] = '';
$string['presetprimerdefault10'] = '';

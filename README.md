# AI Text to questions generator #

This plugin allows you to automatically create questions on a given text using OpenAI ChatGPT. It requires an OpenAI API Key.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/local/aiquestions

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## Usage

- In a course, or in the question bank of a course, you can find the new menu point **Ai Questions** in the secondary navigation.
- Select a preset and enter the topic of your questions. You can also modify the preset data if you want.

## Presets

- Presets can be edited by the administrator in the module's settings.
- Users can modify presets before creating questions.
- Share your presets at the Moodle Docs page for this plugin: https://docs.moodle.org/402/en/AI_Text_to_questions_generator.


## License ##

2023 Ruthy Salomon <ruthy.salomon@gmail.com> , Yedidia Klein <yedidia@openapp.co.il>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.

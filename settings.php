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
* Version information for block_a11y_check
*
* @package   block_a11y_check
* @copyright 2023 Swarthmore College
* @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

defined('MOODLE_INTERNAL') || die();

global $CFG;

if ($hassiteconfig) {

  $settings = new admin_settingpage('block_a11y_check', get_string('pluginname', 'block_a11y_check'));

  //$ADMIN->add('blockplugins', $settings); 

  $settings->add(new admin_setting_configtext('block_a11y_check/text_check_help',
        get_string('settings:text_check_help', 'block_a11y_check'),
        get_string('settings:text_check_help_desc', 'block_a11y_check'),
        'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
        PARAM_URL,
        60
    ));

    $settings->add(new admin_setting_configtext('block_a11y_check/title_check_help',
        get_string('settings:title_check_help', 'block_a11y_check'),
        get_string('settings:title_check_help_desc', 'block_a11y_check'),
        'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
        PARAM_URL,
        60
    ));

    $settings->add(new admin_setting_configtext('block_a11y_check/lang_check_help',
        get_string('settings:lang_check_help', 'block_a11y_check'),
        get_string('settings:lang_check_help_desc', 'block_a11y_check'),
        'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
        PARAM_URL,
        60
    ));

    $settings->add(new admin_setting_configtext('block_a11y_check/outline_check_help',
        get_string('settings:outline_check_help', 'block_a11y_check'),
        get_string('settings:outline_check_help_desc', 'block_a11y_check'),
        'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
        PARAM_URL,
        60
    ));

}

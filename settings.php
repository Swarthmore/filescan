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
 * @package   block_afs
 * @copyright 2019 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings->add(new admin_setting_configtext('block_afs/apiurl',
        get_string('settings:apiurl', 'block_afs'),
        get_string('settings:apiurl_desc', 'block_afs'),
        '', PARAM_TEXT, 128));

    $settings->add(new admin_setting_configtext('block_afs/numfilespercron',
        get_string('settings:numfilespercron', 'block_afs'),
        get_string('settings:numfilespercron_desc', 'block_afs'),
        '5', PARAM_TEXT, 128));

    $settings->add(new admin_setting_configtext('block_afs/maxfilesize',
        get_string('settings:maxfilesize', 'block_afs'),
        get_string('settings:maxfilesize_desc', 'block_afs'),
        128000000, PARAM_INT
    ));

    $settings->add(new admin_setting_configtext('block_afs/maxretries',
        get_string('settings:maxretries', 'block_afs'),
        get_string('settings:maxretries_desc', 'block_afs'),
        '3', PARAM_TEXT, 128));

    $settings->add(new admin_setting_configtext('block_afs/text_check_help',
        get_string('settings:text_check_help_desc', 'block_afs'),
        '',
        'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
        PARAM_URL,
        60
    ));

    $settings->add(new admin_setting_configtext('block_afs/title_check_help',
        get_string('settings:title_check_help_desc', 'block_afs'),
        '',
        'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
        PARAM_URL,
        60
    ));

    $settings->add(new admin_setting_configtext('block_afs/lang_check_help',
        get_string('settings:lang_check_help_desc', 'block_afs'),
        '',
        'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
        PARAM_URL,
        60
    ));

    $settings->add(new admin_setting_configtext('block_afs/outline_check_help',
        get_string('settings:outline_check_help_desc', 'block_afs'),
        '',
        'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
        PARAM_URL,
        60
    ));

}

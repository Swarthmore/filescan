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
 * Accessibilty file scan
 *
 * @package   block_filescan
 * @copyright 2017 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


if ($hassiteconfig) {
    
	$settings->add(new admin_setting_heading('filescan/config',
											 get_string('headerconfig', 'block_filescan'),
											 get_string('descconfig', 'block_filescan')));

	$settings->add(new admin_setting_configtext('filescan/apiurl',
													get_string('filescan_apiurl', 'block_filescan'),
													get_string('filescan_apiurl_desc', 'block_filescan'),
													'', PARAM_TEXT, 128));
													
	$settings->add(new admin_setting_configtext('filescan/numfilespercron',
													get_string('filescan_numfilespercron', 'block_filescan'),
													get_string('filescan_numfilespercron_desc', 'block_filescan'),
													'5', PARAM_TEXT, 128));
        $settings->add(new admin_setting_configtext('filescan/maxretries',
                                                                                                        get_string('filescan_maxretries', 'block_filescan'),
                                                                                                        get_string('filescan_maxretries_desc', 'block_filescan'),
                                                                                                        '3', PARAM_TEXT, 128));												
                                                
	$settings->add(new admin_setting_configtext('filescan/text_check_help',
		get_string('filescan_text_check_help_desc', 'block_filescan'),
		'',
		'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
		PARAM_TEXT
	));

	$settings->add(new admin_setting_configtext('filescan/title_check_help',
		get_string('filescan_title_check_help_desc', 'block_filescan'),
		'',
		'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
		PARAM_TEXT
	));

	$settings->add(new admin_setting_configtext('filescan/lang_check_help',
		get_string('filescan_lang_check_help_desc', 'block_filescan'),
		'',
		'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
		PARAM_TEXT
	));

	$settings->add(new admin_setting_configtext('filescan/outline_check_help',
		get_string('filescan_outline_check_help_desc', 'block_filescan'),
		'',
		'https://www.adobe.com/accessibility/pdf/pdf-accessibility-overview.html',
		PARAM_TEXT
	));
}

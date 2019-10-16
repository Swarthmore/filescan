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
 * @package   block_filescan
 * @copyright 2018 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// General
$string['pluginname']                             = 'Accessibility File Scan';
$string['filescan']                               = 'File Scan';
$string['reportheading']                          = 'File Scan Report';
$string['summaryview']                            = 'File Scan Summary';

// Capabilities
$string['filescan:addinstance']                   = 'Add a new File Scan block';
$string['filescan:myaddinstance']                 = 'Add a new File Scan block to the Moodle Dashboard';
$string['filescan:viewpages']                     = 'View File Scan';
$string['filescan:managepages']                   = 'Manage File Scan';
$string['filescan:scan']                          = 'Request a file scan';
$string['filescan:canviewadmin']                  = 'View Access File Scan Report (System-Wide)';
$string['filescan:viewadminreport']               = 'View Access File Scan Report (System-Wide)';

// Navigation and notification
$string['viewreport']                             = 'View Report';
$string['viewdetailspage']                        = 'View File Details';
$string['nofiles']                                = 'There are no files to scan';
$string['cannot_view']                            = 'Not allowed to view page';
$string['helptitle']                              = 'What is this and how do I fix it?';

// Cache
$string['cachedef_filescan']                      = 'Filescan results cache for each course';

// Summary.
$string['summary:files_found']                    = '{$a} PDF files found'; // Substituting integer.
$string['summary:files_accessible']               = '{$a} Accessible'; // Substituting integer.
$string['summary:files_partially_accessible']     = '{$a} Partially Accessible'; // Substituting integer.
$string['summary:files_inaccessible']             = '{$a} Inaccessible'; // Substituting integer.
$string['summary:files_error']                    = '{$a} File Error'; // Substituting integer.
$string['summary:files_accessibility_unknown']    = '{$a} Accessibility Unknown'; // Substituting integer.
$string['summary:last_updated']                   = 'Updated: {$a}'; // Substituting string (date).
$string['summary:title']                          = 'Summary';

// Admin block specific
$string['adminsummary:title']                     = 'At a Glance';

// Table.
$string['table:mod_header']                       = 'Mod';
$string['table:filename_header']                  = 'Filename';
$string['table:status_header']                    = 'Status';
$string['table:text_check_header']                = 'Text';
$string['table:title_check_header']               = 'Title';
$string['table:lang_check_header']                = 'Language';
$string['table:outline_check_header']             = 'Outline';
$string['table:actions_header']                   = 'Actions';
$string['table:date_checked_header']              = 'Checked';
$string['table:courseinfo_header']                = 'Course Information';
$string['cachedef_filescan'] = 'Filescan results cache for each course';

// Summary
$string['summary:files_found'] = '{$a} PDF files found'; // Substituting integer.
$string['summary:files_accessible'] = '{$a} Accessible'; // Substituting integer.
$string['summary:files_partially_accessible'] = '{$a} Partially Accessible'; // Substituting integer.
$string['summary:files_inaccessible'] = '{$a} Inaccessible'; // Substituting integer.
$string['summary:files_accessibility_unknown'] = '{$a} Accessibility Unknown'; // Substituting integer.
$string['summary:last_updated'] = 'Last updated: {$a}'; // Substituting string (date).
$string['summary:title'] = 'Summary';

// Table
$string['table:mod_header'] = 'Mod';
$string['table:filename_header'] = 'Filename';
$string['table:status_header'] = 'Status';
$string['table:text_check_header'] = 'Text';
$string['table:title_check_header'] = 'Title';
$string['table:lang_check_header'] = 'Language';
$string['table:outline_check_header'] = 'Outline';

// Scheduled tasks
$string['filescan_task']                          = 'Filescan task';
$string['reportgeneration_task']                  = 'Report generation task';
$string['cleanup_task']                           = 'Cleanup Filescan Table Task';

// Config settings
$string['headerconfig']                           = "Configure file scan";
$string['descconfig']                             = "Accessibility scan for PDF files";
$string['filescan_apiurl']                        = "Filescan API URL";
$string['filescan_apiurl_desc']                   = "URL to post files for accessibility scanning (e.g. https://example.edu/filescan";
$string['filescan_lang_check_help_desc']          = "Language Check Help Link";
$string['filescan_lang_check_help_subdesc']       = "Link to help documentation for the language check";
$string['filescan_numfilespercron']               = "Max files to scan";
$string['filescan_numfilespercron_desc']          = "Max number of files to scan per cron job";
$string['filescan_maxfilesize']                   = "Max file size to scan (in bytes)";
$string['filescan_maxretries']                    = "Max retries";
$string['filescan_maxretries_desc']               = "Maximum number of times to try scanning a file before giving up";
$string['filescan_outline_check_help_desc']       = "Outline Check Help Link";
$string['filescan_outline_check_help_subdesc']    = "Link to help documentation for the outline check";
$string['filescan_text_check_help_desc']          = "Text Check Help Link";
$string['filescan_text_check_help_subdesc']       = "Link to help documentation for the text check";
$string['filescan_title_check_help_desc']         = "Title Check Help Link";
$string['filescan_title_check_help_subdesc']      = "Link to help documentation for the title check";

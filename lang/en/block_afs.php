<?php

/**
 * @package   block_afs
 * @copyright 2019 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string["pluginname"] = "Accessibility File Scanner";
$string['filescan'] = 'Accessibility File Scanner';
$string['reportheading'] = 'Accessibility File Scanner Report';
$string['summaryview'] = 'Accessibility File Scanner Summary';

// Capabilities
$string['capability:addinstance'] = 'Add a new File Scan block';
$string['capability:myaddinstance'] = 'Add a new File Scan block to the Moodle Dashboard';
$string['capability:viewpages'] = 'View File Scan';
$string['capability:managepages'] = 'Manage File Scan';
$string['capability:scan'] = 'Request a file scan';
$string['capability:canviewadmin'] = 'View Access File Scan Report (System-Wide)';
$string['capability:viewadminreport'] = 'View Access File Scan Report (System-Wide)';

// Navigation and notification
$string['viewreport'] = 'View Report';
$string['viewdetailspage'] = 'View File Details';
$string['nofiles'] = 'There are no files to scan';
$string['cannot_view'] = 'Not allowed to view page';
$string['helptitle'] = 'What is this and how do I fix it?';

// Cache
$string['cache:afs'] = 'Filescan results cache for each course';

// Summary.
$string['summary:files_found'] = '{$a} PDF files found'; // Substituting integer.
$string['summary:files_accessible'] = '{$a} Accessible'; // Substituting integer.
$string['summary:files_partially_accessible'] = '{$a} Partially Accessible'; // Substituting integer.
$string['summary:files_inaccessible'] = '{$a} Inaccessible'; // Substituting integer.
$string['summary:files_error'] = '{$a} File Error'; // Substituting integer.
$string['summary:files_accessibility_unknown'] = '{$a} Accessibility Unknown'; // Substituting integer.
$string['summary:last_updated'] = 'Updated: {$a}'; // Substituting string (date).
$string['summary:title'] = 'Summary';

// Summary page specific
$string['summary:title'] = 'At a Glance';

// Table.
$string['table:mod_header'] = 'Mod';
$string['table:filename_header'] = 'Filename';
$string['table:status_header'] = 'Status';
$string['table:text_check_header'] = 'Text';
$string['table:title_check_header'] = 'Title';
$string['table:lang_check_header'] = 'Language';
$string['table:outline_check_header'] = 'Outline';
$string['table:actions_header'] = 'Actions';
$string['table:date_checked_header'] = 'Checked';
$string['table:courseinfo_header'] = 'Course Information';

// Summary
$string['summary:files_found'] = '{$a} PDF files found'; // Substituting integer.
$string['summary:files_accessible'] = '{$a} Accessible'; // Substituting integer.
$string['summary:files_partially_accessible'] = '{$a} Partially Accessible'; // Substituting integer.
$string['summary:files_inaccessible'] = '{$a} Inaccessible'; // Substituting integer.
$string['summary:files_accessibility_unknown'] = '{$a} Accessibility Unknown'; // Substituting integer.
$string['summary:last_updated'] = 'Last updated: {$a}'; // Substituting string (date).
$string['summary:title'] = 'Summary';

// Scheduled tasks
$string['task:scan'] = 'Filescan task';
$string['task:generate_report'] = 'Report generation task';
$string['task:cleanup'] = 'Cleanup Filescan Table Task';

// Config settings
$string['settings:headerconfig'] = "Configure file scan";
$string['settings:descconfig'] = "Accessibility scan for PDF files";
$string['settings:apiurl'] = "Filescan API URL";
$string['settings:apiurl_desc'] = "URL to post files for accessibility scanning (e.g. https://example.edu/filescan";
$string['settings:lang_check_help_desc'] = "Language Check Help Link";
$string['settings:lang_check_help_subdesc'] = "Link to help documentation for the language check";
$string['settings:numfilespercron'] = "Max files to scan";
$string['settings:numfilespercron_desc'] = "Max number of files to scan per cron job";
$string['settings:maxfilesize'] = "Max file size to scan";
$string['settings:maxfilesize_desc'] = "The max file size to scan in bytes (128000000 = 128MB)";
$string['settings:maxretries'] = "Max retries";
$string['settings:maxretries_desc'] = "Maximum number of times to try scanning a file before giving up";
$string['settings:outline_check_help_desc'] = "Outline Check Help Link";
$string['settings:outline_check_help_subdesc'] = "Link to help documentation for the outline check";
$string['settings:text_check_help_desc'] = "Text Check Help Link";
$string['settings:text_check_help_subdesc'] = "Link to help documentation for the text check";
$string['settings:title_check_help_desc'] = "Title Check Help Link";
$string['settings:title_check_help_subdesc'] = "Link to help documentation for the title check";

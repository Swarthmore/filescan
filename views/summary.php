<?php

/**
 * @package   block_afs
 * @copyright 2019 Swarthmore College ITS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php'); // global config

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

global $OUTPUT;
global $PAGE;
global $USER;
global $COURSE;

require_login();

// Define if the user has the capability to view the admin report
$canview = has_capability('block/afs:viewsummary', context_course::instance($COURSE->id));

if (!$canview) {
    die('You do not have the proper permissions to view this page.');
}

// set page details
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string("summaryview", "block_afs"));
$PAGE->set_url('/block/afs/views/summary.php', null);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('reportheading', 'block_afs'));

/**
 * This counts how many records have the passed in option
 *
 * @param $option
 * @return int
 */
function has($option)
{
    global $DB;
    $table = 'block_afs';

    switch ($option) {
        case 'text':
            return $DB->count_records($table, ['hastext' => 1]);
            break;
        case 'title':
            return $DB->count_records($table, ['hastitle' => 1]);
            break;
        case 'outline':
            return $DB->count_records($table, ['hasoutline' => 1]);
            break;
        case 'language':
            return $DB->count_records($table, ['haslanguage' => 1]);
            break;
        default: // catch all
            return 0;
            break;
    }
}

/**
 * This function will generate a report encapsulating all files within the plugin table
 *
 * @param $status
 * @return int
 */

function generateOverallReport($status)
{
    global $DB;

    $table = 'block_afs';

    switch ($status) {
        case 'passing':
            return $DB->count_records($table, ['status' => 'pass']);
            break;
        case 'fails':
            return $DB->count_records($table, ['status' => 'fail']);
            break;
        case 'errors':
            return $DB->count_records($table, ['status' => 'error']);
            break;
        case 'checks':
            return $DB->count_records($table, ['status' => 'check']);
            break;
        default: // catch all
            return 0;
            break;
    }
}

/**
 * This function returns the total records within $table
 *
 * @param $table
 * @return int
 */

function getTotalRecords($table)
{
    global $DB;
    return $DB->count_records($table, $conditions = null);
}

// start outputting our page
echo $OUTPUT->header();

echo "<div id='summary-root'></div>";

// call the datatables module
$PAGE->requires->js_call_amd("block_afs/afs");

echo $OUTPUT->footer();
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

require_once('../../config.php');             // global config

defined('MOODLE_INTERNAL') || die('Direct access to this script is forbidden.');

global $OUTPUT;
global $PAGE;
global $USER;
global $COURSE;

require_login();

// Define if the user has the capability to view the admin report
$canview = has_capability('block/filescan:viewadminreport', context_course::instance($COURSE->id));

if (!$canview) {
  die('You do not have the proper permissions to view this page.');
}

// set page details
$PAGE->set_context(context_system::instance());
$PAGE->set_title('File Scan Report (System-wide)'); // to do: put this in lang string file
$PAGE->set_url('/block/filescan/admin.php', null);
$PAGE->set_pagelayout('base');
$PAGE->set_heading(get_string('reportheading', 'block_filescan'));

/**
 * This counts how many records have the passed in option
 *
 * @param $option
 * @return int
 */
function has($option)
{
  global $DB;
  $table = 'block_filescan_files';

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

  $table = 'block_filescan_files';

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

// Load the datatables css file
// This needs to come before the header
$PAGE->requires->css('/blocks/filescan/css/datatables.min.css', true);

// start outputting our page
echo $OUTPUT->header();

// call the datatables module
$PAGE->requires->js_call_amd('block_filescan/app', 'init');

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

// TODO: move all this output junk into a function or class
// generate the dashboard totals
$checks         = array('text', 'title', 'language', 'outline');
$totalRecords   = getTotalRecords('block_filescan_files');

echo html_writer::tag('h2', get_string('adminsummary:title', 'block_filescan'), array());

// generate the title, text, outline and language card row
echo html_writer::start_tag('div', array(), null);

  foreach ($checks as $check) {
    $fileHas    = has($check);

    // Prevent division by zero errors
    $completed = $totalRecords !== 0 ? round($fileHas / $totalRecords * 100, 2) : 0;

    // Generate the output string for each check
    $out = "<p><strong>" . ucfirst($check) . "</strong>" . ": " . $completed . "%" . "<br>" . $fileHas . "/" . $totalRecords . " PDFs" ."</p>";
    echo $out;
  }

echo html_writer::end_tag('div');

// Create the DataTable structure
// TODO: write this in html writer
echo
'<table id="view" class="table table-striped table-bordered dataTable" style="width: 100%;" role="grid" aria-describedby="PDFs in your Moodle instance">
  <thead>
      <tr>
        <th class="sorting">' . get_string('table:status_header', 'block_filescan') . '</th>
        <th class="sorting">' . get_string('table:text_check_header', 'block_filescan') .
'</th>
        <th class="sorting">' . get_string('table:title_check_header', 'block_filescan') .
'</th>
        <th class="sorting">' . get_string('table:lang_check_header', 'block_filescan') .
'</th>
        <th class="sorting">' . get_string('table:outline_check_header', 'block_filescan') .
'</th>
        <th class="sorting">' . get_string('table:date_checked_header', 'block_filescan') .
'</th>
        <th class="sorting">' . get_string('table:courseinfo_header', 'block_filescan') .
'</th>
      </tr>
  </thead>
</table>';

echo $OUTPUT->footer();
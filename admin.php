<?php

require_once('../../config.php');             // global config

global $OUTPUT;
global $PAGE;
global $USER;
global $COURSE;

// Define if the user has the capability to view the admin report
$canview = has_capability('block/filescan:viewadminreport', context_course::instance($COURSE->id));

if (!$canview) {
  die('You do not have the proper permissions to view this page.');
}

// set page details
$PAGE->set_context(context_system::instance());
$PAGE->set_title('File Scan Report (System-wide)');
$PAGE->set_url('/block/filescan/admin.php', null);
$PAGE->set_pagelayout('base');
$PAGE->set_heading(get_string('reportheading', 'block_filescan'));

// use this variable to scale the bars under the displayed percentages
$scale = array(
  'x' => 1,
  'y' => 0.5 
);

// use this variable to define the width and height (in pixels) of the progress bars
// located in each card
$progressBar = array(
  'width'   => 100 * $scale['x'],
  'height'  => 16  * $scale['y']
);

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

// start outputting our page
echo $OUTPUT->header();

// call the datatables module
$PAGE->requires->js_call_amd('block_filescan/dt', 'init');

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

echo html_writer::tag('h4', 'At a Glance', array('class' => 'text-primary'));

// generate the title, text, outline and language card row
echo html_writer::start_tag('div', array('class' => 'card-group'), null);

foreach ($checks as $check) {
  $fileHas    = has($check); // do this so we dont kill the db
  $completed  = round($fileHas / $totalRecords * 100,2);

  $fillAttributes = array(
    'style' => 'width: ' . $completed * $scale['x'] . 'px; height: ' . $progressBar['height'] . 'px; max-width: 95%;',
    'class' => 'striped-success bg-success'
  );

  $cardAttributes = array(
    'class' => 'card text-white bg-dark m-3 text-center'
  );

  $primaryCard = array(
    'class' => 'card text-white bg-primary m-3 text-center'
  );

  echo html_writer::start_tag('div', $cardAttributes, null);
  echo html_writer::start_tag('div', array('class' => 'card-body'), null);
  echo html_writer::tag('p', ucfirst($check), array('class' => 'lead'));

  echo html_writer::tag('h4', $completed . '%', array('class' => 'text-white'));
  echo html_writer::start_tag('div', array('style' => 'margin: 0 auto;  max-width: 95%; width: ' . $progressBar['width'] . 'px;', 'class' => 'mb-2 striped-danger bg-danger'), null);
  echo html_writer::tag('div', null, $fillAttributes);

  echo html_writer::end_tag('div');

  echo html_writer::tag('p', $fileHas . ' / ' . $totalRecords . ' PDFs', array('class' => 'mt-3', 'style' => 'color: #bdbfc0;'));

  echo html_writer::end_tag('div');
  echo html_writer::end_tag('div');
}

echo html_writer::end_tag('div');

// Generate the datatable
echo
'
  <div class="container-fluid">
    <main>       
      <table id="view" class="table mx-2 my-3" style="width: 100%;">
        <thead>
            <tr class="bg-primary text-white">
              <th class="text-center">Status</th>
              <th class="text-center">Text</th>
              <th class="text-center">Title</th>
              <th class="text-center">Language</th>
              <th class="text-center">Outline</th>
              <th class="text-center">Checked</th>
              <th>Course Information</th>
            </tr>
        </thead>
      </table>  
    </main>
  </div>
';

// these styles are only needed if you are running the Boost theme.
echo '
<style>
.dataTables_paginate,
.dataTables_info {
    font-size: 16px;
}
.dataTables_paginate a {
    padding: 10px;
}
.paginate_button.next, .paginate_button.previous {
    font-weight: bold;
    padding: 10px 16px;
}
.list-fix li {
border: 0;
margin: 0;
padding: 0;
}
.striped-danger {
  background: repeating-linear-gradient(
    45deg,
    #c62828,
    #c62828 10px,
    #e53935 10px,
    #e53935 20px
  );
}

.striped-success {
  background: repeating-linear-gradient(
      45deg,
      #43a047,
      #43a047 10px,
      #388e3c 10px,
      #388e3c 20px
    );
}
</style>
';

echo $OUTPUT->footer();
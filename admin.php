<?php

// filename
// course name
// teachers
// # enrolled

require_once('../../config.php');             // global config
$config = include_once('config/config.php');  // plugin config

// if config indicates development mode, then allow errors to be thrown to the dom
if ($config['env'] === 'development') {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  ini_set('memory_limit', '1024M');
  error_reporting(-1);
}

global $OUTPUT;
global $PAGE;

// meta stuff
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Moodle Accessibility File Checker');
$PAGE->set_url('/block/filescan/admin.php', null);
$PAGE->set_pagelayout('base');
$PAGE->set_heading(get_string('adminsonly', 'block_filescan'));
$PAGE->set_cacheable($config['cacheable']);

// a valid token is required to access the web service.
// see: https://docs.moodle.org/35/en/Using_web_services
$token = array('token' => $config['token']);

// use this variable to scale the bars under the displayed percentages
$scale = array(
  'x' => 2,
  'y' => 1
);

// use this variable to define the width and height (in pixels) of the progress bars
// located in each card
$progressBar = array(
  'width'   => 100 * $scale['x'],
  'height'  => 16  * $scale['y']
);

/**
 * This function should return a filepath given a filehash
 *
 * @param $filehash
 * @return string
 */

//function get_file_from_hash($filehash) {
//  global $DB;
//
//  $fs = get_file_storage();
//
//  // get the row
//  $fileinfo = $DB->get_records('files', $conditions = array('contenthash' => $filehash), $sort='', $fields='*', $limitfrom=0, $limitnum=0);
//
//  // reset internal pointer to first item
//  reset($fileinfo);
//
//  // return the current element at the internal pointer
//  $f = current($fileinfo);
//
//  $component = 'mod';
//  $filearea = 'resource';
//
////    // join contenthash on files table
////    // grab that contextid
////    // join the contextid on the context table
////
////    return $DB->get_records($table, array('contenthash' => $filehash));
//
//  $file = $fs->get_file(
//    $f->contextid,
//    $component,
//    $filearea,
//    $f->itemid,
//    $f->filepath,
//    $f->filename
//  );
//
//  if (!$file) {
//    return 'File not found';
//  } else {
//    return $file;
//  }
//}

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

// call the datatables module
$PAGE->requires->js_call_amd('block_filescan/dataTableModule', 'make', $token);

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
$checks         = array('title', 'text', 'outline', 'language');
$totalRecords   = getTotalRecords('block_filescan_files');

// start outputting our page
echo $OUTPUT->header();

// testing area
//echo '<p class="debug">' . print_r(get_file_from_hash('5eb368ec30c2952742fa67eaf26e36b0b75a7598'), true) . '</p>';

// generate the title, text, outline and language card row
echo html_writer::start_tag('div', array('class' => 'card-group'), null);

foreach ($checks as $check) {
  $fileHas    = has($check); // do this so we dont kill the db
  $completed  = round($fileHas / $totalRecords * 100,2);

  $fillAttributes = array(
    'style' => 'width: ' . $completed * $scale['x'] . 'px; height: ' . $progressBar['height'] . 'px;',
    'class' => 'bg-success'
  );

  $cardAttributes = array(
    'class' => 'card text-white bg-dark m-3 p-3 text-center'
  );

  $primaryCard = array(
    'class' => 'card text-white bg-primary m-3 p-3 text-center'
  );

  echo html_writer::start_tag('div', $cardAttributes, null);
  echo html_writer::start_tag('div', array('class' => 'card-body'), null);
  echo html_writer::tag('p', ucfirst($check), array('class' => 'lead'));

  echo html_writer::tag('h3', $completed . '%', array('class' => 'text-white display-4'));
  echo html_writer::start_tag('div', array('style' => 'margin: 0 auto; width: ' . $progressBar['width'] . 'px;', 'class' => 'mb-2 bg-danger'), null);
  echo html_writer::tag('div', null, $fillAttributes);

  echo html_writer::end_tag('div');

  echo html_writer::tag('p', $fileHas . ' / ' . $totalRecords, array('class' => 'text-muted'));

  echo html_writer::end_tag('div');
  echo html_writer::end_tag('div');
}

echo html_writer::end_tag('div');

// Generate the middle content area
echo html_writer::start_tag('div', array('class' => 'card-group'), null);

//card 1
echo html_writer::start_tag('div', $cardAttributes, null);
echo html_writer::start_tag('div', array('class' => 'card-body'), null);
echo html_writer::tag('h1', 'What goes here?', array('class' => 'display-3'));
echo '<iframe width="560" height="315" src="https://www.youtube.com/embed/fq3abPnEEGE" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>';
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// card 2
// card 2 variables
$passingFiles = generateOverallReport('passing');
$pctFixed = round($passingFiles / $totalRecords, 4);

// card 2 output
echo html_writer::start_tag('div', $primaryCard, null);
echo html_writer::start_tag('div', array('class' => 'card-body mt-5'), null);
echo html_writer::tag('p', 'Total Passing Files', array('class' => 'lead'));
echo html_writer::tag('h1', $pctFixed . '%', array('class' => 'display-1'));
echo html_writer::tag('p', $passingFiles . ' / ' . $totalRecords, array('class' => 'text-white'));
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// end our card div
echo html_writer::end_tag('div');

// Generate the datatable
$columns = array('Id', 'Pages', 'Status', 'Checked', 'Text', 'Title', 'Outline', 'Language', 'Last Checked', 'Courses', 'FP', 'Action');

echo html_writer::start_tag('div', array('class' => 'table-responsive dataTable'), null);
echo html_writer::start_tag('table', array('id' => 'dt', 'class' => 'm-3 table table-md'), null);
echo html_writer::start_tag('thead', null);
echo html_writer::start_tag('tr', null, null);

foreach($columns as $column) {
  echo html_writer::tag('th', $column, array('score' => 'col'));
}

echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::end_tag('table');
echo html_writer::end_tag('div');

echo $OUTPUT->footer();

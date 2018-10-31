<?php

require_once('../../config.php'); // global config

$config = include_once('config/config.php'); // plugin config

// if config indicates development mode, then allow errors to be thrown to the dom
if ($config['env'] === 'development') {
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  ini_set('memory_limit', '1024M');
  error_reporting(-1);
}

global $OUTPUT;
global $PAGE;

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
    default:
      return 0;
      break;
  }
}

// This function will generate a report encapsulating all files within the plugin table
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
    default:
      break;
  }
}

function getFiles()
{
  global $DB;
  $table = 'block_filescan_files';
  return $DB->get_records_list($table, 'status', array('check', 'fail', 'error'));
}

$PAGE->set_url('/block/filescan/admin.php', null);
$PAGE->set_pagelayout('base');
$PAGE->set_heading(get_string('adminsonly', 'block_filescan'));

$token = array('token' => $config['token']);

$PAGE->requires->js_call_amd('block_filescan/dt', 'make', $token);

echo $OUTPUT->header();

// TODO: make this a class function
function getTotalRecords($table)
{
  global $DB;
  return $DB->count_records($table, $conditions = null);
}

// TODO: move all this output junk into a function or class
// generate the dashboard totals
$checks         = array('title', 'text', 'outline', 'language');
$totalRecords   = getTotalRecords('block_filescan_files');

// use this variable to scale the bars under the displayed percentages
$scale = array(
  'x' => 1.5,
  'y' => 1.0
);

$progressBar = array(
  'width'   => 100 * $scale['x'],
  'height'  => 16  * $scale['y']
);

// generate the title, text, outline and language card row
echo html_writer::start_tag('div', array('class' => 'card-group'), null);

foreach ($checks as $check) {
  $fileHas    = has($check); // do this so we dont kill the db
  $completed  = round($fileHas / $totalRecords * 100,2);

  $fillArray = array(
    'style' => 'width: ' . $completed * $scale['x'] . 'px; height: ' . $progressBar['height'] . 'px;',
    'class' => 'bg-success'
  );

  echo html_writer::start_tag('div', array('class' => 'card text-white bg-dark m-3 p-3'), null);
  echo html_writer::start_tag('div', array('class' => 'card-body'), null);
  echo html_writer::tag('p', ucfirst($check), array('class' => 'lead'));

  echo html_writer::tag('h3', $completed . '%', array('class' => 'text-white display-4'));
  echo html_writer::start_tag('div', array('style' => 'width: ' . $progressBar['width'] . 'px;', 'class' => 'bg-danger'), null);
  echo html_writer::tag('div', null, $fillArray);

  echo html_writer::end_tag('div');

  echo html_writer::tag('p', $fileHas . ' / ' . $totalRecords, array('class' => 'text-muted'));

  echo html_writer::end_tag('div');
  echo html_writer::end_tag('div');
}

echo html_writer::end_tag('div');

// Generate the middle content area
echo html_writer::start_tag('div', array('class' => 'card-group'), null);

//card 1
echo html_writer::start_tag('div', array('class' => 'card bg-dark text-white m-3 p-3'), null);
echo html_writer::start_tag('div', array('class' => 'card-body'), null);
echo html_writer::tag('h1', 'What goes here? It is a mystery', array('class' => 'display-3'));
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// card 2
echo html_writer::start_tag('div', array('class' => 'card bg-dark text-white m-3 p-3 text-center'), null);
echo html_writer::start_tag('div', array('class' => 'card-body'), null);
echo html_writer::tag('p', 'Total Passing Files', array('class' => 'lead'));
echo html_writer::tag('h1', generateOverallReport('passing'), array('class' => 'display-1'));
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');

// Generate the datatable
$columns = array('Id', 'Pages', 'Status', 'Checked', 'Text', 'Title', 'Outline', 'Language', 'Last Checked', 'Courses', 'Path');

echo html_writer::start_tag('div', array('class' => 'table-responsive'), null);
echo html_writer::start_tag('table', array('id' => 'dt', 'class' => 'table table-dark text-white table-sm', 'style' => 'width: 100%;'), null);
echo html_writer::start_tag('thead', array('class' => 'thead-dark'));
echo html_writer::start_tag('tr', null, null);

foreach($columns as $column) {
  echo html_writer::tag('th', $column, array('score' => 'col'));
}

echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::end_tag('table');
echo html_writer::end_tag('div');

echo $OUTPUT->footer();

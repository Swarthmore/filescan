<?php

const env = 'development';

if (env === 'development') {
    ini_set('display_errors',1);
    ini_set('display_startup_errors',1);
    ini_set('memory_limit', '1024M');
    error_reporting(-1);
}

require_once('../../config.php');

global $OUTPUT;
global $PAGE;

$colors = array(
  'pass'  => 'rgb(76, 175, 80, 1)',
  'check' => 'rgb(255, 193, 7, 1)',
  'error' => 'rgb(244, 67, 54, 1)',
  'fail'  => 'rgb(255, 87, 34, 1)'
);

function has($option) {
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
      return $DB->count_records($table, [ 'status'=> 'pass' ]);
      break;
    case 'fails':
      return $DB->count_records($table, [ 'status'=> 'fail' ]);
      break;
    case 'errors':
      return $DB->count_records($table, [ 'status'=> 'error' ]);
      break;
    case 'checks':
      return $DB->count_records($table, [ 'status'=> 'check' ]);
      break;
    default: break;
  }

}

function getFiles() {
  global $DB;
  $table = 'block_filescan_files';
  return $DB->get_records_list($table, 'status', array('check', 'fail', 'error'));
}

$PAGE->set_url('/block/filescan/admin.php', null);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('adminsonly', 'block_filescan'));

// Define data to pass to our chart function
$params = array(
  'passing'     => generateOverallReport('passing'),
  'checks'      => generateOverallReport('checks'),
  'errors'      => generateOverallReport('errors'),
  'failures'    => generateOverallReport('fails'),
  'hasText'     => has('text'),
  'hasTitle'    => has('title'),
  'hasOutline'  => has('outline'),
  'hasLanguage' => has('language')
);

// Call our javascript modules

// todo: moodle call to get user's token
$token = '';

$PAGE->requires->js_call_amd('block_filescan/charts', 'draw', $params);
$PAGE->requires->js_call_amd('block_filescan/mog', 'make', $token);

echo $OUTPUT->header();

echo '<h2>Totals</h2>';
echo '<h5>Passing: '  . generateOverallReport('passing') . '</h5>';
echo '<h5>Checks: '   . generateOverallReport('checks' ) . '</h5>';
echo '<h5>Errors: '   . generateOverallReport('errors' ) . '</h5>';
echo '<h5>Fails: '    . generateOverallReport('fails'  ) . '</h5>';

// TODO: Write this with HTML Writer
echo '
  <div id="app">
    <div class="container-fluid">
        <div class="row">
          <div class="col-md-6">
            <div class="card mx-2 my-3">
              <div id="c1" class="card-body"></div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card mx-2 my-3">
              <div id="c2" class="card-body"></div>
            </div>
          </div>
          
        </div>
    </div>
  </div>
';


echo '
<table class="table table-striped table-bordered" id="mog">
    <thead>
        <tr>
            <th>ID</th>
            <th>Pages</th>
            <th>Status</th>
            <th>Checked</th>
            <th>Text</th>
            <th>Title</th>
            <th>Outline</th>
            <th>Language</th>
            <th>Checked On</th>
            <th>Courses</th>
        </tr>
    </thead>
</table>
';

echo $OUTPUT->footer();

<?php

// enable php errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class block_filescan extends block_base
{

  public function init()
  {
    $this->title = get_string('filescan', 'block_filescan');
  }

  // This function tells Moodle that the block can be viewed on the site, my (dashboard), and course
  public function applicable_formats()
  {
    return [
      'site' => true,
      'my' => true,
      'course' => true
    ];
  }

  // when you enter a course, this pulls the files for the selected course
  // there's a caching mechanism for this -- this is only called when there's
  // not a cache summary in place

  private function get_course_files()
  {

    global $COURSE;
    global $CFG;
    global $DB;

    require_once($CFG->dirroot . '/course/lib.php');

    $cms = get_fast_modinfo($COURSE)->get_cms();
    $file_list = array();  // Array containing detailed file info
    $fs = get_file_storage();

    // Loop through each module in the course looking for files
    foreach ($cms as $cm) {

      if ($cm->is_user_access_restricted_by_capability()) {
        continue;
      }

      $cmtype = $cm->modname;
      $module_name = $cm->name;
      $section_no = $cm->get_course_module_record(true)->sectionnum;
      $sectionName = get_section_name($COURSE->id, $section_no);

      // Check if the resource is a folder. If it is a folder, then get all files with a mime type
      // of application/pdf and push them to $file_list
      // If the resourse is a file, then get all pdf files in the "file" resource

      if ($cmtype === 'folder') {
        $cmfiles = $fs->get_area_files($cm->context->id, 'mod_folder', 'content', false, 'timemodified', false);
        foreach ($cmfiles as $f) {
          if (isset($f) && ($f->get_mimetype() === 'application/pdf')) {
            array_push($file_list, $f->get_contenthash());
          }
        }
      } else if ($cmtype === 'resource') { // Check if the resource is a file

        // Get files in "file" resource
        $files = $fs->get_area_files($cm->context->id, 'mod_resource', 'content', false, 'timemodified', false);
        foreach ($files as $f) {
          if (isset($f) && ($f->get_mimetype() === 'application/pdf')) {
            array_push($file_list, $f->get_contenthash());
          }
        }
      }
    }
    return $file_list;
  }

  /**
   * Counts the records in the database for each 'check result' and returns them as an array
   *
   * @return mixed
   */

  private function generate_admin_summary()
  {
    global $DB;
    $table = 'block_filescan_files';

    $results['pass']    = $DB->count_records($table, ['status' => 'pass']);
    $results['fails']   = $DB->count_records($table, ['status' => 'fail']);
    $results['errors']  = $DB->count_records($table, ['status' => 'error']);
    $results['checks']  = $DB->count_records($table, ['status' => 'check']);

    return $results;
  }

  private function generate_summary($courseid)
  {
    global $DB;

    $filelist             = $this->get_course_files();
    $accessible           = 0;
    $partially_accessible = 0;
    $inaccessible         = 0;
    $unknown              = 0;

    foreach ($filelist as $f) {
      // For each file, lookup file scan status

      $record = $DB->get_record("block_filescan_files", array('contenthash' => $f));

      if ($record) {
        switch ($record->status) {
          case 'pass':
            $accessible++;
            break;

          case 'fail':
            $inaccessible++;
            break;

          case 'check':
            $partially_accessible++;
            break;

          default:
            $unknown++;
            break;
        }
      }

//      if ($record && $record->status == 'pass') {
//        $accessible += 1;
//      } else if ($record && $record->status == 'fail') {
//        $inaccessible += 1;
//      } else if ($record && $record->status == 'check') {
//        $partially_accessible += 1;
//      } else {
//        $unknown += 1;
//      }

    }

    $output = get_string('summary:files_found', 'block_filescan', count($filelist));

    if ($accessible > 0) {

      $output .= html_writer::tag('br', null);
      $output .= html_writer::tag('i', null, [ 'class' => 'fa fa-check text-success fa-fw', 'aria-hidden' => 'true' ]);
      $output .= html_writer::tag('span', get_string('summary:files_accessible', 'block_filescan', $accessible));

    }

    if ($partially_accessible > 0) {

      $output .= html_writer::tag('br', null);
      $output .= html_writer::tag('i', null, [ 'class' => 'fa fa-check text-success fa-fw', 'aria-hidden' => 'true' ]);
      $output .= html_writer::tag('span', get_string('summary:files_partially_accessible', 'block_filescan',
        $partially_accessible));

    }

    if ($inaccessible > 0) {
      $output .= html_writer::tag('br', null);
      $output .= html_writer::tag('i', null, [ 'class' => 'fa fa-check text-success fa-fw', 'aria-hidden' => 'true' ]);
      $output .= html_writer::tag('span', get_string('summary:files_inaccessible', 'block_filescan', $inaccessible));
    }

    if ($unknown > 0) {
      $output .= html_writer::tag('br', null);
      $output .= html_writer::tag('i', null, [ 'class' => 'fa fa-check', 'aria-hidden' => 'true' ]);
      $output .= html_writer::tag('span', get_string('summary:files_accessibility_unknown', 'block_filescan', $unknown));
    }

    $output .= html_writer::tag('br', null);
    $output .= html_writer::tag('br', null);
    $output .= html_writer::tag('p', get_string('summary:last_updated', 'block_filescan', date("m/d/Y g:iA")));

    return $output;
  }

  /**
   * Moodle calls this to display the content in the block
   */

  public function get_content()
  {

    global $COURSE;
    global $CFG;
    global $DB;

    $table = 'block_filescan_files';

    $results['pass']    = $DB->count_records($table, ['status' => 'pass']);
    $results['fails']   = $DB->count_records($table, ['status' => 'fail']);
    $results['errors']  = $DB->count_records($table, ['status' => 'error']);
    $results['checks']  = $DB->count_records($table, ['status' => 'check']);

    require_once($CFG->dirroot . '/course/lib.php');

    $context      = context_course::instance($COURSE->id);
    $canview      = has_capability('block/filescan:viewpages', $context);
    $canviewadmin = has_capability('block/filescan:viewadminreport', $this->page->context);

    // If the role can't view the block, return an empty string for the content
    // and exit (blocks with no content aren't shown)
    if (!$canview) {
      $this->content = new stdClass;
      $this->content->text = "";
      $this->content->footer = "";
      return $this->content;
    }

    // Determine course metadata
    $coursename       = $COURSE->fullname;
    $courseshortname  = $COURSE->shortname;
    $courseurl        = course_get_url($COURSE);

    // Determine if the file scan has been previously cached or not
    $cache          = cache::make('block_filescan', 'filescan');
    $filescan_cache = $cache->get($COURSE->id);

    if ($filescan_cache) {
      $filescan_summary = $filescan_cache;
    } else {
      $filescan_summary = $this->generate_summary($COURSE->id);
      $result           = $cache->set($COURSE->id, $filescan_summary);
    }

    if ($this->content !== null) {
      return $this->content;
    }

    $this->content = new stdClass;

    // check if the user has the viewadminreport capability. If they do, then the URL and Summary are changed
    if ($canviewadmin) {

      $url = new moodle_url('/blocks/filescan/admin.php');

      $this->content->text = '<h4>Adm Summary</h4>'
        . html_writer::tag('p', 'Passing: ' . $results['pass'], null)
        . html_writer::tag('p', 'Fails: '   . $results['fails'], null)
        . html_writer::tag('p', 'Errors: ' . $results['errors'], null)
        . html_writer::tag('p', 'Checks: ' . $results['checks'], null);

      $this->content->footer = html_writer::link($url, get_string('adminsonly', 'block_filescan'));

    } else {
      $url                    = new moodle_url('/blocks/filescan/view.php', ['courseid' => $COURSE->id] );
      $this->content->text    = '<h4>Summary</h4>' . $filescan_summary;
      $this->content->footer  = html_writer::link($url, get_string('viewdetailspage', 'block_filescan'));
    }

  }

  // Tell Moodle there is a configuration file in settings.php (default)
  function has_config() { return true; }

}

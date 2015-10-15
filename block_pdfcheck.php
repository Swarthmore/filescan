<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);




class block_pdfcheck extends block_base {
    public function init() {
        $this->title = get_string('pdfcheck', 'block_pdfcheck');
    }

public function get_content() {

	global $COURSE, $CFG, $DB;
	require_once($CFG->dirroot . '/course/lib.php');
	$fs = get_file_storage();
 	$context = context_course::instance($COURSE->id);
 	$canview = has_capability('block/pdfcheck:viewpages', $context);
 	
 	// If the role can't view the block, return an empty string for the content
	// and exit (blocks with no content aren't shown)
	if 	(!$canview) {
	    $this->content =  new stdClass;
    	$this->content->text   = "";
		$this->content->footer = "";
     	return $this->content;	
	}
 	
 	
 	
 	// Determine course metadata
	$coursename = $COURSE->fullname;
    $courseshortname = $COURSE->shortname;
    $courseurl = course_get_url($COURSE);
	
	$filenames = "";
	$output_html = "";
	$file_listing = [];
	$cmid = null;
		
	// Get any existing data from db
	$db_data = $DB->get_record('block_pdfcheck', array('blockid' => $this->instance->id));
	
	// If record doesn't exist, initialize the data array
	if (!isset($db_data->id)) {$db_data= new stdClass;}
	
	// Save data to database
	$db_data->scanresults = "Not yet scanned";
	$db_data->processing = true;
	$db_data->blockid = $this->instance->id;
	
	if (isset($db_data->id)) {
		if (!$DB->update_record('block_pdfcheck', $db_data)) {
			print_error('updateerror', 'block_pdfcheck');
		}
	} else {
		if (!$DB->insert_record('block_pdfcheck', $db_data)) {
			print_error('inserterror', 'block_pdfcheck');
		}		
	}
	

	

    if ($this->content !== null) {
      return $this->content ;
    }
    

    $this->content         =  new stdClass;
    $this->content->text   = 'Status of each PDF file in the course: <BR>' . $output_html;
    
	$url = new moodle_url('/blocks/pdfcheck/view.php', array('blockid' => $this->instance->id, 'courseid' => $COURSE->id));
	$this->content->footer = html_writer::link($url, get_string('viewdetailspage', 'block_pdfcheck'));

 
    return $this->content;
  }
}   // Here's the closing bracket for the class definition


?>





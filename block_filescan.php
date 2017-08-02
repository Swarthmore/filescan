<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);




class block_filescan extends block_base {



    public function init() {
        $this->title = get_string('filescan', 'block_filescan');
    }

    public function applicable_formats() {
        return array('site' => false, 'my' => false, 'course' => true);
    }

private function get_file_list() {

	global $COURSE, $CFG, $DB;
	require_once($CFG->dirroot . '/course/lib.php');

	$cms = get_fast_modinfo($COURSE)->get_cms();
	$file_list = array();	// Array containing detailed file info
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
	
		// if resource is a folder
		if ($cmtype === 'folder') {
			$cmfiles = $fs->get_area_files($cm->context->id, 'mod_folder', 'content', false, 'timemodified', false);

			// Loop through all files in a folder
			foreach ($cmfiles as $f) {
		
				if (isset($f) && ($f->get_mimetype() === 'application/pdf')) {
					array_push($file_list, $f->get_contenthash());					
				}
			}
		
		} else if ($cmtype === 'resource') { // if resource is a file.
		
			// Get files in "file" resource 
			$files = $fs->get_area_files($cm->context->id, 'mod_resource', 'content', false, 'timemodified', false);
		
			// Loop through each file
			foreach ($files as $f) {
				if (isset($f) && ($f->get_mimetype() === 'application/pdf')) {
					array_push($file_list, $f->get_contenthash());						
				}
			}
		}	
	}
	
	return $file_list;

}


  	private function generate_summary($courseid) {
		global $DB;
	
		$filelist = $this->get_file_list();
		$accessible = 0;
		$partially_accessible = 0;
		$inaccessible = 0;
		$unknown = 0;
	
		foreach($filelist as $f) {
			// For each file, lookup file scan status
			$record = $DB->get_record("block_filescan_files", array('contenthash'=>$f));
			if ($record && $record->status	== 'pass') {
				$accessible = $accessible + 1;
			} else if ($record && $record->status == 'fail') {
				$inaccessible = $inaccessible + 1;
			} else if ($record && $record->status == 'check') {
				$check = $partially_accessible + 1;
			} else {
				$unknown = $unknown + 1;
			}
		}
	
		$format = 'There are %d PDF files.<BR>%d Accessible<BR>%d Partially Accessible<BR>%d Inaccessible<BR>%d Unknown<BR><BR>Last updated: %s';
		$output = sprintf($format, count($filelist), $accessible, $partially_accessible, $inaccessible, $unknown, date("m/d/Y g:iA"));
		return $output;
	}










public function get_content() {

	global $COURSE, $CFG, $DB;
	require_once($CFG->dirroot . '/course/lib.php');

 	$context = context_course::instance($COURSE->id);
 	$canview = has_capability('block/filescan:viewpages', $context);
 	
 
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

		
 	$cache = cache::make('block_filescan', 'filescan');
 	$filescan_cache = $cache->get($COURSE->id);
 	if ($filescan_cache) {
 		// Cache was found.  Print out summary
 		$filescan_summary = $filescan_cache;
		$filescan_summary .= "<BR>CACHE<BR>";
 	} else {
 		// No cache data found.  Generate summary and save to cache
 		$filescan_summary = $this->generate_summary($COURSE->id);
 		$result = $cache->set($COURSE->id, $filescan_summary);
 		$filescan_summary .= "<BR>NO CACHE<BR>";
 	}		
		


    if ($this->content !== null) {
      return $this->content ;
    }
    

    $this->content         =  new stdClass;
    $this->content->text   = '<h4>Summary</h4>' . $filescan_summary;
    
	$url = new moodle_url('/blocks/filescan/view.php', array('courseid' => $COURSE->id));
	$this->content->footer = html_writer::link($url, get_string('viewdetailspage', 'block_filescan'));

 
    return $this->content;
  }
  
  
  	// Tell Moodle there is a global config
 	function has_config() {return true;}
  
  
  
}   // Here's the closing bracket for the class definition

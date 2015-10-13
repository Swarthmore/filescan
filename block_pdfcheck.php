<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

require 'upload-files-to-s3.php';


class block_pdfcheck extends block_base {
    public function init() {
        $this->title = get_string('pdfcheck', 'block_pdfcheck');
        init_aws_services();
    }

public function get_content() {

	global $COURSE, $CFG;
	
	require_once($CFG->dirroot . '/course/lib.php');
	
	$fs = get_file_storage();
	
	
 	$context = context_course::instance($COURSE->id);
	$coursename = $COURSE->fullname;
    $courseshortname = $COURSE->shortname;
    $courseurl = course_get_url($COURSE);
	
	$filenames = "";
	$output_html = "";
	$file_listing = [];
	$cmid = null;
	
		
	
	$cms = ($cmid === null) ? get_fast_modinfo($COURSE)->get_cms() : array(get_fast_modinfo($COURSE)->get_cm($cmid));

	


	
	
    $fileitems = array();
	$previousSectionName = "";
	foreach ($cms as $cm) {
		if ($cm->is_user_access_restricted_by_capability()) {
			continue;
		}
		$cmtype = $cm->modname;
		
    	$module_name = $cm->name;
    	
		$section_no = $cm->get_course_module_record(true)->sectionnum;
		$sectionName = get_section_name($COURSE->id, $section_no);
		if ($sectionName != $previousSectionName) {
			$output_html .= "<h4>" . $sectionName . "</h4>";
		}
		
		// if resource is a folder
		if ($cmtype === 'folder') {

			$cmfiles = $fs->get_area_files($cm->context->id, 'mod_folder', 'content', false, 'timemodified', false);
			$output_html .= "<span style='font-weight:bold'>Folder: " . $module_name . "</span></br>";

			// Loop through all files in a folder
			foreach ($cmfiles as $f) {
				if (isset($f) && ($f->get_mimetype() === 'application/pdf')) {
					$file_item = create_file_item($coursename, $courseshortname, $courseurl, $cm, $cmtype, $module_name, $sectionName, $f);
					$status = check_db_for_file($file_item, $f);
					$output_html .= ($status ? '&#9989 ' :  '&#10060 ') . $f->get_filename()."<BR>";
				}
			}
			
		} else if ($cmtype === 'resource') { // if resource is a file.
			
			// Get files in "file" resource 
			$files = $fs->get_area_files($cm->context->id, 'mod_resource', 'content', false, 'timemodified', false);
			
			// Loop through each file
			foreach ($files as $f) {
				if (isset($f) && ($f->get_mimetype() === 'application/pdf')) {
					$file_item = create_file_item($coursename, $courseshortname, $courseurl, $cm, $cmtype, $module_name, $sectionName, $f);
					$status = check_db_for_file($file_item, $f);
					$output_html .= ($status ? '&#9989 ' :  '&#10060 ') . $f->get_filename()."<BR>";
				}
			}
		}	
		
		$previousSectionName = $sectionName;	
	}
				

    if ($this->content !== null) {
      return $this->content ;
    }
 
 
    $this->content         =  new stdClass;
    $this->content->text   = 'Status of each PDF file in the course: <BR>' . $output_html;
    
    //$this->content->footer = 'Footer here...';
 
    return $this->content;
  }
}   // Here's the closing bracket for the class definition


// From https://github.com/omer-ilhan/moodle-block_files/blob/master/block_files.php
/**
     * Create a file item.
     *
     * @param $coursename string name of the course this file item belongs to
     * @param $courseshortname string short name of the course this file item belongs to
     * @param $courseurl url of the course this file item belongs to
     * @param $cm course module this file item belongs to
     * @param $cmfile the actual file
     * @return array describing a file item, used to create table items
     */
     
 function create_file_item($coursename, $courseshortname, $courseurl, $cm, $cmtype, $mod_name, $sectionName, $cmfile) {
	return array(
		'mod_id' => $cm->id,
		'mod_name' => $mod_name,
		//'iconurl' => $cm->get_icon_url(),
		//'fileurl' => $cm->context->get_url(),
		'filename' => $cm->get_formatted_name(),
		//'courseurl' => $courseurl,
		'coursename' => $coursename,
		'courseshortname' => $courseshortname,
		'sectionName' => $sectionName,
		'timemodified' => $cmfile->get_timemodified(),
		'filename' => $cmfile->get_filename(),
		'contenthash' => $cmfile->get_contenthash(),
		'filepath' => $cmfile->get_filepath()
	);
}






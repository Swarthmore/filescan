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
	$file_listing = [];
	
	$cms = $cmid === null ? get_fast_modinfo($COURSE)->get_cms() : array(get_fast_modinfo($COURSE)->get_cm($cmid));
    $fileitems = array();

	foreach ($cms as $cm) {
		if ($cm->is_user_access_restricted_by_capability()) {
			continue;
		}
		$cmtype = $cm->modname;
		
    	$module_name = $cm->name ."<BR>";
		
		
		// if resource is a folder
		if ($cmtype === 'folder') {

			$cmfiles = $fs->get_area_files($cm->context->id, 'mod_folder', 'content', false, 'timemodified', false);


			// Loop through all files in a folder
			foreach ($cmfiles as $f) {
				if (isset($f) && ($f->get_mimetype() === 'application/pdf')) {
					$filenames .= "<BR>" . $f->get_filename();
					$file_item = create_file_item($coursename, $courseshortname, $courseurl, $cm, $cmtype, $module_name, $f);
					//echo (json_encode($file_item)."<BR><BR>");
					array_push($file_listing, $file_item);
				}
			}
			
		} else if ($cmtype === 'resource') { // if resource is a file.
			
			// Get files in "file" resource 
			$files = $fs->get_area_files($cm->context->id, 'mod_resource', 'content', false, 'timemodified', false);
			
			// Loop through each file
			foreach ($files as $f) {
				if (isset($f) && ($f->get_mimetype() === 'application/pdf')) {
					//print_r($f);
					$filenames .= "<BR>" . $cm->get_formatted_name() . " (" . $f->get_filename() . ")";
					$file_item = create_file_item($coursename, $courseshortname, $courseurl, $cm, $cmtype, $module_name, $f);
					array_push($file_listing, $file_item);
					//echo (json_encode($file_item)."<BR><BR>");
					
					//$tempfile = tempnam('/Users/aruether/Downloads/test', 'pdf_');
					//$f->copy_content_to($tempfile);
					//echo "Temp file: " . $tempfile;
					//check_pdf($tempfile);
					//unlink($tempfile);

				}
			}
		}
		
			
	}
				

    if ($this->content !== null) {
      return $this->content ;//. "<BR>" . $coursename . "<BR>" . $courseshortname . "<BR>" . $courseurl . "<BR>" ;
    }
 
 
    $this->content         =  new stdClass;
    $this->content->text   = 'The content of our SimpleHTML block!' . "<BR>". $coursename . "<BR>" . $courseshortname . "<BR>" . $courseurl . "<BR>" .$filenames . "<hr>" . json_encode($file_listing);
    
    $this->content->footer = 'Footer here...';
 
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
     
 function create_file_item($coursename, $courseshortname, $courseurl, $cm, $cmtype, $mod_name, $cmfile) {
	return array(
		'mod_id' => $cm->id,
		'mod_name' => $mod_name,
		//'iconurl' => $cm->get_icon_url(),
		//'fileurl' => $cm->context->get_url(),
		'filename' => $cm->get_formatted_name(),
		//'courseurl' => $courseurl,
		'coursename' => $coursename,
		'courseshortname' => $courseshortname,
		'timemodified' => $cmfile->get_timemodified(),
		'filename' => $cmfile->get_filename(),
		'contenthash' => $cmfile->get_contenthash()
	);
}



function check_pdf($filepath) {

	//$filepath = "/Users/aruether/Downloads/test_pdfs/sample_pdf_text.pdf";

	echo $filepath; 

	$target_url = 'http://127.0.0.1:8080';

	
   $ch = curl_init($target_url); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $args['file'] = new CurlFile($filepath,'application/pdf',"test.pdf");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $args); 
    $result = curl_exec($ch);
    echo $result; 
    curl_close ($ch);      
}





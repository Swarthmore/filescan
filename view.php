<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

 
require_once('../../config.php');
global $DB;
 
// Check for all required variables.
$courseid = required_param('courseid', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_filescan', $courseid);
}
 
 
require_login($course);
$PAGE->set_url('/block/filescan/view.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('viewdetailspage', 'block_filescan'));

global $COURSE, $CFG, $DB, $OUTPUT, $PAGE;


require_once($CFG->dirroot . '/course/lib.php');
$fs = get_file_storage();
$context = context_course::instance($COURSE->id);
$canview = has_capability('block/filescan:viewpages', $context);


// If the role can't view the block, return an empty string for the content
// and exit (blocks with no content aren't shown)
if 	(!$canview) {
	echo "Not allowed to view page";
	return;
}
 		
// Clear any existing cache data for this course 
$cache = cache::make('block_filescan', 'filescan');
 $filescan_cache = $cache->delete($COURSE->id); 		
 		
 		
// Determine course metadata
$coursename = $COURSE->fullname;
$courseshortname = $COURSE->shortname;
$courseurl = course_get_url($COURSE);

$filenames = "";
$output_html = "";
$file_listing = [];
$cmid = null;



// Get any existing data from db
//$db_data = $DB->get_record('block_pdfcheck', array('blockid' => $blockid));

// If record doesn't exist, initialize the data array
//if (!isset($db_data->id)) {$db_data= new stdClass;}

$cms = ($cmid === null) ? get_fast_modinfo($COURSE)->get_cms() : array(get_fast_modinfo($COURSE)->get_cm($cmid));


$file_list = array();	// Array containing detailed file info

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
				$file_item = create_file_item($DB, $cm, $cmtype, $module_name, $sectionName, $section_no, $f);
				array_push($file_list, $file_item);					
			}
		}
		
	} else if ($cmtype === 'resource') { // if resource is a file.
		
		// Get files in "file" resource 
		$files = $fs->get_area_files($cm->context->id, 'mod_resource', 'content', false, 'timemodified', false);
		
		// Loop through each file
		foreach ($files as $f) {
			if (isset($f) && ($f->get_mimetype() === 'application/pdf')) {
				//$status = check_db_for_file($f->get_contenthash(), $f);
				$file_item = create_file_item($DB, $cm, $cmtype, $module_name, $sectionName, $section_no, $f);
				array_push($file_list, $file_item);						
			}
		}
	}	
}


// Get status for all fileitems
//batchGetFileStatus($file_list);


		
// Loop through PDF listing to format the output into a table
$previous_section_number = "";
foreach ($file_list as $f) {
	if ($f['sectionNumber'] != $previous_section_number) {
		if ($f['sectionNumber']  > 1) {$output_html .= "</table>";}	// Complete previous section's table
		$output_html .= "<h4>" . $f['sectionName'] . "</h4>";
		$output_html .= "<table><tr><th style='text-align:center;width:50px;'>Mod</th><th style='width:300px;'>Filename</th><th>OCR Status</th></tr>";
	}

	switch($f['status']['ocr']) {
		case "pending":
			$ocr_status = "&#10024";
			break;
		case "error":
			$ocr_status = "&#10071";
			break;			
		case "pass":
			$ocr_status = "&#9989";
			break;			
		case "fail":
			$ocr_status = "&#10060";
			break;			
		case "processing":
			$ocr_status = "&#9889";
			break;
		default:
			$ocr_status = "&#10067";	// Unknown
	}
					
	// Check to see if this is a folder
	if ($f['mod_type'] == 'folder') {
		$mod_link = "<a href=\"" . $f['mod_url'] . "\" title=\"" . $f['mod_name'] . "\">&#128194;</a>" ;
	} else {
		$mod_link = "<a href=\"" . $f['mod_url'] . "\" title=\"" . $f['mod_name'] . "\">&#128196;</a>" ;
	}				

	$output_html .= "<tr><td style='text-align:center'>" . $mod_link ."<td><a href=\"" . $f['fileurl']  . "\">" . $f['filename'] . "</a></td><td style='text-align:center'><span title=\"" . $f['status']['ocr'] ."\">" . $ocr_status . "</span></td></tr>";
	$previous_section_number = $f['sectionNumber'];
}
$output_html .= "</table>"; // Close out last table on last section




// Calculate summary information

$processing = false;	// Assume no files are still processing.

$ocr_summary = array('pending' => 0, 'pass' => 0, 'fail' => 0, 'error' => 0, 'unknown' => 0);
foreach ($file_list as $f) {
	switch($f['status']['ocr']) {
		case "pending":
			$ocr_summary['pending']++;
			break;
		case "error":
			$ocr_summary['error']++;
			break;			
		case "pass":
			$ocr_summary['pass']++;
			break;			
		case "fail":
			$ocr_summary['fail']++;
			break;			
		default:
			$ocr_summary['unknown']++;
	}
}

// Check to see if there are any files still processing and update status flag as needed
if ($ocr_summary['pending'] > 0) {
	$processing = true;
}

$ocr_summary_array = array();
foreach (array_keys($ocr_summary) as $s) {
	if ($ocr_summary[$s] > 0) {
		array_push($ocr_summary_array, $ocr_summary[$s] . " " . $s);
	}
}
$ocr_summary_text = "OCR: " . join( ", ", $ocr_summary_array) . ".";

$summary_text = count($file_list) . " total files.  ";
$summary_text .= $ocr_summary_text;
$summary_text .= "<BR>Last updated: " .userdate(time(), '%m/%d/%Y %r'); 
// Prepend summary to top of content
$output_html = "<h1>Summary</h1>" . $summary_text . "<BR><BR>" . $output_html;
			

echo $OUTPUT->header();
echo $output_html;
echo $OUTPUT->footer();


/*
	// Save data to database
	$db_data->scanresults = $summary_text;
	$db_data->processing = $processing;
	$db_data->blockid = $blockid;
	
	if (isset($db_data->id)) {
		if (!$DB->update_record('block_pdfcheck', $db_data)) {
			print_error('updateerror', 'block_pdfcheck');
		}
	} else {
		if (!$DB->insert_record('block_pdfcheck', $db_data)) {
			print_error('inserterror', 'block_pdfcheck');
		}		
	}
*/



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
     
 function create_file_item($DB, $cm, $cmtype, $mod_name, $sectionName, $section_no, $file) {
 
 	// See if there is an file scan entry for this file
 	
		$results = $DB->get_record("block_filescan_files", array('contenthash'=>$file->get_contenthash()), $fields='*');
 	 	
 	 	// Assume no status -- correct if it exists
 	 	
 		if ( $results){
 			$status = array("ocr"=>($results->ocrstatus));
 		} else {
 			$status = null;
 		}
 
	return array(
		'mod_id' => $cm->id,
		'mod_type' => $cmtype,
		'mod_name' => $mod_name,
		'mod_url' =>  $cm->url,
		'filename' => $cm->get_formatted_name(),
		'fileurl' => moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename()),  //$cm->context->get_url(),
		'sectionName' => $sectionName,
		'sectionNumber' => $section_no, 
		'timemodified' => $file->get_timemodified(),
		'filename' => $file->get_filename(),
		'contenthash' => $file->get_contenthash(),
		'filepath' => $file->get_filepath(),
		'status' => $status,
		'file' => $file
	);
}



?>
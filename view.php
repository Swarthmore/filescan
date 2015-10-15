<?php

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);

 
require_once('../../config.php');
require 'upload-files-to-s3.php';
global $DB;
 
// Check for all required variables.
$courseid = required_param('courseid', PARAM_INT);
$blockid = required_param('blockid', PARAM_INT);

if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_pdfcheck', $courseid);
}
 
 
require_login($course);
$PAGE->set_url('/blocks/pdfcheck/view.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('viewdetailspage', 'block_pdfcheck'));

global $COURSE, $CFG, $DB, $OUTPUT, $PAGE;


require_once($CFG->dirroot . '/course/lib.php');
$fs = get_file_storage();
$context = context_course::instance($COURSE->id);
$canview = has_capability('block/pdfcheck:viewpages', $context);


// If the role can't view the block, return an empty string for the content
// and exit (blocks with no content aren't shown)
if 	(!$canview) {
	echo "Not allowed to view page";
	return;
}
 		
// Determine course metadata
$coursename = $COURSE->fullname;
$courseshortname = $COURSE->shortname;
$courseurl = course_get_url($COURSE);

$filenames = "";
$output_html = "";
$file_listing = [];
$cmid = null;



// Start up AWS services -- for getting info about files and doing conversions	
init_aws_services();


// Get any existing data from db
$db_data = $DB->get_record('block_pdfcheck', array('blockid' => $blockid));

// If record doesn't exist, initialize the data array
if (!isset($db_data->id)) {$db_data= new stdClass;}

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
				$status = check_db_for_file($f->get_contenthash(), $f);
				$file_item = create_file_item($cm, $cmtype, $module_name, $sectionName, $section_no, $f, $status);
				array_push($file_list, $file_item);					
			}
		}
		
	} else if ($cmtype === 'resource') { // if resource is a file.
		
		// Get files in "file" resource 
		$files = $fs->get_area_files($cm->context->id, 'mod_resource', 'content', false, 'timemodified', false);
		
		// Loop through each file
		foreach ($files as $f) {
			if (isset($f) && ($f->get_mimetype() === 'application/pdf')) {
				$status = check_db_for_file($f->get_contenthash(), $f);
				$file_item = create_file_item($cm, $cmtype, $module_name, $sectionName, $section_no, $f, $status);
				array_push($file_list, $file_item);						
			}
		}
	}	
}
	//$output_html .= "</table>"; // Close out last table on last section
		
// Loop through PDF listing to format the output into a table
$previous_section_number = "";
foreach ($file_list as $f) {
	if ($f['sectionNumber'] != $previous_section_number) {
		if ($f['sectionNumber']  > 1) {$output_html .= "</table>";}	// Complete previous section's table
		$output_html .= "<h4>" . $f['sectionName'] . "</h4>";
		$output_html .= "<table><tr><th style='width:300px;'>Filename</th><th>OCR Status</th></tr>";
	}
	
	switch($f['status']['ocr']) {
		case "pending":
			$ocr_status = "&#10024";
			break;
		case "error":
			$ocr_status = "&#10071";
			break;			
		case "text":
			$ocr_status = "&#9989";
			break;			
		case "notext":
			$ocr_status = "&#10060";
			break;			
		default:
			$ocr_status = "&#10067";	
	}
						

	$output_html .= "<tr><td>" . $f['filename'] . "</td><td style='text-align:center'>" . $ocr_status . "</td></tr>";
	$previous_section_number = $f['sectionNumber'];
}
$output_html .= "</table>"; // Close out last table on last section

//$output_html .= "<tr><th colspan=2 style='text-align:left'>Folder: " . $module_name . "</th></tr>";
			

echo $OUTPUT->header();
print_r($file_list);
echo $output_html;
echo $OUTPUT->footer();



	// Save data to database
	$db_data->scanresults = "Not yet scanned";
	$db_data->processing = true;
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
     
 function create_file_item($cm, $cmtype, $mod_name, $sectionName, $section_no, $cmfile, $status) {
	return array(
		'mod_id' => $cm->id,
		'mod_name' => $mod_name,
		'filename' => $cm->get_formatted_name(),
		'sectionName' => $sectionName,
		'sectionNumber' => $section_no, 
		'timemodified' => $cmfile->get_timemodified(),
		'filename' => $cmfile->get_filename(),
		'contenthash' => $cmfile->get_contenthash(),
		'filepath' => $cmfile->get_filepath(),
		'status' => $status
	);
}



?>
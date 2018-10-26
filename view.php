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
	echo get_string('cannot_view', 'block_filescan');
	return;
}

// Clear any existing cache data for this course 
$cache 						= cache::make('block_filescan', 'filescan');
$filescan_cache 	= $cache->delete($COURSE->id);

// Determine course metadata
$coursename 			= $COURSE->fullname;
$courseshortname 	= $COURSE->shortname;
$courseurl 				= course_get_url($COURSE);

$filenames 			= "";
$output_html	  = "";
$file_listing   = [];
$cmid 				  = null;

// Get any existing data from db
$cms = ($cmid === null) ? get_fast_modinfo($COURSE)->get_cms() : array(get_fast_modinfo($COURSE)->get_cm($cmid));

$file_list = array();	// Array containing detailed file info

// Loop through each module in the course looking for files
foreach ($cms as $cm) {
	
	if ($cm->is_user_access_restricted_by_capability()) {
		continue;
	}
	
	$cmtype 			= $cm->modname;
	$module_name 	= $cm->name;
	
	$section_no 	= $cm->get_course_module_record(true)->sectionnum;
	$sectionName 	= get_section_name($COURSE->id, $section_no);
	
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

// Loop through PDF listing to format the output into a table

$success_icon 				= '<i class="fa fa-check text-success fa-fw" aria-hidden="true"></i>';
$unknown_icon 				= '<i class="fa fa-question text-info fa-fw" aria-hidden="true"></i>';
$partial_success_icon 		= '<i class="fa fa-exclamation text-warning fa-fw" aria-hidden="true"></i>';
$fail_icon 					= '<i class="fa fa-times text-danger fa-fw" aria-hidden="true"></i>';

$previous_section_number = "";

$output_html .= "<table class='filescan-details table table-striped table-condensed'><tbody>";

function get_help_icon($header, $link, $title) {
	global $OUTPUT;
	$o  = "<a href='$link' title='$title' aria-label='$header: $title' target='_blank'>";
	$o .= "<img class='icon iconhelp' alt='' aria-hidden='true' src='" . $OUTPUT->image_url('help') . "'></a>";
	return $o;
}

function get_table_header($id) {
	// Params.
	$header = get_string("table:{$id}_header", 'block_filescan');
	$link 	= get_config('block_filescan', $id . '_help');
	$title 	= get_string('helptitle', 'block_filescan');

	// Output.
	$o = "<th class='fs-table-header fs-table-header-$id'>$header";
	if (!empty($link)) {
		$o .= get_help_icon($header, $link, $title);
	}
	$o .= '</th>';
	return $o;
}

foreach ($file_list as $f) {
	if ($f['sectionNumber'] != $previous_section_number) {
		$output_html .= "<tr><td colspan='7' style='background-color: transparent;border:none;'><h4>" . $f['sectionName'] . "</h4></td></tr>";
		$output_html .= "<tr><th style=''>" . get_string('table:mod_header', 'block_filescan') . '</th>';
		$output_html .= '<th>' . get_string('table:filename_header', 'block_filescan') . '</th>';
		$output_html .= '<th>' . get_string('table:status_header', 'block_filescan') . '</th>';
		$output_html .= get_table_header('text_check');
		$output_html .= get_table_header('title_check');
		$output_html .= get_table_header('lang_check');
		$output_html .= get_table_header('outline_check');
		$output_html .= "</tr>";
	}
	
	switch($f['status']['hastext']) {
		case -1:
			$hastext = "&#10071";
			break;
		case "1":
			$hastext = $success_icon;		// Yes
			break;
		case "0":
			$hastext = $fail_icon; 	// No
			break;
		default:
			$hastext = $unknown_icon;	// Unknown
	}
	
	switch($f['status']['hastitle']) {
		case -1:
			$hastitle = "&#10071";
			break;
		case "1":
			$hastitle = $success_icon;		// Yes
			break;
		case "0":
			$hastitle = $fail_icon; 	// No
			break;
		default:
			$hastitle = $unknown_icon;	// Unknown
	}
	
	switch($f['status']['haslanguage']) {
		case -1:
			$haslanguage = "&#10071";
			break;
		case "1":
			$haslanguage = $success_icon;		// Yes
			break;
		case "0":
			$haslanguage = $fail_icon; 	// No
			break;
		default:
			$haslanguage = $unknown_icon;	// Unknown
	}		
	
	switch($f['status']['hasoutline']) {
		case -1:
			$hasoutline = "&#10071";
			break;
		case "1":
			$hasoutline = $success_icon;		// Yes
			break;
		case "0":
			$hasoutline = $fail_icon; 	// No
			break;
		default:
			$hasoutline = $unknown_icon;	// Unknown
	}	
	
	# Determine overall status
	switch($f['status']['status']) {
		case "fail":
			$overallstatus = $fail_icon; 	// Bad
			break;
		case "pass":
			$overallstatus = $success_icon;		// Good
			break;
		case "check":
			$overallstatus = $partial_success_icon; 	// check
			break;
		default:
			$overallstatus = $unknown_icon;	// Unknown
	}	
	
	
	
	// Check to see if this is a folder
	if ($f['mod_type'] == 'folder') {
		$mod_link = "<a href=\"" . $f['mod_url'] . "\" title=\"" . $f['mod_name'] . "\">&#128194;</a>" ;
	} else {
		$mod_link = "<a href=\"" . $f['mod_url'] . "\" title=\"" . $f['mod_name'] . "\">&#128196;</a>" ;
	}				
	
	$output_html .= "<tr><td style='text-align:center'>" . $mod_link ."<td><a href=\"" . $f['fileurl']  . "\" target='_blank'>" . $f['filename'] . "</a></td>
	<td style='text-align:center;'><span title=\"" . get_string('table:status_header', 'block_filescan') . '">' . $overallstatus . "</span></td>
	<td style='text-align:center;border-left-width:2px;'><span title=\"" . get_string('table:text_check_header', 'block_filescan') . '">' . $hastext . "</span></td>
	<td style='text-align:center'><span title=\"" . get_string('table:title_check_header', 'block_filescan') . '">' . $hastitle . "</span></td>
	<td style='text-align:center'><span title=\"" . get_string('table:lang_check_header', 'block_filescan') . '">' . $haslanguage . "</span></td>
	<td style='text-align:center'><span title=\"" . get_string('table:outline_check_header', 'block_filescan') . '">' . $hasoutline . "</span></td>
	</tr>";
	
	$previous_section_number = $f['sectionNumber'];
}
$output_html .= "</tbody></table>"; // Close out last table on last section

// Calculate summary information
$processing = false;	// Assume no files are still processing.

$ocr_summary = array ('Accessible' => 0, 'Partially Accessible' => 0, 'Inaccessible' => 0, 'Pending' => 0, 'Error' => 0, 'Unknown' => 0);
foreach ($file_list as $f) {
	switch($f['status']['status']) {
		case "pending":
		$ocr_summary['Pending']++;
		break;
		case "error":
		$ocr_summary['Error']++;
		break;			
		case "pass":
		$ocr_summary['Accessible']++;
		break;			
		case "fail":
		$ocr_summary['Inaccessible']++;
		break;	
		case "check":
		$ocr_summary['Partially Accessible']++;
		break;							
		default:
		$ocr_summary['Unknown']++;
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

$summary_text = count($file_list) . " total PDF files";


if ($ocr_summary['Accessible'] > 0) {
	$summary_text .= '<BR>' . $success_icon . get_string('summary:files_accessible', 'block_filescan', $ocr_summary['Accessible']);
}

if ($ocr_summary['Partially Accessible'] > 0) {
	$summary_text .= '<BR>' . $partial_success_icon . get_string('summary:files_partially_accessible', 'block_filescan', $ocr_summary['Partially Accessible']);
}	

if ($ocr_summary['Inaccessible'] > 0) {
	$summary_text .= '<BR>' . $fail_icon . get_string('summary:files_inaccessible', 'block_filescan', $ocr_summary['Inaccessible']);
}

if ($ocr_summary['Unknown'] > 0) {
	$summary_text .= '<BR>' . $unknown_icon . get_string('summary:files_accessibility_unknown', 'block_filescan', $ocr_summary['Unknown']);
}


// Prepend summary to top of content
$output_html = "<h1>" . get_string('summary:title', 'block_filescan') . "</h1>" . $summary_text . "<BR><BR>" . $output_html;

echo $OUTPUT->header();
echo $output_html;
echo $OUTPUT->footer();

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
		$status = array(
			"checked"=>($results->checked),
			"status"=>($results->status),
			"hastext"=>($results->hastext),
			"hastitle"=>($results->hastitle),
			"haslanguage"=>($results->haslanguage),
			"hasoutline"=>($results->hasoutline),
			"pagecount"=>($results->pagecount)
		);
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

<?php
/**
 * Created by PhpStorm.
 * User: tony
 * Date: 9/21/18
 * Time: 10:22 AM
 */

global $DB;
global $PAGE;
global $OUTPUT;

// Set some page variables
$PAGE->set_url('/block/filescan/admin.php', null);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading('Admin View');

$fs = get_file_storage();
$context = null;
$canview = has_capability('block/filescan:viewpages', $context);

// Check if the role can view
if (!$canview) {
  echo 'Cannot view the materials';
  return;
}
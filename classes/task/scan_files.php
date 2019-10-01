<?php

// Per https://docs.moodle.org/dev/Task_API#Failures
// A task, either scheduled or adhoc can sometimes fail. An example would be updating an RSS field when the network
// is temporarily down. This is handled by the task system automatically - all the failing task needs to do is throw
// an exception. The task will be retried after 1 minute. If the task keeps failing, the retry algorithm will add
// more time between each successive attempts up to a max of 24 hours.
//
// Will throw exceptions when fatal errors occur

namespace block_filescan\task;

class scan_files extends \core\task\scheduled_task
{

  public function get_name()
  {
    // Shown in admin screens
    return get_string('filescan_task', 'block_filescan');
  }


  private static function save_filescan_results($DB, $fileentry)
  {

    $record = $DB->get_record("block_filescan_files", array('contenthash' => $fileentry->contenthash));

    if ($record) {
      $fileentry->id = $record->id;

      // If this is a failure, update the statuscode
      if ($fileentry->checked == 0) {
        $fileentry->statuscode = $record->statuscode + 1;
      }
      $sql = $DB->update_record("block_filescan_files", $fileentry);
      $sql ? mtrace("Updated record for " . $fileentry->contenthash) : mtrace("Could not update record " . $fileentry->contenthash);

    } else {
      if ($fileentry->checked == 0) {
        $fileentry->statuscode = 1;
      }

      $sql = $DB->insert_record("block_filescan_files", $fileentry, $returnid = true, $bulk = false);
      $sql ? mtrace("Inserted record") : mtrace("Could not insert record");
    }
  }

  public function execute()
  {
    global $CFG, $DB;
    require_once($CFG->libdir . '/filelib.php');

    mtrace("Filescan cron script is running");

    $api_url = get_config('block_filescan', 'apiurl');

    // Only proceed if config parameters are defined
    if (empty($api_url)) {
      throw new \RuntimeException("Moodle FileScan API URL not configured");
    }

    mtrace("Looking up files to scan");

    $max_files_to_check = (int)get_config('block_filescan', 'numfilespercron');
    $max_files_to_check = (is_int($max_files_to_check) && $max_files_to_check > 0) ? $max_files_to_check : 2;

    $max_retries = (int)get_config('block_filescan', 'maxretries');

    // Find PDF files in course materials (not student files, stamps, etc) that haven't already been scanned
    // Have to do 2 lookups because there can be multiple entries for each contenthash and need to ensure we get the latest updated contenthashes
    $query = "SELECT DISTINCT f.id, f.contenthash
                FROM {files} f, {context} c
               WHERE c.id = f.contextid
                 AND c.contextlevel = 70
                 AND f.filesize <> 0
                 AND f.mimetype = 'application/pdf'
                 AND f.component <> 'assignfeedback_editpdf'
                 AND f.filearea <> 'stamps'
               ORDER BY f.id DESC
               LIMIT $max_files_to_check";

//    $query = "SELECT DISTINCT f.id, f.contenthash
//                FROM {files} f, {context} c
//               WHERE c.id = f.contextid
//                 AND c.contextlevel = 70
//                 AND f.filesize <> 0
//                 AND f.mimetype = 'application/pdf'
//                 AND f.component <> 'assignfeedback_editpdf'
//                 AND f.filearea <> 'stamps'
//                 AND f.contenthash NOT IN
//                     (SELECT contenthash
//                        FROM {block_filescan_files}
//                       WHERE checked = 1
//                          OR (checked = 0 AND status = 'error' AND statuscode >= $max_retries))
//               ORDER BY f.id DESC
//               LIMIT $max_files_to_check";

    $contenthashes = $DB->get_records_sql($query);

    if (!$contenthashes) {
      mtrace("No files found");
      return false;
    }

    // Set up an array containing the latest contenthashes
    $hash_array = array();
    foreach ($contenthashes as $c) {
      $hash_array[] = $c->contenthash;
    }
    $comma_separated_contenthashes = implode("','", $hash_array);
    $comma_separated_contenthashes = "'" . $comma_separated_contenthashes . "'";

    $query = 'SELECT f.contenthash, f.pathnamehash FROM {files} f WHERE f.contenthash IN (' . $comma_separated_contenthashes . ') GROUP BY f.contenthash, f.pathnamehash';

    $files = $DB->get_records_sql($query);

    if (!$files) {
      mtrace("No files found");
      return false;
    }

    // Get the file storage , we will need this for getting the files by their hash
    $fs = get_file_storage();

    // output the files
    foreach($files as $f) {
      $file = $fs->get_file_by_hash($f->pathnamehash);
      $file_contents = $file->get_content();
      $file_content_hash = $f->contenthash;

      // If there are no file contents, save the results to the db here and break out of the
      // current iteration
      if (!$file_contents) {
        $row = new \stdClass();
        $row->timechecked = date("Y-m-d H:i:s");
        $row->contenthash = $file_content_hash;
        $row->status = "error";
        $row->checked = 0;
        self::save_filescan_results($DB, $row);
        continue;
      }

      // Construct the request
      $opts = array();
      $headers = array(
        "cache-control: no-cache",
        "Content-Type: multipart/form-data"
      );

      // See the undocumented curl docs in the moodle source code
      // https://github.com/moodle/moodle/blob/master/lib/filelib.php#L2972

      $request = new \curl($opts);

      // Make the request and get the response
      $response = $request->post(
        $api_url . "/v1/file",
        array(
          "upfile" => $file,
          "id" => $f->contenthash
        ),
        array(
          "CURLOPT_HEADER" => $headers,
          "CURLOPT_TIMEOUT" => 10000,
          "CURLOPT_FRESH_CONNECT" => true,
          "CURLOPT_FOLLOWLOCATION" => true,
          "CURLOPT_SSL_VERIFYPEER" => false
        )
      );

      $request_info = $request->get_info();

      // Create the row object here and assign the contenthash to it
      $row = new \stdClass();
      $row->contenthash = $file_content_hash;

      // If we don't get a 200 response, output the error to moodle via mtrace and
      // mark the row with an error. Save the results and exit out of the current loop
      if ($request_info['http_code'] != 200) {

        mtrace("Did not receive 200 status from filescan server when requesting scan for " . $row->contenthash);
        mtrace(var_dump($request_info));

        // Update the row with an error status and break out of the loop
        $row->status = "error";
        $row->checked = 0;
        self::save_filescan_results($DB, $row);
        continue;
      }

      // Decode the response into an array of arrays
      $results  = json_decode($response);

      // Handle the results
      $row->timechecked = date("Y-m-d H:i:s");
      $row->hastext = $results["application/json"]["hasText"];
      $row->hasTitle = $results["application/json"]["title"];
      $row->hasLanguage = $results["application/json"]["language"];
      $row->hasOutline = $results["application/json"]["hasOutline"];
      $row->checked = 1;
      $row->pagecount = $results["application/json"]["numPages"];

      // Determine if the file is accessible or not
      // no text -> fail
      // has text, title, language, and outline -> pass 🔥🔥🔥
      // has text but is missing other -> check
      $row->status = ($row->hasTitle && $row->hasLanguage && $row->hasOutline)
        ? ($row->hasText ? "pass" : "fail")
        : "check" ;

      // Update the database with the results
      self::save_filescan_results($DB, $row);

    }
  }

}

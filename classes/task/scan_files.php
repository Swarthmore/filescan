<?php

namespace block_filescan\task;
require(__DIR__ . '/../../vendor/autoload.php');

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

/* Per https://docs.moodle.org/dev/Task_API#Failures
	A task, either scheduled or adhoc can sometimes fail. An example would be updating an RSS field when the network
	is temporarily down. This is handled by the task system automatically - all the failing task needs to do is throw
	an exception. The task will be retried after 1 minute. If the task keeps failing, the retry algorithm will add
	more time between each successive attempts up to a max of 24 hours.

	Will throw exceptions when fatal errors occur
*/

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
      $sql ? mtrace("Updated record") : mtrace("Could not update record");

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

    //$DB->set_debug(true);

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
                 AND f.contenthash NOT IN
                     (SELECT contenthash
                        FROM {block_filescan_files}
                       WHERE checked = 1
                          OR (checked = 0 AND status = 'error' AND statuscode >= $max_retries))
               ORDER BY f.id DESC
               LIMIT $max_files_to_check";

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


    $query = 'SELECT f.contenthash, f.pathnamehash
                FROM {files} f
               WHERE f.contenthash IN (' . $comma_separated_contenthashes . ')
               GROUP BY f.contenthash, f.pathnamehash';

    $files = $DB->get_records_sql($query);


    if (!$files) {
      mtrace("No files found");
      return false;
    }

    $client = new Client([
      // Base URI is used with relative requests
      'base_uri' => $api_url,
      // You can set any number of default request options.
      'timeout' => 50.0,
    ]);


    // Add each concurrent request to an array
    $promises = array();
    $fs = get_file_storage();

    foreach ($files as $f) {
      mtrace("Found file: " . $f->contenthash);
      $file = $fs->get_file_by_hash($f->pathnamehash);

      $file_contents = $file->get_content();

      if ($file_contents === FALSE) {
        // Cannot get file contents -- report an error
        $fileentry = new \stdClass();
        $fileentry->timechecked = date("Y-m-d H:i:s");
        $fileentry->contenthash = $f->contenthash;
        $fileentry->status = "error";
        $fileentry->checked = 0;
        self::save_filescan_results($DB, $fileentry);
        continue;
      }


      // Make an async POST request
      $promises[$f->contenthash] = $client->postAsync('/v1/file', [
        'multipart' => [
          [
            'name' => 'upfile',
            'contents' => $file_contents,
            'filename' => $f->contenthash,
          ],
          [
            'name' => 'id',
            'contents' => $f->contenthash
          ]
        ]
      ]);

    }


    // Wait for the requests to complete, even if some of them fail
    $results = Promise\settle($promises)->wait();


    // Handle each the results.  They $key is the contenthash.
    foreach ($results as $key => $r) {

      $fileentry = new \stdClass();
      $fileentry->timechecked = date("Y-m-d H:i:s");
      $fileentry->contenthash = $key;


      if ($r['state'] == 'fulfilled') {

        $response = $r['value'];

        $response_code = $response->getStatusCode();
        $result = json_decode($response->getBody(), true);

        // Assume an unknown status -- correct as needed
        // TODO: implement an incomplete entry if the conversion fails
        if (array_key_exists('filename', $result['application/json'])) {
          mtrace("Saving results for " . $result['application/json']['filename']);
        } else {
          mtrace(print_r($r));
        }

        if ($response_code == 200) {

          if ($result['application/json']['hasText']) {
            $fileentry->hastext = 1;
          } else {
            $fileentry->hastext = 0;
          }

          if ($result['application/json']['title']) {
            $fileentry->hastitle = 1;
          } else {
            $fileentry->hastitle = 0;
          }

          if ($result['application/json']['language']) {
            $fileentry->haslanguage = 1;
          } else {
            $fileentry->haslanguage = 0;
          }

          if ($result['application/json']['hasOutline']) {
            $fileentry->hasoutline = 1;
          } else {
            $fileentry->hasoutline = 0;
          }

          # Determine overall status based on parameters above
          # no text ==> fail
          # has text, title, language, outline ==> pass
          # has text but missing at least one of title, language, outline ==> check
          if ($fileentry->hastext == 0) {
            $fileentry->status = "fail";
          } elseif (($fileentry->hastitle == 1) && ($fileentry->haslanguage == 1) && ($fileentry->hasoutline == 1)) {
            $fileentry->status = "pass";
          } else {
            $fileentry->status = "check";
          }


          $fileentry->checked = 1;
          $fileentry->pagecount = $result['application/json']['numPages'];

        } else {
          $fileentry->status = "error";
          $fileentry->checked = 0;
        }


      } else if ($r['state'] == 'rejected') {
        // Not sure how to get the response based on the request.
        mtrace("Request rejectedi for " . $fileentry->contenthash);
        mtrace($r['reason']);
        $fileentry->status = "error";
        $fileentry->checked = 0;

      } else {
        mtrace("Unknown exceptioni for " . $fileentry->contenthash);
        $fileentry->status = "error";
        $fileentry->checked = 0;
      }

      // Update the database with the results
      self::save_filescan_results($DB, $fileentry);


    }

  }

}

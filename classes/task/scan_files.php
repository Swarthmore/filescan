<?php

namespace block_filescan\task;
require(__DIR__.'/../../vendor/autoload.php');


use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;



ini_set('display_errors', 'On');
error_reporting(E_ALL);


/* Per https://docs.moodle.org/dev/Task_API#Failures
	A task, either scheduled or adhoc can sometimes fail. An example would be updating an RSS field when the network 
	is temporarily down. This is handled by the task system automatically - all the failing task needs to do is throw 
	an exception. The task will be retried after 1 minute. If the task keeps failing, the retry algorithm will add 
	more time between each successive attempts up to a max of 24 hours.
	
	Will throw exceptions when fatal errors occur
*/



class scan_files extends \core\task\scheduled_task {



    public function get_name() {
        // Shown in admin screens
        return get_string('filescan_task', 'block_filescan');
    }


    public function execute() {

		global $CFG, $DB;
        require_once($CFG->libdir.'/filelib.php');
		
		//$DB->set_debug(true);
		
        mtrace( "Filescan cron script is running" );

        $api_url = get_config('filescan', 'apiurl');

		// Only proceed if config parameters are defined
		if (empty($api_url)) { throw new \RuntimeException("Moodle FileScan API URL not configured");}


		mtrace("Looking up files to scan"); 
    

		$max_files_to_check = (int)get_config('filescan', 'numfilespercron');
		$max_files_to_check = (is_int($max_files_to_check) && $max_files_to_check > 0) ? $max_files_to_check : 2;
        
        $query = "SELECT distinct f.contenthash, f.pathnamehash 
        		FROM {files} f, {context} c
        		WHERE c.id = f.contextid 
        			AND c.contextlevel = 70 
        			AND f.filesize <> 0 
        			AND f.mimetype = 'application/pdf' 
        			AND f.component != 'assignfeedback_editpdf' 
        			AND f.filearea != 'stamps'
        			AND f.contenthash NOT IN (SELECT contenthash FROM {block_filescan_files} where checked=True)
        			ORDER BY f.timemodified DESC
        			LIMIT " . $max_files_to_check
        		;
		
        $files = $DB->get_records_sql($query);
       
        if (!$files) {
        	mtrace("No files found");
            return false;
        }
    
    
		$client = new Client([
			// Base URI is used with relative requests
			'base_uri' => $api_url,
			// You can set any number of default request options.
			'timeout'  => 50.0,
		]);    
    
    
       	// Add each concurrent request to an array
		$promises = array();
		$fs = get_file_storage();
		
 		foreach ($files as $f) {
 			mtrace("Found file: " . $f->contenthash);
			$file = $fs->get_file_by_hash($f->pathnamehash);
		
			// Make an async POST request		
			$promises[$f->contenthash] = $client->postAsync('/v1/file',[
					'multipart' => [
						[
							'name'     => 'upfile',
							'contents' => $file->get_content(),
							'filename' => $f->contenthash,
						],
						[
							'name'     => 'id',
							'contents' => $f->contenthash
						]
					]
				]);
				
		}		


		// Wait for the requests to complete, even if some of them fail
		$results = Promise\settle($promises)->wait();
		
		
		
		foreach($results as $r) {	
			
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
				
				$fileentry = new \stdClass();					
				if ($response_code = 200) {
		
					$fileentry->contenthash = $result['application/json']['filename'];
							
					if ($result['application/json']['hasText']) {
						$fileentry->ocrstatus = "pass";
					} else {
						$fileentry->ocrstatus = "fail";
					}
							
					$fileentry->checked = 1;
					$fileentry->pagecount = $result['application/json']['pages'];
				
				} else {
					$fileentry->ocrstatus = "fail";
					$fileentry->checked = 0;
				}
			
		
				// Determine if there is already a record
				$record = $DB->get_record("block_filescan_files", array('contenthash'=>$result['application/json']['filename']));
				if ($record) {
					$fileentry->id = $record->id;
					$sql = $DB->update_record("block_filescan_files", $fileentry);
					$sql ? mtrace("Updated record") : mtrace("Could not update record");
				} else {
					$sql = $DB->insert_record("block_filescan_files", $fileentry, $returnid=true, $bulk=false);
					$sql ? mtrace("Inserted record") : mtrace("Could not insert record");
				}
				
			} else if ($r['state'] == 'rejected') {
				// Not sure how to get the response based on the request.
				mtrace("Request rejected");
				mtrace($r['reason']);
				
			} else {	
				mtrace("Unknown exception");	
			}
			
		}

 	}       

}
	


<?php

namespace block_filescan\task;
require(__DIR__.'/../../vendor/autoload.php');


use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Promise;



ini_set('display_errors', 'On');
error_reporting(E_ALL);


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
	/*
        $api_url = get_config('filescan', 'api_url');

		// Only proceed if config parameters are defined
		if (empty($api_url)) { mtrace("API key not defined");}
        if (empty($api_url)) {
        	return false;
        }
        */
        
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
			'base_uri' => get_config('filescan', 'apiurl'),
			// You can set any number of default request options.
			'timeout'  => 30.0,
		]);    
    
        
       
       	// Add each concurrent request to an array
		$promises = array();
		$fs = get_file_storage();
 		foreach ($files as $f) {
 			mtrace("Checking file: " . $f->contenthash);
			$file = $fs->get_file_by_hash($f->pathnamehash);
		
				
			// Make an async POST request		
			$promise = $client->postAsync('/v1/file',[
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
				
				
			// Process the results	
			$promise->then(
				function (ResponseInterface $res) {
					echo $f->contenthash . " ==> " . $res->getStatusCode() . "\n";
				},
				function (RequestException $e) {
					echo $f->contenthash . " ==> " . $e->getMessage() . "\n";
					echo $e->getRequest()->getMethod();
				}
			);	
			
				
		}		
		
/*		
		

		
		


		// Wait for the requests to complete, even if some of them fail
		$results = Promise\settle($promises)->wait();
    

		foreach($results as $r) {	
				
			$response = $r['value'];
			
			$response_code = $response->getStatusCode();
			
			// Assume an unknown status -- correct as needed
			// TODO: implement an incomplete entry if the conversion fails
			$fileentry = new \stdClass();					
			if ($response_code = 200) {
			
				$results = json_decode($response->getBody(), true);
				$fileentry->contenthash = $results['application/json']['filename'];
							
				if ($results['application/json']['hasText']) {
					$fileentry->ocrstatus = "pass";
				} else {
					$fileentry->ocrstatus = "fail";
				}
							
				$fileentry->checked = 1;
				$fileentry->pagecount = $results['application/json']['pages'];
				
			} else {
				$fileentry->ocrstatus = "fail";
				$fileentry->checked = 0;
			}
			
		
			// Determine if there is already a record
			$record = $DB->get_record("block_filescan_files", array('contenthash'=>$results['application/json']['filename']));
			if ($record) {
				$fileentry->id = $record->id;
				$sql = $DB->update_record("block_filescan_files", $fileentry);
				$sql ? mtrace("Updated record") : mtrace("Could not update record");
			} else {
				$sql = $DB->insert_record("block_filescan_files", $fileentry, $returnid=true, $bulk=false);
				$sql ? mtrace("Inserted record") : mtrace("Could not insert record");
			}
			
		}

 	}       


*/

	}
	
}

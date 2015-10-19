<?php

// Include the SDK using the Composer autoloader
require 'vendor/autoload.php';
$config = [];


function init_aws_services() {

	//echo "Starting AWS script\n";
	//echo "KEY: " . getenv('AWS_ACCESS_KEY_ID');

	global $config;

	// Use the us-west-2 region and latest version of each client.
	 $config['sharedConfig'] = [
		'region'  => 'us-east-1',
		'version' => 'latest'
	];

	// Create an SDK class used to share configuration across clients.
	$config['sdk'] = new Aws\Sdk( $config['sharedConfig']);

	//echo "Created SDK class\n";

	// Set up dynamodb
	$config['dynamodb'] = $config['sdk']->createDynamoDb();
	//echo "Created DynamoDB client\n";

	$config['dynamodb_batch_size'] = 100;	// Max batch operations for DynamoDB
	$config['dynamodb_batchwrite_size'] = 25;
	
	$config['tableName'] = 'moodle-files';



}






function check_db_for_file($contenthash, $file) {

	global $config;

	//echo "Looking up contenthash in database";
	
	// Lookup contenthash in db
	try {

		$response = $config['dynamodb']->getItem([
			'TableName' => $config['tableName'],
			'Key' => [
				'contenthash' => [ 'S' => $contenthash ],
			]
		]);

	} catch (DynamoDbException $e) {
		// Catch an DynamoDb specific exception.
		echo "DynamoDb Error: ";
		echo $e->getMessage();
	} catch (AwsException $e) {
		// This catches the more generic AwsException. You can grab information
		// from the exception using methods of the exception object.
		echo "AWS Error: ";
		echo $e->getAwsRequestId() . "\n";
		echo $e->getAwsErrorType() . "\n";
		echo $e->getAwsErrorCode() . "\n";
	}	
	

	// If file exists in db and has a complete status, return the results.  If not, 
	// upload the file to S3.
	
	if (isset($response['Item'])) {
		
		// Check to see if OCR status is known.  If so, report it.  If not, upload file to S3.
		$file_status = json_decode($response['Item']['status']['S']);
		
		if (isset($file_status->ocr)) {
			return array('ocr' => $file_status->ocr);
		} 		
	}

	write_filedata_to_db($contenthash);
	upload_file_to_s3($contenthash, $file);
	return array('ocr' => 'pending');	

}




function batchGetFileStatus(&$file_list) {

	global $config;

	// Get all contenthashes from the file list
	// Break into groups of 100 (AWS Dynamodb limit for batch read)
	
	$keys = array();
	$hashes = array();
	
	// Get list of contenthashes
	foreach ($file_list as $f) {
		array_push($hashes, $f['contenthash']);
	}

	// Given that $keyValues contains a list of your hash and range keys:
	//     array(array(<hash>, <range>), ...)
	// Build the array for the "Keys" parameter
	foreach (array_unique($hashes) as $h) {
		$keys[] = array('contenthash' => array( 'S' => $h));
	}

	// Chunk array into the size for DynamoDB batch operations
	$chunked_keys = array_chunk($keys, $config['dynamodb_batch_size']);
	

	foreach ($chunked_keys as $k) {
		
	
		// Lookup contenthash in db
		try {

			$response = $config['dynamodb']->batchGetItem( array(
				'RequestItems' => array(
					$config['tableName'] => array('Keys' => $k)
				)
			));
	
		} catch (DynamoDbException $e) {
			// Catch an DynamoDb specific exception.
			echo "DynamoDb Error: ";
			echo $e->getMessage();
		} catch (AwsException $e) {
			// This catches the more generic AwsException. You can grab information
			// from the exception using methods of the exception object.
			echo "AWS Error: ";
			echo $e->getAwsRequestId() . "\n";
			echo $e->getAwsErrorType() . "\n";
			echo $e->getAwsErrorCode() . "\n";
		}	

		
		// Loop through each contenthash response and see 
		foreach($response['Responses'][$config['tableName']] as $r) {
			//print_r($r);
			//echo "<BR><BR><BR>";
				
			if (isset($r['status'])) {
				// Check to see if OCR status is known.  If so, report it.  If not, upload file to S3.
				$file_status = json_decode($r['status']['S'], true);
				
				// Update file_item status
				foreach ($file_list as &$f) {
					if ($f['contenthash'] == $r['contenthash']['S']) {
						$f['status'] = $file_status;
					}
				}
				unset($f);
				
			}	
		}	
	}
	
	// At this point, all the file items have had their status updated
	// Now check for missing status file items
	// Update file_item status
	$batchwrite_request = array();
	$hash_write_list = array();
	foreach ($file_list as &$f) {
		//echo($f['contenthash'] . " (" . $f['status'] . ") ==> no status<BR>");
		if (is_null($f['status'])) {
			$f['status'] = array('ocr' => 'pending');
			array_push($hash_write_list, $f['contenthash']);	
		}
	}
	unset($f);
	
	
	// Avoid duplicates
	foreach (array_unique($hash_write_list) as $h) {
		array_push($batchwrite_request, array('PutRequest' => array(
				'Item' => array(
					'contenthash'     => array('S' => $h), 
					'status'          => array('S' => json_encode(array('ocr' => 'pending'))) 
				)
			)
			)			
		);	
	}
	


	write_filedata_to_db($batchwrite_request);	
	
	
	// If file exists in db and has a complete status, return the results.  If not, 
	// upload the file to S3.


	//write_filedata_to_db($contenthash);
	//upload_file_to_s3($contenthash, $file);
	//return array('ocr' => 'pending');	

}








function write_filedata_to_db($contenthashes) {

	global $config;
	//echo "Writing file to db";
	
	
	// Chunk array into the size for DynamoDB batch operations
	$chunked_hashes = array_chunk($contenthashes, $config['dynamodb_batchwrite_size']);
	

	foreach ($chunked_hashes as $k) {

		try {
			$response = $config['dynamodb']->batchWriteItem([
				'RequestItems' => [
					$config['tableName'] =>	$k
				],
			]);
			//echo "done.\n";
		} catch (DynamoDbException $e) {
			echo $e->getMessage() . "\n";
			exit ("Unable to load data into $tableName\n");
		}
	}	


}





function upload_file_to_s3($contenthash, $file) {

	global $config;

	// Create an Amazon S3 client using the shared configuration data.
	$s3Client = $config['sdk']->createS3();

	//echo "Created S3 client\n";

	// Need to copy file locally (can't get direct access to Moodle files)
	$tempfile = tempnam('./temp', 'pdf_');
	$file->copy_content_to($tempfile);
	//echo "Temp file: " . $tempfile;


	try {
	    // Upload data.
		$result = $s3Client->putObject(array(
			'Bucket' => 'pdf-checker',
			'Key'    => $contenthash,
			'SourceFile'   => $tempfile
		));

		// Print the URL to the object.
		//echo $result['ObjectURL'] . "\n";
		
	} catch (MultipartUploadException $e) {
		echo $e->getMessage() . "\n";
	} catch (S3Exception $e) {
		// Catch an S3 specific exception.
		echo $e->getMessage();
	} catch (AwsException $e) {
		// This catches the more generic AwsException. You can grab information
		// from the exception using methods of the exception object.
		echo $e->getAwsRequestId() . "\n";
		echo $e->getAwsErrorType() . "\n";
		echo $e->getAwsErrorCode() . "\n";
	}	
	
	
	//echo "Send S3 PutObject request: ";
	
	unlink($tempfile);
	//echo "Deleted tempfile: $tempfile";
	

}

?>
<?php

// Include the SDK using the Composer autoloader
require 'vendor/autoload.php';
$config = [];
use Aws\S3\MultipartUploader;
use Aws\Exception\MultipartUploadException;


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

	// Set up dynamodb
	$config['dynamodb'] = $config['sdk']->createDynamoDb();

	$config['dynamodb_batch_size'] = 100;	// Max batch operations for DynamoDB
	$config['dynamodb_batchwrite_size'] = 25;
	
	$config['tableName'] = 'moodle-files';

}






function batchGetFileStatus(&$file_list) {

	global $config;

	// Get all contenthashes from the file list
	// Break into groups of 100 (AWS Dynamodb limit for batch read)
	
	$keys = array();
	$hashes = array();
	
	// Make array of all contenthashes to check on
	foreach ($file_list as $f) {
		array_push($hashes, $f['contenthash']);
	}

	// Make a array of unique db lookup queries for each contenthash
	foreach (array_unique($hashes) as $h) {
		$keys[] = array('contenthash' => array( 'S' => $h));
	}

	// Chunk array into the size for DynamoDB batch operations
	$chunked_keys = array_chunk($keys, $config['dynamodb_batch_size']);
	
	// Run query on each chunk of content hash queries
	foreach ($chunked_keys as $k) {
		
		// Lookup contenthash chunk in db
		try {

			$response = $config['dynamodb']->batchGetItem( array(
				'RequestItems' => array($config['tableName'] => array('Keys' => $k))
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
		
		// Loop through each contenthash response and see if there is a status reported in the database
		// if so, set the status in the $file_list object
		foreach($response['Responses'][$config['tableName']] as $r) {
			if (isset($r['status'])) {
				foreach ($file_list as &$f) {
					if ($f['contenthash'] == $r['contenthash']['S']) {
						$f['status'] = json_decode($r['status']['S'], true);
					}
				}
				unset($f);	
			}	
		}	
	} // Done looping through all the file_list items
	
	// At this point, all the file items have had their status updated with any existing values in the db.
	// Now check for any file_list items without a status value and set them to a "pending" value.
	$hash_write_list = array();
	foreach ($file_list as &$f) {
		if (is_null($f['status'])) {
			$f['status'] = array('ocr' => 'pending');
			array_push($hash_write_list, $f['contenthash']);	
		}
	}
	unset($f);
	
	// To avoid duplicate entries, need to make sure contenthash values are unique
	$batchwrite_request = array();
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
	
	
	

	// Loop through each unique content hash and write it's file to the database

	foreach (array_unique($hash_write_list) as $h) {
		// Find file reference for hash
		$key = array_search($h, array_column($file_list,'contenthash'));
		upload_file_to_s3($h, $file_list[$key]['file']);
	}

}







// Given a list of database entries, insert them into the db
function write_filedata_to_db($file_items) {

	global $config;
	
	// Chunk array into the size for DynamoDB batch operations
	$chunked_hashes = array_chunk($file_items, $config['dynamodb_batchwrite_size']);

	foreach ($chunked_hashes as $k) {
		try {
			$response = $config['dynamodb']->batchWriteItem([
				'RequestItems' => [
					$config['tableName'] =>	$k
				],
			]);
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

	$uploader = new MultipartUploader($s3Client, $tempfile, [
		'bucket' => 'pdf-checker',
		'key'    => $contenthash,
	]);

	try {
		$result = $uploader->upload();
	} catch (MultipartUploadException $e) {
		echo $e->getMessage() . "\n";
	}

/*
	$result = $s3Client->createMultipartUpload([
		'bucket' => 'pdf-checker',
		'key'    => $contenthash
	]);

	try {
		$result = $uploader->upload();
		echo "Upload complete: {$result['ObjectURL'}\n";
		
	} catch (MultipartUploadException $e) {
		echo $e->getMessage() . "\n";
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
	
	*/
	//echo "Send S3 PutObject request: ";
	
	unlink($tempfile);
	//echo "Deleted tempfile: $tempfile";
	

}

?>
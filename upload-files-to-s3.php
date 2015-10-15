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

	$config['tableName'] = 'moodle-files';

}






function check_db_for_file($filedata, $file) {

	global $config;

	//echo "Looking up contenthash in database";
	
	// Lookup contenthash in db
	try {

		$response = $config['dynamodb']->getItem([
			'TableName' => $config['tableName'],
			'Key' => [
				'contenthash' => [ 'S' => $filedata['contenthash'] ],
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
		
		if (isset($file_status->ocr_status)) {
			return $file_status->ocr_status;
		} 		
	}

	write_filedata_to_db($filedata);
	upload_file_to_s3($filedata, $file);
	return false;	

}






function write_filedata_to_db($filedata) {

	global $config;
	//echo "Writing file to db";

	$contenthash = $filedata['contenthash'];
	unset($filedata['contenthash']);		// Remove to avoid duplication

	try {
		$response = $config['dynamodb']->batchWriteItem([
			'RequestItems' => [
				$config['tableName'] => [
					[
						'PutRequest' => [
							'Item' => [
								'contenthash'     => ['S' => $contenthash], 
								'status'          => ['S' => json_encode($filedata)],      
							]
						]
					]
				],
			],
		]);
		//echo "done.\n";
	} catch (DynamoDbException $e) {
		echo $e->getMessage() . "\n";
		exit ("Unable to load data into $tableName\n");
	}
	


}





function upload_file_to_s3($filedata, $file) {

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
			'Key'    => $filedata['contenthash'],
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
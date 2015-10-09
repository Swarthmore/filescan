<?php

// Include the SDK using the Composer autoloader
require 'vendor/autoload.php';


function init_aws_services() {

	echo "Starting AWS script\n";

	// Use the us-west-2 region and latest version of each client.
	$sharedConfig = [
		'region'  => 'us-east-1',
		'version' => 'latest'
	];

	// Create an SDK class used to share configuration across clients.
	$sdk = new Aws\Sdk($sharedConfig);

	echo "Created SDK class\n";

}



function check_db_for_file($file_object) {



}




function upload_file_to_s3() {



// Create an Amazon S3 client using the shared configuration data.
$s3Client = $sdk->createS3();

echo "Created S3 client\n";



// See if contenthash exists in database
$dynamodb = $sdk->createDynamoDb();
echo "Created DynamoDB client\n";

$tableName = 'moodle-files';

try {
    $response = $dynamodb->batchWriteItem([
        'RequestItems' => [
            $tableName => [
                [
                    'PutRequest' => [
                        'Item' => [
                            'contenthash'     => ['S' => '206'], 
                            'status'          => ['S' => '{"status":null}'],      
                        ]
                    ]
                ]
            ],
        ],
    ]);
    echo "done.\n";
} catch (DynamoDbException $e) {
    echo $e->getMessage() . "\n";
    exit ("Unable to load data into $tableName\n");
}




// Lookup file
try {

	$response = $dynamodb->getItem([
		'TableName' => $tableName,
		'Key' => [
			'contenthash' => [ 'S' => '206' ],
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
	
echo "Lookup data in database\n";

print_r ($response['Item']);

}

/*
try {
	// Send a PutObject request and get the result object.
	$result = $s3Client->putObject([
		'Bucket' => 'pdf-checker',
		'Key'    => 'my-key',
		'Body'   => 'this is the body!'
	]);
	
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
	
	

echo "Send S3 PutObject request: ";
*/


?>
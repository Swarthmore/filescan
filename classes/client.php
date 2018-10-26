<?php

require_once('./curl.php');

$token = "08248df90017c46872d3d28faa495d01";
$domainname = 'https://mappt.swarthmore.edu/';
$functionname = 'block_filescan_get_access_files';
$restformat = 'json';

$payload = array(
    'start'     => 10,
    'length'    => 50
);

// rest call
header('Content-Type: text/plain');
$serverurl = $domainname . '/webservice/rest/server.php'. '?wstoken=' . $token . '&wsfunction=' . $functionname;


$curl = new curl;
$restformat = ($restformat == 'json')?'&moodlewsrestformat=' . $restformat:'';
$response = $curl->post($serverurl . $restformat, $payload);

print_r($response);
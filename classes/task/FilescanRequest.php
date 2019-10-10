<?php

class FilescanRequest
{
  private $api_url;
  private $server_url;
  private $file_id;
  private $file_url;

  function __construct($api_url, $server_url, $file_id, $file_url)
  {
    $this->api_url    = $api_url;
    $this->server_url = $server_url;
    $this->file_id    = $file_id;
    $this->file_url   = $file_url;
  }

  // Sends the request and returns the response
  public function send() {
    $ch = curl_init();

    // Set up php_curl options
    $opts = array(
      CURLOPT_URL             => $this->api_url,
      CURLOPT_POST            => true,
      CURLOPT_RETURNTRANSFER  => true,
      CURLOPT_TIMEOUT         => 120,
      CURLOPT_HTTPHEADER      => [
        "Content-Type: multipart/form-data",
      ],
      CURLOPT_POSTFIELDS      => [
        "id"      => $this->file_id,
        "upfile"  => $this->file_url 
      ],
      CURLOPT_VERBOSE         => true
    );

    curl_setopt_array($ch, $opts);

    $res = curl_exec($ch);

    // cleanup
    curl_close($ch);
    unset($ch);

    return $res;
  }
}

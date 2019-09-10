<?php

/**
 * The Filescan_Request object is can be used to send a download URL to a Filecan server.
 * 
 * @author Anthony Weed <aweed1@swarthmore.edu>
 * @return void
 */
class Filescan_Request {

    private $api_base_url;
    private $api_route;
    private $file_id;
    private $file_url;

    private $errors = array();

    public function __construct($api_base_url, $api_route, $file_id, $file_url) {
        $this->api_base_url = $api_base_url;
        $this->api_route = $api_route;
        $this->file_id = $file_id;
        $this->file_url = $file_url;
    }

    /**
     * The send method sends the request to the filescan server. If the request is succesful, the method will
     * return true. If there is an error, the method will return false. Errors can be retrieved by calling
     * the object's get_errors() method.
     * 
     * @param string $api_base_url
     * @param string $api_route
     * @param string $file_id
     * @param string $file_url
     * 
     * @return boolean
     */
    public function send() {
        $handle = curl_init();

        // Create the api endpoint url
        $url = $this->api_base_url . $this->api_route;

        // Set the headers (For additional headers, just add an array element)
        $headers = array("Content-Type: multipart/form-data");

        // Create the post data
        $file = curl_file_create($this->file_url, "application/pdf", $this->file_id);
        $post_data = array("upfile" => $file);

        // Set up the curl options
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_HEADER, $headers);
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($handle, CURLOPT_TIMEOUT, 120);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true); // if any redirection after upload
        curl_setopt($handle, CURLOPT_VERBOSE, true);

        // Send the request
        $res = curl_exec($handle);
        $info = curl_getinfo($handle);
        
        // Check for any errors that may have occured
        if (curl_errno($handle)) {
            $error_msg = curl_error($handle);
            if (isset($error_msg)) {
                array_push($this->errors, $error_msg);
                return false;
            }
        }

        // Cleanup the connection and return
        curl_close($handle);
        unset($handle);

        return true;
    }

    /**
     * The get_errors method returns any errors that may have occured during the lifespan of this object.
     * 
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }
};
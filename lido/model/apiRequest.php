<?php
/**
 * Perform the upstream API request
 */


class apiRequest {

    public $remote_url;
    private $format;
    private $remote_format;
    public $response = array (
        'header' => null,
        'content' => null
    );
    private $remote_status;

    function __construct () {
        $this->remote_url = $this->remoteUrl ();
        $this->format = $this->format ();
        $this->remote_format = $this->remoteFormat ();
    }

    /*
     * Get r-parameter from GET-request
     */
    private function remoteUrl () {
        if (isset ($_GET['r'])) {
            return urldecode ($_GET['r']);
        } else {
            throw new Exception ('Error: parameter r is missing from the GET-request');
        }
    }

    /*
     * Get format-parameter from GET-request (is optional, defaults to JSON)
     */
    private function format () {
        if (isset ($_GET['format'])) {
            return strtoupper ($_GET['format']);
        } else {
            return 'JSON';
        }
    }

    /*
     * Get remote_format-parameter from GET-request (is optional, defaults to XML)
     */
    private function remoteFormat () {
        if (isset ($_GET['remote_format'])) {
            return strtoupper ($_GET['remote_format']);
        } else {
            return 'XML';
        }
    }

    /*
     * Resolve the current request
     * Sets $this->response
     */
    public function resolve () {
        if ($this->downloadRemote () != true) {
            throw new Exception ('Error: failed to download Remote URL: ERROR '.$this->remote_status);
        }
    }

    /*
     * Download the remote URL into $this->response['content']
     */
    private function downloadRemote () {
        $ch = curl_init ();
        curl_setopt ($ch, CURLOPT_URL, $this->remote_url);
        curl_setopt ($ch, CURLOPT_REFERER, 'http://www.packed.be');
        curl_setopt ($ch, CURLOPT_USERAGENT, 'SimpleCollectionViewAPI');
        curl_setopt ($ch, CURLOPT_HEADER, 0);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        $output = curl_exec ($ch);
        $status = curl_getinfo ($ch, CURLINFO_HTTP_CODE);
        $ctype = curl_getinfo ($ch, CURLINFO_CONTENT_TYPE);
        curl_close ($ch);
        if ($status >= 400) {
            /* Error */
            $this->remote_status = $status;
            return false;
        }
        $this->response['content'] = $output;
        $this->response['header'] = $ctype;
        return true;
    }

}
<?php
require_once('RemoteAuthRequest.php');

/**
 * Class responsible for performing requests to the remote API, identified by $remote_url
 */
class RemoteRequest {

    protected $remote_url;
    protected $token;
    protected $headers = array();
    protected $c; /* Curl object */
    public $request = array (
        'request' => array(
            'url' => '',
            'headers' => array()
        ),
        'reply' => array(
            'url' => '',
            'data' => '',
            'content-type' => '',
            'parsed' => null
        )
    );
    private $auth_request;

    /**
     * Create a remote request to $remote_url; if authentication ("basic") is required, set $authenticate to true
     * and $remote_type to the key of the auth. key in list.json.
     * @param $remote_url
     * @param bool|false $authenticate
     * @param null $remote_type
     * @param bool|false $parse whether to try to parse the API reply
     * @throws Exception
     */
    function __construct ($remote_url, $authenticate = false, $remote_type = null, $parse = false) {
        if ($authenticate === true) {
            $this->auth_request = new RemoteAuthRequest();
            array_push($this->headers, $this->remote_auth_request($remote_type));
        }
        $this->set_headers();
        $this->prepare($remote_url);
        $response = $this->execute();
        $this->request['reply'] = $this->parse_reply($response[0], $response[1], $parse);
        $this->request['url'] = $this->remote_url;
        $this->request['headers'] = $this->headers;
    }

    /**
     * Function to create a authentication headers for remotes requiring authentication
     * @throws Exception
     * @param string $remote_type API name of the remote, corresponds to a key in list.json
     * @return string $auth_header
     */
    protected function remote_auth_request ($remote_type) {
        if ($remote_type === null) {
            throw new Exception ('Error: no remote_type specified!');
        }
        return $this->auth_request->add_basic_header($remote_type);
    }

    /**
     * Function to set the headers
     */
    protected function set_headers() {

    }

    /**
     * Function to prepare a request to the remote using curl
     * @param string $remote_url
     */
    protected function prepare ($remote_url) {
        $this->remote_url = $remote_url;
        $this->c = curl_init();
        curl_setopt($this->c, CURLOPT_URL, $this->remote_url);
        curl_setopt($this->c, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->c, CURLOPT_HTTPHEADER, $this->headers);
    }

    /**
     * Function to execute a remote request as prepared by $this->prepare using $this->c als Curl object
     * @throws Exception
     * @return array (content_type, data)
     */
    protected function execute() {
        $data = curl_exec($this->c);
        if (curl_getinfo($this->c, CURLINFO_HTTP_CODE) >= 400) {
            /* Throw exception when the request failed */
            throw new Exception('Error: remote returned an error: '.curl_getinfo($this->c, CURLINFO_HTTP_CODE));
        }
        $content_type = curl_getinfo($this->c, CURLINFO_CONTENT_TYPE);
        curl_close($this->c);
        return array(
            $content_type,
            $data
        );
    }

    /**
     * Function to parse a remote request into an array suitable for assignong to $this->request['reply']
     * @param $content_type
     * @param $data
     * @param $parse; if set to true, attempt to parse the remote replies depending on content_type
     * @return array
     * @throws Exception
     */
    protected function parse_reply($content_type, $data, $parse = true) {
        /* For some content types, like application/json or xml, we try to parse it using PHP's internal parsers
            for json: json_decode
            for xml: simplexml
        If we fail, we ignore it and don't parse
        */
        if ($data == null) {
            throw new Exception('Error: remote returned an empty response!');
        }
        $reply = array(
            'parsed' => null
        );
        if ($parse === true) {
            if ($content_type == 'application/json') {
                $json = json_decode($data, true);
                if (json_last_error() == JSON_ERROR_NONE) {
                    $reply['parsed'] = $json;
                }
            }
            if (preg_match('/xml/', $content_type)) {
                $xml = simplexml_load_string($data);
                if ($xml !== false) {
                    $reply['parsed'] = $xml;
                }
            }
        }
        $reply['url'] = $this->remote_url;
        $reply['data'] = $data;
        $reply['content-type'] = $content_type;
        return $reply;
    }
}
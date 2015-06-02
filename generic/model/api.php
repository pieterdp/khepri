<?php


class api {

    /**
     * Lazy api that bypasses Cross-request-origin policy for javascript apps on this domain.
     * Gets the response of an upstream api (identified by url) and output as response, changing nothing and
     * keeping the header content-type
     * For the response of the V&A API, do some extra tricks
     */

    public $response = array (
        'content' => '',
        'headers' => array ()
    );
    protected $request = array (
        'url' => '',
        'upstream_response' => array (
            'content' => '',
            'content-type' => ''
        )
    );
    protected $vam = array (
        'results' => array (),
        'amount' => 0
    );

    function __construct () {
        $this->request['url'] = $this->get_request ();
        $this->request['upstream_response'] = $this->perform_upstream_request ();
        if (preg_match ('/'.preg_quote ('vam.ac.uk').'/', $this->request['url']) == 1 && preg_match ('/'.preg_quote ('search?').'/', $this->request['url']) == 1) {
            /* Matched */
            /*
             * The V&A API limits the contents of a single request to max 45 items, but there could be more.
             * This code will fetch all results (using result_count) and concatenate them to one big json-array
             * that looks like the original result, but contains all the items.
             */
            $j = json_decode ($this->request['upstream_response']['content'], true);
            if (json_last_error () != JSON_ERROR_NONE) {
                throw new Exception ('Error: the V&A API returned malformed JSON!');
            }
            $this->vam['amount'] = $j['meta']['result_count'];
            $this->iterate_vam_api ();
            /* Now add the complete list to $j['records'] (=> where they are in the original response) */
            $j['records'] = $this->vam['results'];
            /* Set this->request['content'] to the json_encoded string */
            $this->request['upstream_response']['content'] = json_encode ($j);
        }
        /* Create the response */
        $this->create_response ();
    }


    /**
     * Reply to the request: set the HTTP-headers and the content
     */
    public function reply () {
        foreach ($this->response['headers'] as $header) {
            header ($header);
        }
        /* Body */
        echo ($this->response['content']);
    }

    /**
     * Function that creates $this->response
     */
    protected function create_response () {
        if ($this->request['upstream_response']['content-type'] == null) {
            array_push ($this->response['headers'], 'Content-Type: text/plain'); /* Fall-back */
        } else {
            array_push ($this->response['headers'], 'Content-Type: '.$this->request['upstream_response']['content-type']);
        }
        array_push ($this->response['headers'], 'Access-Control-Allow-Origin: *');
        $this->response['content'] = $this->request['upstream_response']['content'];
    }

    /**
     * Iterate over the response of the VAM API to get all responses in $this->vam['results']
     * @throws Exception
     */
    protected function iterate_vam_api () {
        /* Replace the original &limit=[0-9]+ with 45 or add the parameter */
        if (preg_match ('/&limit=[0-9]+/', $this->request['url']) == 1) {
            /* replace */
            $url = preg_replace ('&limit=[0-9]+', '&limit=45', $this->request['url']);
        } else {
            $url = $this->request['url'].'&limit=45';
        }
        /* Now loop (in steps of 45) till we get to the max_results and add offset */
        for ($i = 0; $i <= $this->vam['amount']; $i = $i + 45) {
            $r_url = $url.'&offset='.$i;
            $r = $this->perform_upstream_request ($r_url);
            $j = json_decode ($r['content'], true);
            if (json_last_error () != JSON_ERROR_NONE) {
                throw new Exception ('Error: the V&A API returned malformed JSON!');
            }
            $this->vam['results'] = array_merge ($this->vam['results'], $j['records']);
        }
    }
    /**
     * Get the request
     * @return null|string
     * @throws Exception
     */
    protected function get_request () {
        return urldecode ($this->__get_parameter ('url'));
    }

    /**
     * Perform a request to the upstream provided in $this->request['url']
     * @param $url (optional)
     * @throws Exception
     * @return array $reply (has the same keys/values as $this->requesŧ['upstream_response'])
     */
    protected function perform_upstream_request ($url = null) {
        /*CURLINFO_CONTENT_TYPE*/
        $reply = array ();
        if ($url == null) {
            $url = $this->request['url'];
        }
        $curl = curl_init ();
        curl_setopt ($curl, CURLOPT_URL, $url);
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec ($curl);
        if (curl_getinfo ($curl, CURLINFO_HTTP_CODE) != 200) {
            throw new Exception ('Error: upstream '.$url.' did not return 200 OK: '.curl_getinfo ($curl, CURLINFO_HTTP_CODE));
        }
        $reply['content'] = $data;
        $reply['content-type'] = curl_getinfo ($curl, CURLINFO_CONTENT_TYPE);
        curl_close ($curl);
        return $reply;
    }

    /**
     * Get the $_GET[param] for a request, with isset-checking
     * @param $param
     * @param bool $CAN_BE_EMPTY
     * @return null|string
     * @throws Exception
     */
    protected function __get_parameter ($param, $CAN_BE_EMPTY = false) {
        if (isset ($_GET[$param])) {
            return $_GET[$param];
        } else {
            if ($CAN_BE_EMPTY == true) {
                return null;
            }
            throw new Exception ('Error: required parameter '.$param.' is not set!');
        }
    }

}
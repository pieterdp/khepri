<?php
/**
 * Created by PhpStorm.
 * User: pieter
 * Date: 1/06/15
 * Time: 15:15
 */

require_once ('apiRequest.php');
require_once ('apiResponse.php');
/* TODO preprocessor for XML-data that is wrapped around a LIDO */

require_once ('lidoBase.php');
require_once('lidoJSON.php');
require_once('lidoXML.php');

class api {

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
    private $parsing = array (
        'unwrapped_xml' => array ()
    );
    private $req; /* apiRequest object */
    private $resp; /* apiResponse object */
    private $unwrap; /* lidoBase object */

    function __construct () {
        $this->req = new apiRequest ();
        $this->req->resolve ();
        $this->request['url'] = $this->req->remote_url;
        $this->request['upstream_response']['content'] = $this->req->response['content'];
        $this->request['upstream_response']['content-type'] = $this->req->response['header'];
        if ($this->unwrap () != true) {
            throw new Exception ('Error: failed to unwrap XML.');
        }
        if ($this->prepare_response () != true) {
            throw new Exception ('Error: failed to convert XML to JSON.');
        }
        array_push ($this->response['headers'], 'Content-type: application/json; charset=utf-8');
    }

    /**
     * Prepare the response
     * @return bool
     * @throws Exception
     */
    protected function prepare_response () {
        $r = array ();
        foreach ($this->parsing['unwrapped_xml'] as $lidoxml) {
            $l = new lidoXML ($lidoxml);
            $j = new lidoJSON ($l->convertAllFromXML (), 'baselido');
            //array_push ($r, json_decode ($j->convertAllToJSON (), true));
            array_push ($r, $j->convertToJSON ());
            if (json_last_error () != JSON_ERROR_NONE) {
                throw new Exception ('Error: failed to decode JSON response');
            }
        }
        /* If we only have one result, simple return that; else, jsonify array */
        if (count ($r) == 1) {
            $this->response['content'] = json_encode ($r[0]);
        } else {
            $this->response['content'] = json_encode ($r);
        }
        return true;
    }

    /**
     * Get the request
     * @return null|string
     * @throws Exception
     */
    protected function get_request () {
        return urldecode ($this->__get_parameter ('r'));
    }

    /**
     * Unwrap the xml into an array of strings containing only the topmost element(s) (and their children) in the lido namespace
     * @return bool
     */
    protected function unwrap () {
        $this->unwrap = new lidoBase ($this->request['upstream_response']['content'], 'lido');
        $this->parsing['unwrapped_xml'] = $this->unwrap->unwrap_all ();
        if (count ($this->parsing['unwrapped_xml']) == 0) {
            return false;
        }
        return true;
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
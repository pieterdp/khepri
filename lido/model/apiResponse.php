<?php
/**
 * Return the JSON/XML-translation of the upstream API response
 */

class apiResponse {

    public $header;
    public $content;

    function __construct ($content, $TYPE = 'application/json') {
        $this->setContentType ($TYPE);
        $this->setContent ($content);
    }

    /*
     * Set the header (for use in head ())
     * @param string $TYPE - content-type
     */
    protected function setContentType ($TYPE) {
        $header = 'Content-Type: %s; charset=utf-8';
        $this->header = sprintf ($header, $TYPE);
    }

    /*
     * Set the content (directly echoed to the page)
     * @param string $content
     */
    protected function setContent ($content) {
        $this->content = $content;
    }
}
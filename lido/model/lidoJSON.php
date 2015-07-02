<?php
/**
 * Class lidoJSON
 * TODO: make function names portable with lidoXML and vice versa
 * Convert JSON to XML does not yet work
 */

require_once('lidoBase.php');

class lidoJSON {

    public $json_data;
    public $base_lido_element;

    function __construct ($v, $TYPE = 'string') {
        switch ($TYPE) {
            case 'string':
                $this->__load_json_string ($v);
                break;
            case 'file':
                $this->__load_json ($v);
                break;
            case 'baselido':
                $this->__load_baseLidoElement ($v);
                break;
            default:
                throw new Exception ('Error: unknown type');
                break;
        }
    }

    /*
     * Import a JSON-string
     */
    protected function __load_json_string ($string) {
        $this->json_data = json_decode ($string, true);
        if (json_last_error () != JSON_ERROR_NONE) {
            throw new Exception ('Error: failed to decode JSON string');
        }
    }

    /*
     * Import a JSON-file (convert to baseLidoElement later on)
     */
    protected function __load_json ($file) {
        if (!file_exists ($file)) {
            throw new Exception ('Error: file '.$file.' does not exist!');
        }
        $this->json_data = json_decode (file_get_contents ($file), true);
        if (json_last_error () != JSON_ERROR_NONE) {
            throw new Exception ('Error: failed to decode JSON file');
        }
    }
    /*
     * Import a baseLidoElement
     */
    protected function __load_baseLidoElement ($element) {
        $this->base_lido_element = $element;
    }

    /**
     * Array
    (
    [0] => Array
    (
    [name] => lidoRecID
    [content] => YCBA/lido-TMS-499
    [attributes] => Array
    (
    [source] => Array
    (
    [0] => Array
    (
    [name] => source
    [value] => Yale Center for British Art
    )

    )

    [type] => Array
    (
    [0] => Array
    (
    [name] => type
    [value] => local
    )

    )

    )

    [__children] => Array
    (
    )

    [__attributes] => Array
    (
    [0] => source
    [1] => type
    )

    )

    )
     * @param $baseElement
     * @return array
     */
    protected function singleToJSON ($baseElement) {
        /*
         * $baseElement = array (
         *  [0] => array (__children => array (child1, child2 etc.); __attributes => array (attr1, attr2); attributes => array (foo, bar); name => string; content => string)
         * )
         */
        $j = array ();
        if (count ($baseElement) == 1) {
            /* $baseElement is always in array, but it an have a single element (this means only 1 element has this name at this position - i.e. <foo><bar>xx</bar></foo> instead of <foo><bar>a</bar><bar>b</bar></foo> */
            $b = $baseElement[0];
            $j = $this->elementToJSON ($b);
        } else {
            foreach ($baseElement as $b) {
                array_push ($j, $this->elementToJSON ($b));
            }
        }
        return $j;
    }

    /**
     * Convert a really single baseElement to a JSON-ifyable array
     * Array
    (
    [name] => lidoRecID
    [content] => YCBA/lido-TMS-499
    [attributes] => Array
    (
    [source] => Array
    (
    [0] => Array
    (
    [name] => source
    [value] => Yale Center for British Art
    )

    )

    [type] => Array
    (
    [0] => Array
    (
    [name] => type
    [value] => local
    )

    )

    )

    [__children] => Array
    (
    )

    [__attributes] => Array
    (
    [0] => source
    [1] => type
    )

    )

     * @param $element
     * @return array
     */
    protected function elementToJSON ($element) {
        /* $element is a single element from a baseElement array potentially containing multiple - i.e. $list = array ($e, $e, $e) */
        $j = array ();
        if ($element['content'] != '') {
            /* Has content */
            $j['value'] = $element['content'];
        }
        /* Return attributes */
        if (count ($element['__attributes']) != 0) {
            $this->parseAttributes ($j, $element);
        }
        if (count ($element['__children']) != 0) {
            /* Has children */
            foreach ($element['__children'] as $child) {
                $j[$child] = $this->singleToJSON ($element[$child]);
            }
        }
        return $j;
    }

    /**
     * Parse the attributes to the $json-array from singleToJSON
     * @param &$j
     * @param $b
     */
    protected function parseAttributes (&$j, $b) {
        foreach ($b['__attributes'] as $a_name) {
            $a = $b['attributes'][$a_name]; /* Array of attributes with the same name */
            if (count ($a) == 1) {
                /* Only one attribute with this name */
                $r = array_shift ($a);
                $j[$r['name']] = $r['value'];
            } else {
                $j[$a_name] = array ();
                foreach ($a[$a_name] as $single) {
                    array_push ($j[$a_name], $single['value']);
                }
            }
        }
    }

    /**
     * Convert a base_lido_element to an object (or array) that mimicks LIDO and can be converted to a JSON string
     * @return array
     */
    public function convertToJSON () {
        /*
         * $base_lido_element is like
         * name = lido
         * content = null
         * children = elements
         * => convert to normal array 'cause that's what singleToJSON expects
         */
        $e = array ($this->base_lido_element);
        $j = $this->singleToJSON ($e);
        return $j;
    }
}
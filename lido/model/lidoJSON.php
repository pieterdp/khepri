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
        $this->json_data = $this->convertSingleElementToJSONData ($this->base_lido_element);
    }

    /*
     * Function to convert a single element to JSONdata (can't convert to string because we want to recurse)
     * @param baseLidoElement $element
     * @return array $jsondata
     */
    public function convertSingleElementToJSONData ($element) {
        $json = array (
        );
        if (count ($element['__children']) != 0) {
            /* Element has children */
            foreach ($element['__children'] as $child_name) {
                $child = $this->convertChildElementToJSONData ($element[$child_name]);
                if (isset ($json[$child_name])) {
                    if (is_array ($json[$child_name])) {
                        array_push ($json[$child_name], $child);
                    } else {
                        $json[$child_name] = array ($json[$child_name], $child);
                    }
                } else {
                    $json[$child_name] = $child;
                }
            }
        } else {
            /* Element has no children */
            if ($element['content'] != '') {
                /* Element has content */
                $json['value'] = $element['content'];
            }
        }
        /* Add attributes */
        if (count ($element['__attributes']) != 0) {
            foreach ($element['__attributes'] as $attribute) {
                $json[$attribute] = $this->convertAttributeToJSONData ($element['attributes'][$attribute]);
            }
        }
        return $json;
    }

    /*
     * Function to convert $this->base_lido_element to JSON-string
     * @return string $json
     */
    public function convertAllToJSON () {
        return json_encode ($this->convertSingleElementToJSONData ($this->base_lido_element));
    }

    /*
     * Function to convert a child element to JSONdata (child elements are wrapped in arrays, as are attributes)
     */
    protected function convertChildElementToJSONData ($element) {
        $json = array ();
        if (count ($element) == 1) {
            /* Array with only one element */
            $json = $this->convertSingleElementToJSONData ($element[0]);
        } else {
            foreach ($element as $single_element) {
                array_push ($json, $this->convertSingleElementToJSONData ($single_element));
            }
        }
        return $json;
    }

    /*
     * Function to convert a attribute to JSONdata (attributes are wrapped in arrays)
     */
    protected function convertAttributeToJSONData ($attribute) {
        $json = array ();
        if (count ($attribute) == 1) {
            $json = $attribute[0]['value'];
        } else {
            foreach ($attribute as $single_attribute) {
                array_push ($json, $single_attribute['value']);
            }
        }
        return $json;
    }
}
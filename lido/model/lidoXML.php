<?php

/**
 * Class lidoXML
 * (c) 2015 PACKED vzw <pieter@packed.be>
 * Convert lido XML to a baseLidoElement and vice-versa
 */

require_once('baseLidoElement.php');
require_once('lidoBase.php');

class lidoXML {

    public $xml_data;
    public $base_lido_element;
    protected $internal_lido;
    private $ns;

    /**
     * @param $v string either a XML string or an XML file
     * @param string $TYPE
     * @throws Exception
     */
    function __construct ($v, $TYPE = 'string') {
        switch ($TYPE) {
            case 'string':
                $this->__load_xml_string ($v);
                break;
            case 'file':
                $this->__load_xml ($v);
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
     * Import XML file
     */
    protected function __load_xml ($file) {
        /*
         * Must take all variables and then objectify them
         */
        $this->ns = 'lido';
        if (!file_exists ($file)) {
            throw new Exception ('Error: file '.$file.' does not exist!');
        }
        $this->xml_data = simplexml_load_file ($file, 'SimpleXMLElement', LIBXML_PARSEHUGE, $this->ns, true);
    }

    /*
     * Import XML string
     */
    protected function __load_xml_string ($string) {
        $this->ns = 'lido';
        $this->xml_data = simplexml_load_string ($string, 'SimpleXMLElement', LIBXML_PARSEHUGE, $this->ns, true);
    }

    /*
     * Import baseLidoElement
     */
    protected function __load_baseLidoElement ($element) {
        $this->base_lido_element = $element;
    }

    /*
     * Function to convert a single SimpleXMLElement Object to a baseElement or baseAttribute
     * @param SimpleXMLElement Object $element
     * @return assoc array $element
     */
    protected function convertSingleElementFromXML ($element) {
        /*
         * Converting an element to a string gets its value
         * foreach supported
         */
        if (trim ($element->__toString (), " \n") == '') {
            /* This element has no string content - i.e. <foo></foo> instead of <foo>bar</foo> */
            $content = null;
        } else {
            $content = trim ($element->__toString ());
        }
        $baseElement = new baseLidoElement ();
        $baseElement->__create ($element->getName (), $content);
        if ($element->count () != 0) {
            /* Element has children */
            $children = $element->children ($this->ns, true);
            foreach ($children as $child) {
                $baseElement->addChild ($this->convertSingleElementFromXML ($child));
            }
        }
        /* Add attributes */
        $attrs = $element->attributes ($this->ns, true);
        foreach ($attrs as $name => $value) {
            $baseElement->addAttribute ($baseElement->baseAttribute ($name, trim ($value->__toString (), " \n")));
        }
        return $baseElement->element;
    }

    /*
     * Function to convert $this->xml_data to a baseElement
     * @return assoc array $element
     */
    public function convertAllFromXML () {
        $this->internal_lido = $this->convertSingleElementFromXML ($this->xml_data);
        return $this->internal_lido;
    }
}
<?php

/**
 * Class baseLidoElement
 * (c) 2015 PACKED vzw <pieter@packed.be>
 */

class baseLidoElement {
    protected $baseElement = array (
        'name' => '', /* LIDO-tag */
        'content' => '', /* text content, if any */
        'attributes' => array (), /* $attributes[name] = array of values */
        '__children' => array (), /* List of the child_name of all children of this element, for easier retrieval. Children are accessed $element[child_name] = child */
        '__attributes' => array () /* List of all the attribute_names, for easier retrieval. Attributes are accessed $attribute[attribute_name] = attribute */
    );
    protected $baseAttribute = array (
        'name' => '', /* Attribute name */
        'value' => '' /* text content, if any */
    );
    public $element;

    /*
     * Create a simple element
     * @param string $name
     * @param string $content (optional)
     */
    public function __create ($name, $content = null) {
        $this->element = $this->baseElement ($name, $content);
    }

    /*
     * Import an element that has been created outside of this application
     */
    public function __import ($element) {
        $this->element = $element;
    }

    /*
     * Function to create a baseElement for use in this class (e.g. as a child element)
     */
    public function baseElement ($name, $content = null) {
        $element = $this->baseElement;
        $element['name'] = $name;
        if ($content === null) {
            $element['content'] = '';
        } else {
            $element['content'] = $content;
        }
        return $element;
    }

    /*
     * Function to create a baseAttribute for use in this class
     */
    public function baseAttribute ($name, $content = null) {
        $attribute = $this->baseAttribute;
        $attribute['name'] = $name;
        if ($content === null) {
            $attribute['value'] = $content;
        } else {
            $attribute['value'] = $content;
        }
        return $attribute;
    }

    /*
     * Function to add a child to a parent
     */
    public function addChild ($child) {
        if (isset ($this->element[$child['name']])) {
            array_push ($this->element[$child['name']], $child);
        } else {
            $this->element[$child['name']] = array ($child);
        }
        array_push ($this->element['__children'], $child['name']);
    }

    /*
     * Function to add a attribute
     */
    public function addAttribute ($attribute) {
        if (isset ($this->element['attributes'][$attribute['name']])) {
            array_push ($this->element['attributes'][$attribute['name']], $attribute);
        } else {
            $this->element['attributes'][$attribute['name']] = array ($attribute);
        }
        array_push ($this->element['__attributes'], $attribute['name']);
    }

    /*
     * Function to get the children of a single element
     */
    public function getChildren ($element = null) {
        if ($element == null) {
            $element = $this->element;
        }
        $children = array ();
        foreach ($element['__children'] as $name) {
            array_push ($children, $element[$name]);
        }
        return $children;
    }
}
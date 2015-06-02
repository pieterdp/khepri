<?php
/**
 * Created by PhpStorm.
 * User: pieter
 * Date: 7/05/15
 * Time: 14:36
 */

class lidoBase {

    private $xml;
    protected $target_ns;
    private $d;
    /**
     * Get all firstchild XML elements that are in a target namespace:
     * Loop over the entire tree (top->down) until we find an element in the target namespace
     * Get all elements that are on the same level; check if they are in the target namesapce
     * Return a DOMNodeList of all these elements
     * @param $xml
     * @param $target_ns
     */
    function __construct ($xml, $target_ns) {
        $this->xml = $xml;
        $this->target_ns = $target_ns;
        $this->d = new DOMDocument;
        $this->d->loadXML ($this->xml);
    }


    /**
     * @return array with stringified XML responses
     */
    public function unwrap_all () {
        $r = $this->unwrap ($this->d);
        array_walk ($r, array ($this, 'asString'));
        return $r;
    }

    protected function unwrap ($node) {

        /*
         * $r // Can have multiple siblings
         * foreach $nodes as $node
         *      if check_namespace = true: $r.push ($node)
         *      else
         *          $r = unwrap ($node)
         * return $r
         */
        $r = array ();
        $c = $node->childNodes;
        for ($i = 0; $i < $c->length; $i++) {
            $childNode = $c->item ($i);
            if ($this->check_namespace ($childNode) == true) {
                array_push ($r, $childNode);
            } else {
                $r = array_merge ($r, $this->unwrap ($childNode));
            }
        }
        return $r;
    }

    protected function check_namespace ($node) {
        /*
         * It seems that $node->prefix doesn't always work
         */
        $prefix = $node->prefix;
        if ($node->prefix == null) {
            $p = explode (':', $node->nodeName, 2);
            $prefix = $p[0];
        }
        if ($prefix == $this->target_ns) {
            return true;
        }
        return false;
    }

    protected function asString (&$node) {
        $node = $this->d->saveXML ($node);
    }
}
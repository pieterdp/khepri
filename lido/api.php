<?php
/**
 * Created by PhpStorm.
 * User: pieter
 * Date: 6/05/15
 * Time: 15:46
 */

include_once ('model/api.php');


$url = 'http://collections.britishart.yale.edu/oaicatmuseum/OAIHandler?verb=GetRecord&identifier=oai:tms.ycba.yale.edu:499&metadataPrefix=lido';

$a = new api ();
foreach ($a->response['headers'] as $header) {
    header ($header);
}
echo $a->response['content'];
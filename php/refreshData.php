<?php

/* $url="http://lit-beach-4706.herokuapp.com/api/v1.0/cac";
$postdata = http_build_query($_GET);
$opts = array('http' =>  array(
			       'method'  => 'POST',
			       'header'  => 'Content-type: application/x-www-form-urlencoded',
			       'content' => $postdata
			       )
	      );
$context  = stream_context_create($opts);
$res = file_get_contents($url, false, $context);
*/

$url = "http://lit-beach-4706.herokuapp.com/api/v1.1/cac";
$qs = $_SERVER['QUERY_STRING'];
$url = "$url?$qs";
$res = file_get_contents($url);
echo $res;
?>
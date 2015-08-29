<?php

require('routeros_api.class.php');

$API = new routeros_api();

$API->debug = true;

if ($API->connect('111.111.111.111', 'LOGIN', 'PASSWORD')) {

   $API->write('/interface/getall');

   $READ = $API->read();
   $ARRAY = $API->parse_response($READ);

   print_r($ARRAY);

   $API->disconnect();

}

?>
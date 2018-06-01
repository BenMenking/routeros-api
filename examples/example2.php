<?php

require('../routeros_api.class.php');
require('config.php');

$API = new RouterosAPI();

$API->debug = true;

if ($API->connect($router_ip, $username, $password)) {

   $API->write('/interface/wireless/registration-table/print',false);
   $API->write('=stats=');
 
   $READ = $API->read(false);
   $ARRAY = $API->parseResponse($READ);

   print_r($ARRAY);

   $API->disconnect();

}

?>

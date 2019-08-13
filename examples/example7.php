<?php

/* Example for adding a multiline script */

require('../routeros_api.class.php');

$API = new RouterosAPI();

$API->debug = true;

if ($API->connect('111.111.111.111', 'LOGIN', 'PASSWORD')) {

   $API->comm("/system/script/add", array(
      "name"     => "myscript",
      "source" => ":put line1;
:put line2;",
   ));

   $API->disconnect();

}

?>

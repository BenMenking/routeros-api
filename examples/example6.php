<?php

/* 3 step action 
   1) fetch all static dns hosts
   2) remove all static dns hosts
   3) add example host
*/

require('../routeros_api.class.php');
$API = new RouterosAPI();

if ($API->connect('111.111.111.111', 'LOGIN', 'PASSWORD')) {
   # Get all current hosts
   $API->write('/ip/dns/static/print');
   $ips = $API->read();

   # delete them all !
   foreach($ips as $num => $ip_data) {
     $API->write('/ip/dns/static/remove', false);
     $API->write("=.id=" . $ip_data[".id"], true);
   }

  #add some new
   $API->comm("/ip/dns/static/add", array(
      "name"     => "jefkeklak",
      "address"  => "1.2.3.4",
      "ttl"      => "1m"
   ));

   #show me what you got
   $API->write('/ip/dns/static/print');
   $ips = $API->read();
   var_dump($ips);
   $API->disconnect();
}

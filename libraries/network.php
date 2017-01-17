<?php

    namespace xd_network;

    // ----------------------------------------------------------
        
function addressBelongsToNetwork($ip, $cidr)
{
   
    list ($net, $mask) = split("/", $cidr);
   
    $ip_mask = ~((1 << (32 - $mask)) - 1);

    $reference_net = ip2long($net) & $ip_mask;
      
   //print decbin($reference_net)."<br>";
      
    $resolved_net = ip2long($ip) & $ip_mask;

   //print decbin($resolved_net)."<br>";
      
    return ($resolved_net == $reference_net);
}//addressBelongsToNetwork

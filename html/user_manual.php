<?php

   /* 
      XDMoD User Manual (Entry Point)
   */
   
   require_once dirname(__FILE__).'/../configuration/linker.php';

   $user_manual_address = xd_utilities\getConfiguration('general', 'user_manual');
   
   $query_string = (!empty($_SERVER['QUERY_STRING'])) ? '?'.$_SERVER['QUERY_STRING']: '';
   
   header("Location: $user_manual_address$query_string");
   
?>

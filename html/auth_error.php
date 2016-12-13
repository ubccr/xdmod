<?php

	require_once dirname(__FILE__).'/../configuration/linker.php';
	
	$email_tech_support = xd_utilities\getConfiguration('general', 'tech_support_recipient');
	
?>

<html>
   
   <head>
      
      <title>Access Denied</title>
      
      <style type="text/css">
      
         body {
            font-family: Arial;
            color: #f00;
            background-color: #fffcea; 
         }
         
         b {
            color: #000;
         }
         
      </style>
      
   </head>
   
   <body>
      
      <table width=100%>
         <tr><td align=right><a href="index.php"><img src="gui/images/xdmod_mini.png" border=0></a></td></tr>   
      </table>
      
      <center>
         Authentication to XSEDE has failed.<br /><br /><br />
         
         <a href="https://portal.xsede.org/web/xup/portal-password-reset">Click here if you need to reset your XSEDE User Portal Password</a><br /><br />
         
         If you do not have an XSEDE Portal Account, 
         <a href="https://portal.xsede.org/home?p_p_id=58&p_p_lifecycle=0&p_p_state=maximized&p_p_mode=view&p_p_col_id=column-2&p_p_col_count=1&_58_struts_action=%2flogin%2fcreate_account">create one now by clicking here.</a>
      

         
      </center>
   
   </body>
   
</html>
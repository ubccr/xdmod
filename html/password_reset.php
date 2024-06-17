<!DOCTYPE HTML>
<?php

   /*
      XDMoD Portal Password Reset Page
      The Center For Computational Research, University At Buffalo
   */

	require_once dirname(__FILE__).'/../configuration/linker.php';
	
	$page_title = xd_utilities\getConfiguration('general', 'title');
	$site_address = xd_utilities\getConfigurationUrlBase('general', 'site_address');

$rid = filter_input(INPUT_GET, 'rid', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => RESTRICTION_RID)));

if ($rid === false) {
    $validationCheck = array(
        'status' => INVALID,
        'user_first_name' => 'INVALID',
        'user_id' => INVALID
    );
} else {
    $validationCheck = XDUser::validateRID($rid);
}


	// -------------------------------
   
   if ($validationCheck['status'] == INVALID) {
   
?>
<html>

   <head>
				
      <link rel="shortcut icon" href="gui/icons/favicon_static.ico" />
					
      <style type="text/css">
			
         body, table {
         
            font-family: helvetica;
            font-size: 12px;
               
         }
					    
      </style>
		
      <title><?php print $page_title; ?></title>
				
   </head>
   
   <body bgcolor="#ffeeee">
				
      <table width=100%>
         <tr><td align=right><a href="index.php"><img src="gui/images/xdmod_mini.png" border=0></a></td></tr>   
      </table>
      
      <center>
      
         <br><br>
      
         <font color="#ff0000">The page you are trying to access has already expired.<br><br>
            If you still need to reset your password, visit the <a href="<?php print $site_address; ?>">login page</a> and click on <b>Problem Logging In?</b> below the login prompt.
         </font>
         
      </center>
					
   </body>
   
</html>		
<?php

      exit;

		}//if (INVALID)

      $first_name = $validationCheck['user_first_name'];
      
      $mode = ( isset($_GET['mode']) && ($_GET['mode'] == 'new') ) ? 'create' : 'reset';
      
      
      
?>
<html>

   <head>

      <meta charset="UTF-8" />

      <link rel="shortcut icon" href="gui/icons/favicon_static.ico" />
            
      <?php
         ExtJS::loadSupportScripts('gui/lib');
      ?>
	  
      <script type="text/javascript" src="gui/lib/jquery/jquery-3.7.1.min.js"></script>
      <script type="text/javascript" src="gui/lib/PasswordStrengthMeter.js"></script>
      
      <link rel="stylesheet" type="text/css" href="gui/css/PasswordReset.css">

      <!--[if IE]>
      <style type="text/css">
         
         .passStrength {
            padding-top: 2px;
         }

      </style>
      <![endif]-->

      <script language="JavaScript">
         var reset_id = <?php print json_encode($rid); ?>;
      </script>
      
      <script type="text/javascript" src="gui/js/PasswordReset.js"></script>

      <title><?php print "$page_title: ".ucfirst($mode); ?> Password</title>

	</head>

	<body onload="initPage()">

      <table width=100%>
      
         <tr>
            <td align=left><img src="gui/images/<?php print $mode; ?>_password.png"></td>
            <td align=right><a href="index.php"><img src="gui/images/xdmod_mini.png" border=0></a></td>
         </tr>   
         
      </table>
               
      <center>
		
         <br><br>
         Welcome, <?php print $first_name; ?>. To <?php print $mode; ?> your password, supply a new password below and click on <b>Update</b>.<br><br>
		
         <div class="splashContainer loginSection">
                  
            <div>
               <table border=0 width=100%>
               
                  <tr><td align="center" colspan=3 style="padding-bottom: 7px"><b><?php print ucwords($mode); ?> Your Password</b></td></tr>  
                  
                  <tr>
                     <td align="left" width=100>Password:</td>
                     <td align="left">
                        <input type="password" class="customTextField" id="updated_password" maxlength="<?php print CHARLIM_PASSWORD; ?>" onfocus="loginNav(this, event)" onkeyup="loginNav(this, event)">
                     </td>
                     <td align="left"><i><span id="stat_updated_password" class="fieldRestrictions">5 characters min.</span></i></td>
                  </tr>
                  
                  <tr>
                     <td align="left">&nbsp</td>
                     <td align="left">
                        <table border=0 style="padding: 0px !important"><tr>
                           <td valign="middle"><img src="gui/images/lock.png"></td>
                           <td valign="middle"><div class="passStrength" id="strengthIndicator">password not specified</div></td>
                        </tr></table>
                     </td>
                  </tr>                        
                  <tr>
                     <td align="left" width=100>Password Again:</td>
                     <td align="left">
                        <input type="password" class="customTextField" id="updated_password_repeat" maxlength="<?php print CHARLIM_PASSWORD; ?>" onkeyup="loginNav(this, event)">
                     </td>
                     <td align="left"><i><span id="stat_updated_password_repeat" class="fieldRestrictions">5 characters min.</span></i></td>
                  </tr>
                  
                  <tr>
                     <td colspan="3" align="center" style="padding-top: 8px">
                        <input type="button" class="customButton" value="Update" onClick="performReset()">
                     </td>
                  </tr>
                  
               </table>
            </div>        
                
         </div>    
          
		</center>

   </body>

</html>

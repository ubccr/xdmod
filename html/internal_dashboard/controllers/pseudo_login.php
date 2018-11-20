<?php

   require_once(dirname(__FILE__).'/../../../configuration/linker.php');

   use CCR\DB;
   
   session_start();
   xd_security\enforceUserRequirements(array(STATUS_LOGGED_IN, STATUS_MANAGER_ROLE), 'xdDashboardUser');

   // ======================================================================
   
function getAbsoluteURL()
{
    $protocol = 'http://';
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $protocol = 'https://';
    }
    return $protocol.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'];
}
   
   // ======================================================================
      
   if (isset($_REQUEST['uid'])) {

      $user_to_login_as = $_REQUEST['uid'];

      $user = XDUser::getUserById($user_to_login_as);

      if (!XDUser::isAuthenticated($user)) {
         print "Unknown user id $user_to_login_as";
         exit;
      }

      $user->postLogin();

      $redirect_url = str_replace('internal_dashboard/controllers/pseudo_login.php', '', getAbsoluteURL());
   
      header("Location: $redirect_url");

      exit;

   }//if (uid set)

?>

<html>

   <head>

      <style type="text/css">

         body {

            font-family: arial;
            font-size: 12px;

         }

      </style>        

   </head>

   <body>

   <?php

      $pdo = DB::factory('database');

      $result = $pdo->query("SELECT id, username, first_name, last_name FROM moddb.Users ORDER BY last_name");

      print "<table border=0 cellpadding=2 cellspacing=0>";
      print "<tr bgcolor=\"#f4da95\"><td>Name</td><td>Username</td><td>&nbsp;</td></tr>\n";

      $rIndex = 0;

      foreach ($result as $r) {

         $bgColor = ($rIndex++ % 2 == 0) ? '#eef' : '#fff';
         
         $formal_name = $r['last_name'].', '.$r['first_name'];
         $username = $r['username'];
         
         if (strpos($username, ';') !== false) {
         
            list($xsede_username, $dummy) = explode(';', $username);
            $username = $xsede_username." (XSEDE)";
         
         }
         
         $user_id = $r['id'];
         $login_link = "<a target=\"_blank\" href=\"?uid=$user_id\">Login as this user</a>";
         
         print "<tr bgcolor=\"$bgColor\"><td width=200>";
         print implode('</td><td width=200>', array($formal_name, $username, $login_link));
         print "</td></tr>\n";

      }//foreach 

      print "</table>";

   ?>

   </body>

</html>

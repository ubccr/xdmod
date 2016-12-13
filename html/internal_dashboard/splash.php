<html>

   <head>

      <link rel="stylesheet" type="text/css" href="css/splash.css" />

      <title>XDMoD Internal Dashboard</title>

      <script language="javascript">

         function init() {

             document.getElementById('field_username').focus();
             var directTo = document.getElementById('direct_to');
             directTo.value = window.location.href;
         }

         function keyListener(e) {
             if (e.keyCode == 13) {
                 document.getElementById('field_password').focus();
                 return false;
             }
         }


      </script>

   </head>

   <body onload="init()">

      <center>

         <?php
            if (isset($reject_response)){
               print '<span style="color: #f00">'.$reject_response.'</span>';
            }
         ?>

         <br><br>
         <img src="images/masthead_splash.png"><br><br>

         <form method="POST" action="index.php">

               <table border=0 width=300 height=100 cellpadding=5>

                  <tr><td align="center" colspan=3 style="padding-bottom: 7px"><b>Please Sign In Below</b></td></tr>

                  <tr>
                     <td align="left" width=100>Username:</td>
                     <td align="left">
                        <input type="text" class="customTextField" id="field_username" onkeypress="return keyListener(event)" name="xdmod_username">
                     </td>
                  </tr>

                  <tr>
                     <td align="left" width=100>Password:</td>
                     <td align="left">
                        <input type="password" class="customTextField" id="field_password" name="xdmod_password">
                     </td>

                  </tr>

                  <tr>
                     <td colspan="3" align="center" style="padding-top: 4px;">
                        <input type="submit" class="customButton" value="Log In">
                     </td>
                  </tr>

               </table>
               <input id='direct_to' type="hidden" name="direct_to" value="<?php print $referer; ?>" />


         </form>

      </center>

   </body>
</html>

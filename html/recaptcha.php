<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
<?php 

   require_once dirname(__FILE__).'/../configuration/linker.php';

   if (\xd_utilities\getConfiguration('mailer', 'captcha_public_key') !== '') {
      echo recaptcha_get_html(\xd_utilities\getConfiguration('mailer', 'captcha_public_key'), null, true);   
   }

?>
</body>
</html>

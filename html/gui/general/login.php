<?php
require_once __DIR__ . '/../../../configuration/linker.php';
@session_start();
$formal_name = isset($_REQUEST['xd_user_formal_name']) ? $_REQUEST['xd_user_formal_name'] :  "";
$samlError = false;
$auth = null;
$message = '';

try {
    $auth = new Authentication\SAML\XDSamlAuthentication();
} catch (InvalidArgumentException $ex) {
    // This will catch when apache or nginx have been set up
    // to to have an alternate saml configuration directory
    // that does not exist, so we ignore it as saml isnt set
    // up and we dont have to do anything with it
}
try {
    if ($auth && $auth->isSamlConfigured()) {
        $xdmodUser = $auth->getXdmodAccount();
        if ($xdmodUser->getAccountStatus()) {
            $formal_name = $xdmodUser->getFormalName();
            $xdmodUser->postLogin();
        } else {
            $message = 'Your account is currently inactive, please contact an administrator.';
        }
    }
} catch (Exception $e) {
    $message = $e->getMessage();
}
// Used for Single Sign On  or samlErrors
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style type="text/css">
      body, table {
          font-family: arial;
          font-size: 18px;
          color: #00f;
          background-color: #e8e8e8;
      }
      .login_message {
          color: #55f;
          font-size: 14px;
      }
      .error_message{
        color: #f00;
        text-align: center;
      }
  </style>
  <script type="text/javascript">
    function loadPortal() {
      setTimeout(function(){
        parent.location.href = '/index.php' + document.location.hash;
      }, 1500);
    }

    function contactAdmin() {
        parent.location.href = '/index.php#main_tab_panel:tools:contact_us';
    }
  </script>
</head>
    <?php

    if (!empty($message)) {
    ?>
      <body class="error_message">
        <p>
            <?php
            echo $message;
            ?>
            <br>
            <a href="javascript:contactAdmin()">Contact a system administrator.</a>
        </p>
      </body>
      </html>
    <?php
    } else {
    ?>
<body onload="loadPortal()">
  <center>
    <table border=0 width=100% height=100%>
        <tr>
            <td colspan=2 align="center">
              <p>
                Welcome, <?php print htmlentities($formal_name); ?>
              </p>
              <p>
                <img src="/gui/images/progbar.gif" />
              </p>
              <p class="login_message">
                Logging you into XDMoD
              </p>
            </td>
        </tr>
    </table>
  </center>
</body>
</html>

<?php
    }

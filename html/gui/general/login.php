<?php
require_once __DIR__ . '/../../../configuration/linker.php';

$formal_name = isset($_REQUEST['xd_user_formal_name']) ? $_REQUEST['xd_user_formal_name'] :  "";
$samlError = false;
$auth = null;
try {
    $auth = new Authentication\SAML\XDSamlAuthentication();
} catch (InvalidArgumentException $ex) {
 // This will catch when a configuration directory does not exist if it is set in the environment level
}
if ($auth && $auth->isSamlConfigured()) {
    $xdmodUser = $auth->getXdmodAccount();
    if ($xdmodUser && $xdmodUser != "EXCEPTION" && $xdmodUser != "ERROR" && $xdmodUser != "EXISTS" && $xdmodUser != "AMBIGUOUS") {
        if ($xdmodUser->getAccountStatus()) {
            \XDSessionManager::recordLogin($xdmodUser);
            $formal_name = $xdmodUser->getFormalName();
        } else {
            $samlError = "INACTIVE";
        }
    } else {
        $samlError = $xdmodUser;
    }
}
// Used for Federated login or samlErrors
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
        parent.location.href = '/index.php';
      }, 2000);
    }
  </script>
</head>
    <?php
    if ($samlError) {
        $message = "";
        switch ($samlError) {
            case "ERROR":
                $message = "There was an error with your account; an administrator has been notified.";
                break;
            case "INACTIVE":
                $message = "Your account is not currently active, please contact an administrator.";
                break;
            case "EXISTS":
                $message = "An account is currently configured with this information, please contact an administrator.";
                break;
            case "AMBIGUOUS":
                $message = "Multiple users configured with the same information, please contact an administrator.";
                break;
            default:
                $message = "An unknown error has occured.";
        }
    ?>
      <body class="error_message" onload="loadPortal()">
        <p>
            <?php
            echo $message;
            ?>
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
                Welcome, <?php print $formal_name; ?>
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

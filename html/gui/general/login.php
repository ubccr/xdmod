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
            print($xdmodUser);
            exit();
            $samlError = $xdmodUser ? $xdmodUser : "INACTIVE";
        }
    } else {
        $samlError = $xdmodUser ? $xdmodUser : null;
    }
}
// Used for Federated login or samlErrors
if (!empty($formal_name) || $samlError) {

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
                $message = "Your account is not currently active.";
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
      <body class="error_message">
        <p>
            <?php
            echo $message;
            ?>
        </p>
        <p>
          <a href="javascript:void(0);" onclick="parent.presentContactFormViaLoginError()">Click Here</a> to contact an administrator.
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
    exit();
}
$xsedeLogin = xd_utilities\getConfiguration('features', 'xsede') == 'on';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style type="text/css">
        body, td {
            font-family: Arial;
            font-size: 11px;
            background-color: #e8e8e8;
            overflow: hidden;
        }
        .xdmod_login td {
            background-color: #eee;
        }
        .signup_16 {
            background-image: url('/gui/images/signup_16.png') !important;
        }
        .form_container {
            border: 1px solid #bbb;
            background-color: #eee;
        }
    </style>

    <!--[if IE]>
    <style type="text/css">
        .ie_container {
            padding: 5px;
        }
        .ie_container_xsede {
            padding: 3px;
        }
    </style>
    <![endif]-->

    <?php

    ExtJS::loadSupportScripts('../lib');

    ?>

    <script type="text/javascript" src="../js/CCR.js"></script>
    <script type="text/javascript" src="../js/RESTProxy.js"></script>

    <script type="text/javascript">

        <?php if ($xsedeLogin) : ?>
        function launchXSEDEPrompt() {

            parent.XDMoD.TrackEvent('Login Window', 'Clicked on Login With XSEDE button');

            window.open('../../oauth/entrypoint.php', 'windowname2',
                'width=850, \
                height=420, \
                directories=no, \
                location=no, \
                menubar=no, \
                resizable=no, \
                scrollbars=0, \
                status=no, \
                toolbar=no');

            return false;

        }//launchXSEDEPrompt

        // ------------------------------------------------

        function postXSEDELoginProcedure(wChild, formalName, token) {

            parent.XDMoD.TrackEvent('Login Window', 'Successful login using XSEDE credentials', formalName + ' (Token: ' + token + ')');

            var public_token = parent.XDMoD.REST.token;
            parent.XDMoD.REST.token = token;

            parent.XDMoD.TrackEvent('Login Window', 'Login from public session', '(Token: ' + public_token + ')', true);

            location.href = '../gui/general/login.php?xd_user_formal_name=' + formalName;

            wChild.close();

        }//postXSEDELoginProcedure

        // ------------------------------------------------

        function checkXDUsername(username) {

            if (username)
                parent.XDMoD.TrackEvent('Login Window', 'Checked XSEDE account for username', 'username: ' + username);

            userCheck.location.href = '../../oauth/user_check.php?username=' + username;

        }//checkXDUsername

        // ------------------------------------------------
        <?php endif; ?>
        // ------------------------------------------------

        var txtEmailAddress;
        var txtLoginUsername, txtLoginPassword;

        function processReset() {

            var objParams = {
                operation: 'pass_reset',
                email: txtEmailAddress.getValue()
            };

            var conn = new Ext.data.Connection;
            conn.request({

                url: '/controllers/user_auth.php',
                params: objParams,
                method: 'POST',
                callback: function (options, success, response) {

                    if (success) {

                        var json = Ext.util.JSON.decode(response.responseText);

                        switch (json.status) {

                            case 'invalid_email_address':

                                presentLoginResponse('A valid e-mail address must be specified.', false, "reset_response");

                                break;

                            case 'no_user_mapping':

                                presentLoginResponse('No XDMoD user could be associated with this e-mail address.', false, "reset_response");

                                break;

                            case 'multiple_accounts_mapped':

                                presentLoginResponse('Multiple XDMoD accounts are associated with this e-mail address.', false, "reset_response");

                                break;

                            case 'success':

                                presentLoginResponse('Password reset instructions have been sent to this e-mail address.', true, "reset_response");

                                break;

                        }//switch(json.status)

                    }
                    else {
                        presentLoginResponse('There was a problem connecting to the portal service provider.', false, "reset_response");
                    }

                    txtEmailAddress.focus();

                }//callback

            });

        }//processReset

        // ------------------------------------------------

        var presentOverlay = function (status, message, customdelay, cb) {

            var delay = customdelay ? customdelay : 2000;
            var section;

            var cStatus = (status == true) ? '#080' : '#f00';

            parent.getEl().mask('<div class="overlay_message" style="color:' + cStatus + '">' + message + '</div>');

        }//presentOverlay

        // ------------------------------------------------

        function switchView(viewToPresent) {

            var oldView = 'panel_account_reset';
            var focusField = txtLoginUsername;

            var header = document.getElementById('right_section_header');
            var subheader = document.getElementById('right_section_subheader');

            if (viewToPresent == 'panel_account_reset') {

                parent.XDMoD.TrackEvent('Login Window', 'Clicked on trouble logging in link');

                focusField = txtEmailAddress;
                oldView = 'panel_xdmod_login';

                header.innerHTML = 'Trouble Logging In?';
                subheader.innerHTML = 'Reset your password';

            }
            else {

                parent.XDMoD.TrackEvent('Login Window', 'Clicked on Return to login link');

                header.innerHTML = 'Have an XDMoD account?';
                subheader.innerHTML = 'Sign in with your local XDMoD account';

            }

            document.getElementById(oldView).style.visibility = 'hidden';
            document.getElementById(viewToPresent).style.visibility = 'visible';

            focusField.focus();

        }//switchView

        // ------------------------------------------------


        function processLogin() {

            if (txtLoginUsername.getValue().length == 0) {

                presentLoginResponse('You must specify a username.', false, "login_response", function () {
                    txtLoginUsername.focus();
                });

                return;

            }

            if (txtLoginPassword.getValue().length == 0) {

                presentLoginResponse('You must specify a password.', false, "login_response", function () {
                    txtLoginPassword.focus();
                });

                return;

            }

            var restArgs = {
                'username': txtLoginUsername.getValue(),
                'password': txtLoginPassword.getValue()
            };

            Ext.Ajax.request({
                url: '/rest/v0.1/auth/login',
                method: 'POST',
                params: restArgs,
                callback: function (options, success, response) {
                    var data = CCR.safelyDecodeJSONResponse(response);
                    if (success) {
                        success = CCR.checkDecodedJSONResponseSuccess(data);
                    }

                    if (success) {
                        XDMoD.TrackEvent('Login Window', 'Successful login', txtLoginUsername.getValue());

                        XDMoD.REST.token = data.results.token;
                        XDMoD.TrackEvent('Login Window', 'Login from public session', '(Token: ' + XDMoD.REST.token + ')', true);

                        presentLoginResponse('Welcome, ' + Ext.util.Format.htmlEncode(data.results.name) + '.', true, "login_response");

                        var token = CCR.tokenize(parent.XDMoD.referer);

                        if (token && token.tab && parent.CCR.xdmod.tabs.indexOf(token.tab) >= 0) {
                            parent.location.href = '../../index.php' + token.raw;
                            parent.location.hash = token.raw;
                        }
                        else{
                            parent.location.href = '../../index.php';
                            parent.location.hash = '';
                        }
                        parent.location.reload();
                    } else {
                        XDMoD.TrackEvent('Login Window', 'Successful login', txtLoginUsername.getValue());
                        var message = data.message || 'There was an error encountered while logging in. Please try again.';
                        message = Ext.util.Format.htmlEncode(message);
                        message = message.replace(
                            CCR.xdmod.support_email,
                            '<br /><a href="mailto:' + CCR.xdmod.support_email + '?subject=Problem Logging In">' + CCR.xdmod.support_email + '</a>'
                        );

                        presentLoginResponse(
                            message,
                            false,
                            "login_response",
                            function () {
                                txtLoginPassword.focus(true);
                            }
                        );//presentLoginResponse
                    } // if(success)
                }
            });
        }//processLogin

        // ------------------------------------------------

        function presentSignUp() {

            parent.presentSignUpViaLoginPrompt();

        }//presentSignUp

        // ------------------------------------------------

        Ext.onReady(function () {

            <?php if ($xsedeLogin) : ?>
            var txtXDUsername = new Ext.form.TextField({
                emptyText: 'XSEDE Username',
                width: 125,
                renderTo: 'txt_xsede_username'
            });

            new Ext.Button({

                text: 'Check',
                width: 60,
                handler: function () {
                    parent.XDMoD.TrackEvent('Login Window', 'Clicked on Check button');
                    checkXDUsername(txtXDUsername.getValue());
                },
                renderTo: 'btn_check_xsede_account'

            });
            <?php endif; ?>

            new Ext.Button({

                text: 'Sign In',
                width: 80,
                handler: function () {
                    parent.XDMoD.TrackEvent('Login Window', 'Clicked on Sign In button');
                    processLogin();
                },
                renderTo: 'btn_sign_in'

            });


            txtLoginUsername = new Ext.form.TextField({
                renderTo: 'txt_login_username',
                width: 184,
                enableKeyEvents: true,
                listeners: {
                    'keydown': function (a, e) {
                        if (e.getCharCode() == 13) txtLoginPassword.focus();
                    },
                    'keyup': function (a, e) {
                        var currentValue = a.getValue();
                        if (a.prevValue !== currentValue) {
                            clearLoginResponse("login_response");
                            a.prevValue = currentValue;
                        }
                    }
                }
            });

            txtLoginUsername.focus();

            txtLoginPassword = new Ext.form.TextField({
                inputType: 'password',
                renderTo: 'txt_login_password',
                width: 184,
                enableKeyEvents: true,
                listeners: {
                    'keydown': function (a, e) {
                        if (e.getCharCode() == 13) processLogin();
                    },
                    'keyup': function (a, e) {
                        var currentValue = a.getValue();
                        if (a.prevValue !== currentValue) {
                            clearLoginResponse("login_response");
                            a.prevValue = currentValue;
                        }
                    }
                }
            });

            new Ext.Panel({
                id: "login_response",
                renderTo: 'login_response_container',
                hidden: true,
                unstyled: true
            });

            new Ext.Button({

                text: 'Sign Up',
                width: 80,
                handler: function () {
                    presentSignUp();
                },

                iconCls: 'signup_16',
                renderTo: 'btn_sign_up'

            });


            txtEmailAddress = new Ext.form.TextField({
                renderTo: 'txt_email_address',
                width: 184,
                enableKeyEvents: true,
                listeners: {
                    'keydown': function (a, e) {
                        if (e.getCharCode() == 13) processReset();
                    },
                    'keyup': function (a, e) {
                        var currentValue = a.getValue();
                        if (a.prevValue !== currentValue) {
                            clearLoginResponse("reset_response");
                            a.prevValue = currentValue;
                        }
                    }
                }
            });

            new Ext.Panel({
                id: "reset_response",
                renderTo: 'reset_response_container',
                hidden: true,
                unstyled: true
            });

            new Ext.Button({

                text: 'Send E-Mail',
                width: 80,
                handler: function () {
                    parent.XDMoD.TrackEvent('Login Window', 'Clicked on Send E-Mail button');
                    processReset();
                },
                renderTo: 'btn_send_email'

            });

            <?php if ($xsedeLogin) : ?>
            var xsede_button_margin = '-2px';

            new Ext.Button({

                text: '<img src="btn_xsede_signin.png" style="margin-top: ' + xsede_button_margin + '">',
                height: 40,
                cls: 'xsede_button',
                handler: function () {
                    launchXSEDEPrompt();
                },
                renderTo: 'btn_xsede_login'

            });
            <?php endif; ?>

        });//Ext.onReady

    </script>

</head>

<body>

<table border=0 width=100% height=100%>
    <tr>
        <td style="vertical-align: top">

            <div class="centered_content">
                <table border=0 width=100% height=90%>
                    <tr>

                        <?php if ($xsedeLogin) : ?>
                            <td width=240 border=0>

                                <table border=0 width=100% height=100%>

                                    <tr>
                                        <td height=10>
                                            <b style="color: #000; font-size: 13px">Have an XSEDE account?</b>
                                            <br/><span style="color: #666">Click below to sign in with your XSEDE account</span>
                                        </td>
                                    </tr>


                                    <tr>
                                        <td align=center height=20>
                                            <div id="btn_xsede_login"
                                                 style="padding-top: 10px; margin-left: -3px"></div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td align=center height=20>

                                            <div style="padding-top: 10px">
                                                <table border=0 width=90%>
                                                    <tr>
                                                        <td valign=top><b>Note:</b></td>
                                                        <td>You must have an active XSEDE account to use this login
                                                            method.
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>

                                        </td>
                                    </tr>

                                    <tr>
                                        <td align=center>

                                            <div class="form_container ie_container_xsede"
                                                 style="width: 200px; margin-left: -3px">

                                                <table class="xdmod_login" width=200 border=0 style="padding: 3px">

                                                    <tr>
                                                        <td colspan=2 style="padding-bottom: 3px"><b>Check your XSEDE
                                                                account</b></td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <div id="txt_xsede_username"></div>
                                                        </td>
                                                        <td align=right>
                                                            <div id="btn_check_xsede_account"></div>
                                                        </td>
                                                    </tr>

                                                </table>

                                            </div>


                                            <div style="width: 200px;">
                                                <iframe id="userCheck" name="userCheck" width=95% height=30
                                                        frameborder="0" scrolling="no"
                                                        src="../../oauth/user_check.php?first_load=y"></iframe>
                                            </div>

                                        </td>
                                    </tr>

                                </table>

                            </td>

                            <td width=20 style="border: 1px solid #bbb; background-color: #ddd; padding: 4px;"><img
                                    src="or.png"></td>
                        <?php endif; ?>

                        <?php
                        if ($auth && $auth->isSamlConfigured()) {
                            $authLogin = $auth->getLoginLink();
                            //TODO:  Look into internationalization
                            $organization = htmlspecialchars($authLogin['organization']['en']);
                        ?>
                            <td style="width:240px;border:none;">

                                <table style="height:100%;width:100%;border:none;">

                                    <tr>
                                        <td style="height:10px;vertical-align:top;">
                                            <b style="color: #000; font-size: 13px">Have a(n) <?php echo $organization; ?> account?</b>
                                            <br/>
                                        <?php if ($authLogin['icon']) { ?>
                                            <span style="color: #666">Click below to sign in with your <?php echo $organization; ?> account</span>
                                        <?php }?>
                                        </td>
                                    </tr>


                                    <tr>
                                        <td style="height:20px;text-align:center;">
                                            <a href="<?php echo htmlspecialchars($authLogin['url']); ?>">
                                            <?php
                                            if ($authLogin['icon']) { ?>
                                                <img style="padding:5px; border: 1px solid black;" alt="<?php echo $organization; ?>" src="<?php echo $authLogin['icon']; ?>" />
                                            <?php
                                            } else {
                                            ?>
                                                Login with your <?php echo $organization; ?> account
                                            <?php
                                            }
                                            ?>
                                            </a>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td style="height:20px;text-align:center;">
                                            <b>Note:</b> if you are already logged into your <?php echo $organization; ?> account, you may not be prompted for relogin.
                                        </td>
                                    </tr>
                                </table>

                            </td>

                            <td width=20 style="border: 1px solid #bbb; background-color: #ddd; padding: 4px;"><img
                                    src="or.png"></td>
                        <?php
                        }
                        ?>

                        <td width=240 style="padding: 2px">

                            <table border=0 width=100% height=100%>

                                <tr>
                                    <td height=10>

                                        <span id="right_section_header"
                                              style="color: #000; font-size: 13px; font-weight: bold">Have an XDMoD account?</span><br/>
                                        <span id="right_section_subheader" style="color: #666">Sign in with your local XDMoD account</span>

                                    </td>
                                </tr>

                                <tr>
                                    <td align=center height=50 style="padding-top: 7px">


                                        <div id="panel_xdmod_login">

                                            <div class="form_container ie_container" style="width: 200px">
                                                <table class="xdmod_login" width=200 border=0 style="padding: 5px">

                                                    <tr>
                                                        <td>Username</td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <div id="txt_login_username"></div>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td>Password</td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <div id="txt_login_password"></div>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td>
                                                            <div id="login_response_container"></div>
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td colspan=2 align=right style="padding-top: 3px">
                                                            <div id="btn_sign_in"></div>
                                                        </td>
                                                    </tr>

                                                </table>
                                            </div>

                                            <div style="padding-top: 5px">
                                                <table border=0>
                                                    <tr>
                                                        <td style="padding-right: 4px">Don't have an account?</td>
                                                        <td>
                                                            <div id="btn_sign_up"></div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding-right: 4px; padding-top: 9px" colspan=2>
                                                            Trouble logging in? <a href="javascript:void(0)"
                                                                                   onClick="switchView('panel_account_reset')">Click
                                                                here</a></td>
                                                    </tr>
                                                </table>
                                            </div>

                                        </div>

                                        <!-- ======================================= -->

                                        <?php if ($xsedeLogin || ($auth && $auth->isSamlConfigured())) : ?>
                                        <div id="panel_account_reset"
                                             style="position: absolute; top: 66px; left: 286px; visibility: hidden">
                                            <?php else : ?>
                                            <div id="panel_account_reset"
                                                 style="position: absolute; top: 66px; left: 16px; visibility: hidden">
                                                <?php endif; ?>


                                                <table border=0 width=230>

                                                    <tr>
                                                        <td style="padding-left: 7px">

                                                            Supply the e-mail address associated with<br/>
                                                            your account. You will be emailed a link<br/>
                                                            that will allow you to reset your password.<br/><br/>

                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td align=center>

                                                            <div class="form_container ie_container"
                                                                 style="width: 200px">
                                                                <table class="xdmod_login" width=200 border=0
                                                                       style="padding: 5px">

                                                                    <tr>
                                                                        <td>E-Mail Address</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>
                                                                            <div id="txt_email_address"></div>
                                                                        </td>
                                                                    </tr>

                                                                    <tr>
                                                                        <td>
                                                                            <div id="reset_response_container"></div>
                                                                        </td>
                                                                    </tr>

                                                                    <tr>
                                                                        <td colspan=2 align=right
                                                                            style="padding-top: 3px">
                                                                            <div id="btn_send_email"></div>
                                                                        </td>
                                                                    </tr>

                                                                </table>
                                                            </div>

                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <td style="padding-top: 10px; padding-left: 7px">
                                                            <a href="javascript:void(0)"
                                                               onClick="switchView('panel_xdmod_login')">Return to
                                                                login</a>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>
</body>
</html>

<?php

/*
   XDMoD Portal Entry Point
   The Center For Computational Research, University At Buffalo
*/

use Models\Realm;
use Models\Services\Acls;
use Models\Services\Realms;

@session_start();

// Fix to the 'trailing slash' issue -------------------------------

// Get URL ------------

$port = ($_SERVER['SERVER_PORT'] >= 9000) ? ':' . $_SERVER['SERVER_PORT'] : '';
$proto = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';

$url = $proto . '://' . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
$referer = $url;
// --------------------

if (preg_match('/index.php(\/+)/i', $url)) {
    $properURI = preg_replace('/index.php(\/+)/i', 'index.php', $url);

    header("Location: $properURI");
    exit;
}

require_once dirname(__FILE__) . '/../configuration/linker.php';

$userLoggedIn = isset($_SESSION['xdUser']);
if ($userLoggedIn) {
    try {

        $user = \xd_security\getLoggedInUser();

    } catch (SessionExpiredException $see) {
        // TODO: Refactor generic catch block below to handle specific exceptions,
        //       which would allow this block to be removed.
        throw $see;
    } catch (Exception $e) {

        xd_web_message\displayMessage('There was a problem initializing your account.', $e->getMessage(), true);
        exit;

    }

    if (!isset($user) || !isset($_SESSION['session_token'])) {

        // There is an issue with the account (most likely deleted while the user was logged in, and the user refreshed the entire site)
        session_destroy();
        header("Location: index.php");
        exit;

    }
} else {
    function isReferrer($referrer)
    {
        if (isset($_SERVER['HTTP_REFERER'])) {

            $pos = strpos($_SERVER['HTTP_REFERER'], $referrer);

            if ($pos !== false && $pos == 0) {
                return true;
            }

        }//if (isset($_SERVER['HTTP_REFERER']))

        return false;
    }//isReferrer

    if (!isset($_SESSION['public_session_token'])) {
        $_SESSION['public_session_token'] = 'public-' . microtime(true) . '-' . uniqid();
    }

    $user = XDUser::getPublicUser();
}

$page_title = xd_utilities\getConfiguration('general', 'title');

// Set REST cookies.
\xd_rest\setCookies();

?>
<!DOCTYPE html>
<html>

<head lang="en">

    <!-- <meta http-equiv="X-UA-Compatible" content="IE=9" /> -->
    <meta charset="utf-8"/>

    <?php

    $meta_description = "XSEDE Metrics on Demand (XDMoD) is a comprehensive auditing framework for XSEDE, the follow-on to NSF's TeraGrid program.  " .
        "XDMoD provides detailed information on resource utilization and performance across all resource providers.";

    $meta_keywords = "xdmod, xsede, analytics, metrics on demand, hpc, visualization, statistics, reporting, auditing, nsf, resources, resource providers";

    ?>

    <meta name="description" content="<?php print $meta_description; ?>"/>
    <meta name="keywords" content="<?php print $meta_keywords; ?>">

    <title><?php print $page_title; ?></title>

    <link rel="shortcut icon" href="gui/icons/favicon_static.ico"/>
    <?php if (!$userLoggedIn): ?>
        <script type="text/javascript">
            /**
             This is so that we can capture the URL that we came in  on.
             **/
            var XDMoD = XDMoD || {};
            XDMoD.referer = document.location.hash || 'main_tab_panel#tg_summary' // <-- TODO: HORRIBLE HORRIBLE HACK, FIX THIS;
            console.log(XDMoD.referer);
        </script>
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" href="gui/lib/extjs/resources/css/ext-all.css">
    <link rel="stylesheet" type="text/css" href="gui/lib/extjs/resources/css/xtheme-gray.css">
    <script type="text/javascript" src="libs.js"></script>

    <link rel="stylesheet" type="text/css" href="gui/css/viewer.css">

    <script type="text/javascript">
        <?php \xd_rest\printJavascriptVariables(); ?>
    </script>

    <link rel="stylesheet" type="text/css" href="gui/css/MultiSelect.css"/>
    <link rel="stylesheet" type="text/css" href="gui/lib/extjs/examples/ux/css/Spinner.css"/>
    <?php if ($userLoggedIn): ?>
        <link rel="stylesheet" type="text/css" href="gui/lib/extjs/examples/ux/css/LockingGridView.css"/>
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" href="gui/css/aboutus.css"/>
    <?php if ($userLoggedIn): ?>
        <link rel="stylesheet" type="text/css" href="../gui/css/GroupTab.css"/>
    <?php endif; ?>


    <link rel="stylesheet" type="text/css" href="gui/css/MetricExplorer.css"/>
    <link rel="stylesheet" type="text/css" href="gui/css/common.css"/>
    <!--[if lte IE 9]>
    <link rel="stylesheet" type="text/css" href="gui/css/common_ie9.css"/>
    <![endif]-->
    <!--[if lte IE 8]>
    <link rel="stylesheet" type="text/css" href="gui/css/common_ie8.css"/>
    <![endif]-->
    <?php if (!$userLoggedIn): ?>
        <link rel="stylesheet" type="text/css" href="gui/css/LoginPrompt.css"/>
    <?php endif; ?>

    <?php
    $realms = array_reduce(Realms::getRealms(), function ($carry, Realm $item) {
        $carry [] = $item->getName();
        return $carry;
    }, array());
    ?>

    <script type='text/javascript'>

        <?php
        print "Ext.namespace('CCR.xdmod.ui');\n";
        print "CCR.xdmod.publicUser = " . json_encode(!$userLoggedIn) . ";\n";

        $tech_support_recipient = xd_utilities\getConfiguration('general', 'tech_support_recipient');
        print "CCR.xdmod.tech_support_recipient = CCR.xdmod.support_email = '$tech_support_recipient';\n";

        print "CCR.xdmod.version = '" . xd_versioning\getPortalVersion() . "';\n";
        print "CCR.xdmod.short_version = '" . xd_versioning\getPortalVersion(true) . "';\n";

        $username = $userLoggedIn ? $user->getUsername() : '__public__';
        print "CCR.xdmod.ui.username = '$username';\n";
        if ($userLoggedIn) {
            print "CCR.xdmod.ui.fullName = " . json_encode($user->getFormalName()) . ";\n";
            $userType = $user->getUserType();
            print "CCR.xdmod.ui.usertype = '$userType';\n";
            print "CCR.xdmod.ui.userIsSSO = " . json_encode(($userType === SSO_USER_TYPE)) . ";\n";
            print "CCR.xdmod.ui.mappedPID = '{$user->getPersonID(TRUE)}';\n";

            $obj_warehouse = new XDWarehouse();
            print 'CCR.xdmod.ui.mappedPName = ' . json_encode($obj_warehouse->resolveName($user->getPersonID(true))) . ";\n";

            print "CCR.xdmod.ui.isManager = " . json_encode($user->isManager()) . ";\n";
            print "CCR.xdmod.ui.isDeveloper = " . json_encode($user->isDeveloper()) . ";\n";
            print "CCR.xdmod.ui.isCenterDirector = " . json_encode($user->hasAcl(ROLE_ID_CENTER_DIRECTOR)) . ";\n";
        }

        $configFile = new \Configuration\XdmodConfiguration(
            'rawstatistics.json',
            CONFIG_DIR,
            null,
            array(
                    'local_config_dir' => implode(DIRECTORY_SEPARATOR, array(CONFIG_DIR, 'rawstatistics.d'))
            )
        );
        $configFile->initialize();

        $config = json_decode($configFile->toJson(), true);
        $rawDataRealms = array_keys($config['realms']);

        print "CCR.xdmod.ui.rawDataAllowedRealms = " . json_encode($rawDataRealms) . ";\n";

        print "CCR.xdmod.ui.disabledMenus = " . json_encode(Acls::getDisabledMenus($user, $realms)) . ";\n";

        if ($userLoggedIn) {
            print "CCR.xdmod.ui.allRoles = " . json_encode($user->enumAllAvailableRoles()) . "\n";
        }

        print "CCR.xdmod.org_name = " . json_encode(ORGANIZATION_NAME) . ";\n";
        print "CCR.xdmod.org_abbrev = " . json_encode(ORGANIZATION_NAME_ABBREV) . ";\n";

        print "CCR.xdmod.logged_in = !CCR.xdmod.publicUser;\n";
        $captchaSiteKey = '';
        try {
            if (!$userLoggedIn) {
                $captchaSiteKey = xd_utilities\getConfiguration('mailer', 'captcha_public_key');
                $captchaSecret = xd_utilities\getConfiguration('mailer', 'captcha_private_key');
                if ('' === $captchaSiteKey || '' === $captchaSecret) {
                    $captchaSiteKey = '';
                }
            }
        } catch (exception $ex) {
        }
        print "CCR.xdmod.captcha_sitekey = '" . $captchaSiteKey . "';\n";
        if (!$userLoggedIn) {
            $auth = null;
            try {
                $auth = new Authentication\SAML\XDSamlAuthentication();
            } catch (InvalidArgumentException $ex) {
                // This will catch when a configuration directory does not exist if it is set in the environment level
            }
            if ($auth && $auth->isSamlConfigured()) {
                $ssoShowLocalLogin = false;
                try {
                    $ssoShowLocalLogin = filter_var(
                        xd_utilities\getConfiguration('sso', 'show_local_login'),
                        FILTER_VALIDATE_BOOLEAN
                    );
                } catch (exception $ex) {
                }

                print "CCR.xdmod.isSSOConfigured = true;\n";
                print "CCR.xdmod.SSOLoginLink = " . json_encode($auth->getLoginLink()) . ";\n";
                print "CCR.xdmod.SSOShowLocalLogin = " . json_encode($ssoShowLocalLogin) . "\n";
            } else {
                print "CCR.xdmod.isSSOConfigured = false;\n";
            }
        }
        if ($userLoggedIn) {
            print "CCR.xdmod.ui.colors = " . COLORS . ";\n";
        }

        $features = xd_utilities\getConfigurationSection('features');
        // Convert array values to boolean
        array_walk($features, function (&$v) {
            $v = ($v == 'on');
        });

        print "CCR.xdmod.features = " . json_encode($features) . ";\n";
        ?>

    </script>

    <script type="text/javascript" src="app.js"></script>

    <?php if ($userLoggedIn): ?>
        <!-- Profile Editor -->

        <link rel="stylesheet" type="text/css" href="gui/css/ProfileEditor.css"/>
    <?php endif; ?>

    <!-- Reporting  -->

    <?php if ($userLoggedIn): ?>
        <link rel="stylesheet" type="text/css" href="gui/css/ChartDateEditor.css"/>
        <link rel="stylesheet" type="text/css" href="gui/css/ReportManager.css"/>
        <link rel="stylesheet" type="text/css" href="gui/css/AvailableCharts.css"/>
    <?php endif; ?>

    <?php if ($userLoggedIn): ?>
        <link rel="stylesheet" type="text/css" href="gui/css/ChartDragDrop.css"/>
    <?php endif; ?>

    <?php if ($userLoggedIn): ?>
        <link rel="stylesheet" type="text/css" href="gui/css/TreeCheckbox.css"/>
        <link rel="stylesheet" type="text/css" href="gui/css/TriStateNodeUI.css"/>
    <?php endif; ?>

    <?php if (!$userLoggedIn): ?>
        <script type="text/javascript" src="gui/js/SignUpDialog.js"></script>
    <?php endif; ?>

    <?php /* Single Job Viewer */ ?>
    <?php if (!empty($rawDataRealms)): ?>
        <script type="text/javascript" src="gui/js/modules/job_viewer/JobViewer.js"></script>
        <script type="text/javascript" src="gui/js/modules/job_viewer/ChartPanel.js"></script>
        <script type="text/javascript" src="gui/js/modules/job_viewer/ChartTab.js"></script>
        <script type="text/javascript" src="gui/js/modules/job_viewer/GanttChart.js"></script>
        <script type="text/javascript" src="gui/js/modules/job_viewer/AnalyticChartPanel.js"></script>
        <script type="text/javascript" src="gui/js/modules/job_viewer/TimeSeriesStore.js"></script>
        <script type="text/javascript" src="gui/js/modules/job_viewer/SearchPanel.js"></script>
        <script type="text/javascript" src="gui/js/modules/job_viewer/SearchHistoryTree.js"></script>
        <script type="text/javascript" src="gui/js/modules/job_viewer/SearchHistoryPanel.js"></script>
        <script type="text/javascript" src="gui/js/modules/job_viewer/JobPanel.js"></script>
        <script type="text/javascript" src="gui/js/modules/job_viewer/NestedViewPanel.js"></script>
        <script type="text/javascript" src="gui/lib/rsvp/rsvp-1979d5ad89293dadbe7656dd53d152f7426fa35e.min.js"></script>
        <script type="text/javascript" src="gui/lib/groupdataview.js"></script>
        <script type="text/javascript" src="gui/lib/groupcombo.js"></script>
    <?php endif; ?>

    <?php
    xd_utilities\checkForCenterLogo();
    ?>

    <?php
    require_once dirname(__FILE__) . '/gaq.php';
    ?>

    <?php echo \OpenXdmod\Assets::generateAssetTags('portal'); ?>

    <script type="text/javascript">

        var xsedeProfilePrompt = function () {
        };

        <?php if (!$userLoggedIn): ?>
        Ext.onReady(xdmodviewer.init, xdmodviewer);
        <?php else: ?>
        <?php
        // ==============================================

        $profile_editor_init_flag = '';
        $usersFirstLogin = ($user->getCreationTimestamp() == $user->getLastLoginTimestamp());

        // If the user logging in is an XSEDE/Single Sign On user, they may or may not have
        // an e-mail address set. The logic below assists in presenting the Profile Editor
        // with the appropriate (initial) view
        $userEmail = $user->getEmailAddress();
        $userEmailSpecified = ($userEmail != NO_EMAIL_ADDRESS_SET && !empty($userEmail));
        if ($user->isSSOUser() == true || $usersFirstLogin) {

            // NOTE: $_SESSION['suppress_profile_autoload'] will be set only upon update of the user's profile (see respective REST call)

            if ($usersFirstLogin && $userEmailSpecified && (!isset($_SESSION['suppress_profile_autoload']) && $user->getUserType() != 50)) {
                // If the user is logging in for the first time and does have an e-mail address set
                // (due to it being specified in the XDcDB), welcome the user and inform them they
                // have an opportunity to update their e-mail address.

                $profile_editor_init_flag = 'XDMoD.ProfileEditorConstants.WELCOME_EMAIL_CHANGE';

            } elseif ($usersFirstLogin && !$userEmailSpecified) {
                // If the user is logging in for the first time and does *not* have an e-mail address set,
                // welcome the user and inform them that he/she needs to set an e-mail address.

                $profile_editor_init_flag = 'XDMoD.ProfileEditorConstants.WELCOME_EMAIL_NEEDED';

            }
        }
        if (!$userEmailSpecified) {
            // Regardless of whether the user is logging in for the first time or not, the lack of
            // an e-mail address requires attention
            $profile_editor_init_flag = 'XDMoD.ProfileEditorConstants.EMAIL_NEEDED';
        }
        // ==============================================

        if (!empty($profile_editor_init_flag)) {

        ?>

        xsedeProfilePrompt = function () {

            (function () {

                var profileEditor = new XDMoD.ProfileEditor();
                profileEditor.init();

            }).defer(1000);

        };

        <?php
        } ?>
        <?php endif; ?>

    </script>

    <?php if ($userLoggedIn): ?>
        <script type="text/javascript">Ext.onReady(xdmodviewer.init, xdmodviewer);</script>
    <?php endif; ?>
    <?php if (strlen($captchaSiteKey) > 0): ?>
        <script src="https://www.google.com/recaptcha/api.js?render=explicit"></script>
    <?php endif; ?>
</head>

<body>

<!-- Fields required for history management -->
<form id="history-form" class="x-hidden">
    <input type="hidden" id="x-history-field"/>
    <iframe id="x-history-frame"></iframe>
</form>

<div id="viewer"></div>

<noscript>
    <?php xd_web_message\displayMessage('XDMoD requires JavaScript, which is currently disabled in your browser.'); ?>
</noscript>

<?php if (!$userLoggedIn): ?>
    <br/><br/><br/><br/><br/>
    <input type="hidden" id="direct_to"/>
<?php endif; ?>
</body>

</html>

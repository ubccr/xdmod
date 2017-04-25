<?php

   /*
      XDMoD Portal Entry Point
      The Center For Computational Research, University At Buffalo
   */

   @session_start();

   // Fix to the 'trailing slash' issue -------------------------------

   // Get URL ------------

   $port = ($_SERVER['SERVER_PORT'] >= 9000) ? ':'.$_SERVER['SERVER_PORT'] : '';
   $proto = (!empty($_SERVER['HTTPS'])) ? 'https' : 'http';

   $url = $proto . '://'.$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI'];
   $referer = $url;
   // --------------------

   if (preg_match('/index.php(\/+)/i', $url)) {
      $properURI = preg_replace('/index.php(\/+)/i', 'index.php', $url);

      header("Location: $properURI");
      exit;
   }

   require_once dirname(__FILE__).'/../configuration/linker.php';

   $userLoggedIn = isset($_SESSION['xdUser']);
   if ($userLoggedIn) {
      try {

         $user = \xd_security\getLoggedInUser();

      }
      catch (SessionExpiredException $see) {
         // TODO: Refactor generic catch block below to handle specific exceptions,
         //       which would allow this block to be removed.
         throw $see;
      }
      catch(Exception $e) {

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

      if (isReferrer('https://go.teragrid.org') || isReferrer('https://portal.xsede.org')) {
         // If someone clicks on the 'Cancel' button when consulting the oAuth login UI, it would normally
         // redirect that person to the xdmod main page.  The logic below inhibits this.

         header('location: oauth/entrypoint.php');
         exit;
      }

      if (!isset($_SESSION['public_session_token'])) {
         $_SESSION['public_session_token'] = 'public-'.microtime(true).'-'.uniqid();
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
      <meta charset="utf-8" />

      <?php

         $meta_description = "XSEDE Metrics on Demand (XDMoD) is a comprehensive auditing framework for XSEDE, the follow-on to NSF's TeraGrid program.  " .
                             "XDMoD provides detailed information on resource utilization and performance across all resource providers.";

         $meta_keywords = "xdmod, xsede, analytics, metrics on demand, hpc, visualization, statistics, reporting, auditing, nsf, resources, resource providers";

      ?>

      <meta name="description" content="<?php print $meta_description; ?>" />
      <meta name="keywords" content="<?php print $meta_keywords; ?>">

      <title><?php print $page_title; ?></title>

      <link rel="shortcut icon" href="gui/icons/favicon_static.ico" />
      <script type="text/javascript" src="gui/lib/oldie-console-patch.js"></script>
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
      <?php
         ExtJS::loadSupportScripts('gui/lib');
      ?>
       <script type="text/javascript" src="gui/lib/ext-oldie-history-patch.js"></script>
       <script type="text/javascript" src="gui/lib/jquery/jquery-1.12.4.min.js"></script>
       <?php if ($userLoggedIn): ?>
       <script type="text/javascript" src="gui/lib/jquery-plugins/base64/jquery.base64.js"></script>
       <?php endif; ?>
        <script type="text/javascript">
         <?php if ($userLoggedIn): ?>
         if (!window.btoa) { window.btoa = $.base64.encode }
         if (!window.atob) { window.atob = $.base64.decode }
         <?php endif; ?>
        jQuery.noConflict();
        </script>

      <link rel="stylesheet" type="text/css" href="gui/css/viewer.css">

      <script type="text/javascript" src="gui/lib/debug.js"></script>

      <?php if ($userLoggedIn): ?>
      <script type="text/javascript" src="gui/lib/RowExpander.js"></script>
      <?php endif; ?>

      <!-- Non-GUI JS Class Definitions -->
      <script type="text/javascript" src="js_classes/DateUtilities.js"></script>
      <?php if ($userLoggedIn): ?>
      <script type="text/javascript" src="js_classes/StringUtilities.js"></script>
      <?php endif; ?>

      <!-- Globals -->
      <script type="text/javascript" src="gui/js/Error.js.php"></script>
      <script type="text/javascript" src="gui/js/globals.js"></script>
      <script type="text/javascript" src="gui/js/StringExtensions.js"></script>
      <!-- Plugins -->
      <script type="text/javascript" src="gui/js/plugins/ContextSensitiveHelper.js"></script>
      <script type="text/javascript" src="gui/js/plugins/CollapsedPanelTitlePlugin.js"></script>

      <!-- Libraries -->
      <script type="text/javascript" src="gui/js/libraries/utilities.js"></script>

      <script type="text/javascript" src="gui/js/SessionManager.js"></script>

      <!-- RESTProxy -->

      <script type="text/javascript" src="gui/js/RESTProxy.js"></script>
      <script type="text/javascript">
         <?php \xd_rest\printJavascriptVariables(); ?>
      </script>
      <script type="text/javascript" src="gui/js/REST.js"></script>

      <link rel="stylesheet" type="text/css" href="gui/css/MultiSelect.css"/>
      <link rel="stylesheet" type="text/css" href="gui/lib/extjs/examples/ux/css/Spinner.css" />
      <?php if ($userLoggedIn): ?>
      <link rel="stylesheet" type="text/css" href="gui/lib/extjs/examples/ux/css/LockingGridView.css" />
      <?php endif; ?>
      <link rel="stylesheet" type="text/css" href="gui/css/aboutus.css" />
      <?php if ($userLoggedIn): ?>
      <link rel="stylesheet" type="text/css" href="../gui/css/GroupTab.css"/>
      <?php endif; ?>

      <?php if ($userLoggedIn): ?>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/LockingGridView.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/ProgressBarPager.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/treegrid/TreeGridSorter.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/treegrid/TreeGridColumnResizer.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/treegrid/TreeGridNodeUI.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/treegrid/TreeGridLoader.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/treegrid/TreeGridColumns.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/treegrid/TreeGrid.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/GroupTabPanel.js"></script>
      <script type="text/javascript" src="../gui/lib/extjs/examples/ux/GroupTab.js"></script>
      <?php endif; ?>

      <script type="text/javascript" src="gui/lib/MultiSelect.js"></script>
      <script type="text/javascript" src="gui/lib/ItemSelector.js"></script>

      <script type="text/javascript" src="gui/lib/NumberFormat.js"></script>
      <script type="text/javascript" src="gui/js/multiline-tree-nodes.js"></script>

      <script type="text/javascript" src="gui/lib/MessageWindow.js"></script>

      <script type="text/javascript" src="gui/js/CCR.js"></script>
      <script type="text/javascript" src="gui/js/RESTDataProxy.js"></script>
      <script type="text/javascript" src="gui/js/CustomHttpProxy.js"></script>

      <script type="text/javascript" src="gui/lib/printer/Printer-all.js"></script>

      <script type="text/javascript" src="gui/js/TGUserDropDown.js"></script>

      <script language="JavaScript" src="gui/js/login.js.php"></script>
      <?php if ($userLoggedIn): ?>
      <script type="text/javascript" src="gui/js/LoginPrompt.js"></script>
      <?php endif; ?>

      <script type="text/javascript" src="gui/lib/CheckColumn.js"></script>

      <script type="text/javascript" src="gui/js/ContainerMask.js"></script>
       <script type="text/javascript" src="gui/js/ContainerBodyMask.js"></script>

      <link rel="stylesheet" type="text/css" href="gui/css/common.css" />
      <!--[if lte IE 9]>
      <link rel="stylesheet" type="text/css" href="gui/css/common_ie9.css" />
      <![endif]-->
      <!--[if lte IE 8]>
      <link rel="stylesheet" type="text/css" href="gui/css/common_ie8.css" />
      <![endif]-->
      <?php if (!$userLoggedIn): ?>
      <link rel="stylesheet" type="text/css" href="gui/css/LoginPrompt.css" />
      <?php endif; ?>

      <?php

         $manager = $user->isManager() ? 'true' : 'false';
         $developer = $user->isDeveloper() ? 'true' : 'false';

         $primary_center_director = (
               ($user->getActiveRole()->getIdentifier() == ROLE_ID_CENTER_DIRECTOR) &&
               true //($user->getPromoter(ROLE_ID_CENTER_DIRECTOR, $user->getActiveRole()->getActiveCenter()) == -1)
         ) ? 'true' : 'false';

      ?>

      <script type='text/javascript'>

         <?php
            print "CCR.xdmod.publicUser = " . ($userLoggedIn ? 'false' : 'true') . ";\n";

            $tech_support_recipient = xd_utilities\getConfiguration('general', 'tech_support_recipient');
            print "CCR.xdmod.tech_support_recipient = CCR.xdmod.support_email = '$tech_support_recipient';\n";

            print "CCR.xdmod.version = '".xd_versioning\getPortalVersion()."';\n";
            print "CCR.xdmod.short_version = '".xd_versioning\getPortalVersion(true)."';\n";

            $username = $userLoggedIn ? $user->getUsername() : '__public__';
            print "CCR.xdmod.ui.username = '$username';\n";
            if ($userLoggedIn) {
               print "CCR.xdmod.ui.fullName = " . json_encode($user->getFormalName()) . ";\n";
               $userType = $user->getUserType();
               print "CCR.xdmod.ui.usertype = '$userType';\n";
               $userIsFederated = ($userType === FEDERATED_USER_TYPE || $userType === XSEDE_USER_TYPE) ? "true" : "false";
               print "CCR.xdmod.ui.userIsFederated = $userIsFederated;\n";
               print "CCR.xdmod.ui.mappedPID = '{$user->getPersonID(TRUE)}';\n";

               $obj_warehouse = new XDWarehouse();
               print "CCR.xdmod.ui.mappedPName = '{$obj_warehouse->resolveName($user->getPersonID(TRUE))}';\n";

               print "CCR.xdmod.ui.isManager = $manager;\n";
               print "CCR.xdmod.ui.isDeveloper = $developer;\n";
               print "CCR.xdmod.ui.isCenterDirector = $primary_center_director;\n";

               print "CCR.xdmod.ui.active_role_label = '{$user->getActiveRole()->getFormalName()}';\n";
            }

            print "CCR.xdmod.ui.disabledMenus = ".json_encode($user->getDisabledMenus(
                array_keys($user->getActiveRole()->getAllQueryRealms('tg_usage'))
            )).";\n";

            if ($userLoggedIn) {
               print "CCR.xdmod.ui.allRoles = ".json_encode($user->enumAllAvailableRoles())."\n";

               print "CCR.xdmod.ui.activeRole = '".$user->getActiveRole()->getIdentifier(true)."';\n";
            }

            print "CCR.xdmod.org_name = ".json_encode(ORGANIZATION_NAME).";\n";
            print "CCR.xdmod.org_abbrev = ".json_encode(ORGANIZATION_NAME_ABBREV).";\n";

            print "CCR.xdmod.logged_in = !CCR.xdmod.publicUser;\n";
            $useCaptcha = 'false';
            try {
              $useCaptcha = xd_utilities\getConfiguration('mailer', 'captcha_private_key') !== '' ? 'true' : 'false';
            }
            catch(exception $ex) {
              print "console.warn(\"" . $ex->getMessage() . "\");\n";
            }
            print "CCR.xdmod.use_captcha = " . $useCaptcha .";";
            if (!$userLoggedIn) {
               $tabs = "";
               try {
                  $tabs =  xd_utilities\getConfiguration('auto_login', 'tabs');
               } catch (exception $ex) {
                  print "console.warn(\"" . $ex->getMessage() . "\");\n";
               }
               print "CCR.xdmod.tabs ='". $tabs ."';\n";
               $auth = null;
               try {
                  $auth = new Authentication\SAML\XDSamlAuthentication();
               } catch (InvalidArgumentException $ex) {
                  // This will catch when a configuration directory does not exist if it is set in the environment level
               }
               if ($auth && $auth->isSamlConfigured()) {
                  print "CCR.xdmod.isFederationConfigured = true;";
               } else {
                  print "CCR.xdmod.isFederationConfigured = false;";
               }
            }
            if ($userLoggedIn) {
               print "CCR.xdmod.ui.colors = ".COLORS.";\n";
            }

            $features = xd_utilities\getConfigurationSection('features');
            // Convert array values to boolean
            array_walk($features, function(&$v) { $v = ($v == 'on'); } );

            print "CCR.xdmod.features = ".json_encode($features).";\n";
         ?>

      </script>

      <?php if ($userLoggedIn): ?>
      <!-- Profile Editor -->

      <link rel="stylesheet" type="text/css" href="gui/css/ProfileEditor.css" />
      <script type="text/javascript" src="gui/js/profile_editor/ProfileGeneralSettings.js"></script>
      <script type="text/javascript" src="gui/js/profile_editor/ProfileRoleDelegation.js"></script>
      <script type="text/javascript" src="gui/js/profile_editor/ProfileEditor.js"></script>
      <?php endif; ?>

      <!-- Data Warehouse -->
      <script type="text/javascript" src="gui/js/common/data_warehouse/AddFilterWindow.js"></script>
      <script type="text/javascript" src="gui/js/common/data_warehouse/FilterStore.js"></script>
      <script type="text/javascript" src="gui/js/common/data_warehouse/QuickFilterStore.js"></script>
      <script type="text/javascript" src="gui/js/common/data_warehouse/QuickFilterButton.js"></script>
      <script type="text/javascript" src="gui/js/common/data_warehouse/QuickFilterToolbar.js"></script>

      <!-- Reporting  -->

      <?php if ($userLoggedIn): ?>
      <link rel="stylesheet" type="text/css" href="gui/css/ChartDateEditor.css" />
      <link rel="stylesheet" type="text/css" href="gui/css/ReportManager.css" />
      <link rel="stylesheet" type="text/css" href="gui/css/AvailableCharts.css" />
      <?php endif; ?>

      <?php if ($userLoggedIn): ?>
      <script type="text/javascript" src="gui/js/report_builder/ChartThumbPreview.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportExportMenu.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportCloneMenu.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ChartDateEditor.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/Reporting.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportManager.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/AvailableCharts.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/SaveReportAsDialog.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportCreatorGrid.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportCreator.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportsOverview.js"></script>
      <script type="text/javascript" src="gui/js/report_builder/ReportPreview.js"></script>
      <?php endif; ?>

      <?php if ($userLoggedIn): ?>
      <script type="text/javascript" src="gui/lib/moment/moment.min.js"></script>
      <script type="text/javascript" src="gui/lib/moment-timezone/moment-timezone-with-data.min.js"></script>
      <?php endif; ?>

      <script type="text/javascript" src="gui/lib/highcharts/js/highcharts.src.js"></script>
      <script type="text/javascript" src="gui/lib/highcharts/js/highcharts-more.js"></script>
      <script type="text/javascript" src="gui/lib/highchartsDateformats.src.js"></script>
      <?php if ($userLoggedIn): ?>
      <script type="text/javascript" src="gui/lib/highchartsChartClicks.src.js"></script>
      <?php endif; ?>
      <script type="text/javascript" src="gui/lib/highchartsDottedLineNullPlot.src.js"></script>
      <script type="text/javascript" src="gui/lib/highcharts/js/modules/exporting.src.js"></script>

      <script type="text/javascript" src="gui/js/HighChartPanel.js"></script>

      <?php if ($userLoggedIn): ?>
      <link rel="stylesheet" type="text/css" href="gui/css/ChartDragDrop.css" />
      <?php endif; ?>

      <script type="text/javascript" src="gui/js/CustomJsonStore.js"></script>
      <script type="text/javascript" src="gui/lib/Portal.js"></script>
      <script type="text/javascript" src="gui/lib/PortalColumn.js"></script>
      <script type="text/javascript" src="gui/lib/Portlet.js"></script>

      <?php if ($userLoggedIn): ?>
      <link rel="stylesheet" type="text/css" href="gui/css/TreeCheckbox.css" />
      <link rel="stylesheet" type="text/css" href="gui/css/TriStateNodeUI.css" />

      <script type="text/javascript" src="gui/js/TreeCheckbox.js"></script>
      <script type="text/javascript" src="gui/js/TriStateNodeUI.js"></script>
      <?php endif; ?>

      <script type="text/javascript" src="gui/js/RESTTree.js"></script>
      <script type="text/javascript" src="gui/js/BufferView.js"></script>
      <script type="text/javascript" src="gui/lib/Spinner.js"></script>
      <script type="text/javascript" src="gui/lib/SpinnerField.js"></script>
      <script type="text/javascript" src="gui/js/CustomCheckItem.js"></script>
      <script type="text/javascript" src="gui/js/CustomDateField.js"></script>
      <script type="text/javascript" src="gui/js/CustomSplitButton.js"></script>
      <?php if ($userLoggedIn): ?>
      <script type="text/javascript" src="gui/js/CustomTwinTriggerField.js"></script>
      <?php endif; ?>
      <script type="text/javascript" src="gui/js/CustomRowNumberer.js"></script>
      <script type="text/javascript" src="gui/js/CustomPagingToolbar.js"></script>
      <script type="text/javascript" src="gui/js/DynamicGridPanel.js"></script>
      <script type="text/javascript" src="gui/js/DurationToolbar.js"></script>
      <script type="text/javascript" src="gui/js/ChartConfigMenu.js"></script>
      <script type="text/javascript" src="gui/js/ChartToolbar.js"></script>
      <script type="text/javascript" src="gui/js/DrillDownMenu.js"></script>

      <script type="text/javascript" src="gui/js/ChartDragDrop.js"></script>
      <script type="text/javascript" src="gui/lib/extjs/examples/ux/DataView-more.js"></script>
      <script type="text/javascript" src="gui/js/FilterDimensionPanel.js"></script>

      <?php if ($userLoggedIn): ?>
      <script type="text/javascript" src="gui/js/CustomMenu.js"></script>
      <script type="text/javascript" src="gui/js/AddDataPanel.js"></script>
      <?php endif; ?>

      <script type="text/javascript" src="gui/js/ExportPanel.js"></script>

      <script type="text/javascript" src="gui/js/CaptchaField.js"></script>
      <?php if (!$userLoggedIn): ?>
      <script type="text/javascript" src="gui/js/SignUpDialog.js"></script>
      <?php endif; ?>
      <script type="text/javascript" src="gui/js/ContactDialog.js"></script>
      <?php if ($userLoggedIn): ?>
      <script type="text/javascript" src="gui/js/RealTimeValidatingTextField.js"></script>
      <?php endif; ?>

      <script type="text/javascript" src="gui/js/PortalModule.js"></script>

      <?php /* Modules used by both XSEDE and Open XDMoD. */ ?>

      <script type="text/javascript" src="gui/js/modules/Summary.js"></script>
      <script type="text/javascript" src="gui/js/modules/Usage.js"></script>
      <?php if ($userLoggedIn): ?>
      <script type="text/javascript" src="gui/js/modules/ReportGenerator.js"></script>
      <?php endif; ?>
      <script type="text/javascript" src="gui/js/modules/About.js"></script>

      <?php if ($userLoggedIn): ?>
      <script type="text/javascript" src="gui/js/modules/metric_explorer/MetricExplorer.js"></script>
      <script type="text/javascript" src="gui/js/modules/metric_explorer/StatusButton.js"></script>
      <script type="text/javascript" src="gui/js/ChangeStack.js"></script>

      <?php /* Single Job Viewer */ ?>
      <?php if (xd_utilities\getConfiguration('features', 'singlejobviewer') == 'on'): ?>
      <script type="text/javascript" src="gui/js/modules/job_viewer/JobViewer.js"></script>
      <script type="text/javascript" src="gui/js/modules/job_viewer/ChartPanel.js"></script>
      <script type="text/javascript" src="gui/js/modules/job_viewer/AnalyticChartPanel.js"></script>
      <script type="text/javascript" src="gui/js/modules/job_viewer/TimeSeriesStore.js"></script>
      <script type="text/javascript" src="gui/js/modules/job_viewer/SearchPanel.js"></script>
      <script type="text/javascript" src="gui/js/modules/job_viewer/SearchHistoryTree.js" ></script>
      <script type="text/javascript" src="gui/js/modules/job_viewer/SearchHistoryPanel.js"></script>
      <script type="text/javascript" src="gui/js/modules/job_viewer/JobPanel.js"></script>
      <script type="text/javascript" src="gui/js/modules/job_viewer/NestedViewPanel.js"></script>
      <script type="text/javascript" src="gui/lib/rsvp/rsvp-1979d5ad89293dadbe7656dd53d152f7426fa35e.min.js"></script>
      <script type="text/javascript" src="gui/lib/groupdataview.js"></script>
      <script type="text/javascript" src="gui/lib/groupcombo.js"></script>
      <?php endif; ?>
      <?php endif; ?>

      <?php
         xd_utilities\checkForCenterLogo();
      ?>

      <script type="text/javascript" src="gui/js/Viewer.js"></script>

      <?php
         require_once dirname(__FILE__).'/gaq.php';
      ?>

      <?php echo \OpenXdmod\Assets::generateAssetTags('portal'); ?>

      <script type="text/javascript">

         var xsedeProfilePrompt = function() {};

         <?php if (!$userLoggedIn): ?>
         Ext.onReady(xdmodviewer.init, xdmodviewer);

         Ext.onReady(function() {
            CCR.xdmod.tabs = CCR.xdmod.tabs.split(',');
            var hash = document.location.hash;
            var hasHash = hash !== null && hash !== undefined && hash !== '';
            if (hasHash) {
               var token = CCR.xdmod.ui.Viewer.viewerInstance.tokenize(hash);
               if (token && token.tab && CCR.xdmod.tabs.indexOf(token.tab) >= 0) {
                  CCR.xdmod.ui.actionLogin();
               }
            }
         });
         <?php else: ?>
         <?php
            // ==============================================

            $profile_editor_init_flag = '';
            $usersFirstLogin = ($user->getCreationTimestamp() == $user->getLastLoginTimestamp());

            // If the user logging in is an XSEDE/Federated user, he/she may or may not have
            // an e-mail address set. The logic below assists in presenting the Profile Editor
            // with the appropriate (initial) view
            $userEmail = $user->getEmailAddress();
            $userEmailSpecified = ($userEmail != NO_EMAIL_ADDRESS_SET && !empty($userEmail));
            if ($user->isXSEDEUser() == true || $usersFirstLogin) {

               // NOTE: $_SESSION['suppress_profile_autoload'] will be set only upon update of the user's profile (see respective REST call)

               if ($usersFirstLogin && $userEmailSpecified && (!isset($_SESSION['suppress_profile_autoload']) && $user->getUserType() != 50) ) {
                  // If the user is logging in for the first time and does have an e-mail address set
                  // (due to it being specified in the XDcDB), welcome the user and inform them they
                  // have an opportunity to update their e-mail address.

                  $profile_editor_init_flag = 'XDMoD.ProfileEditorConstants.WELCOME_EMAIL_CHANGE';

               }
               elseif ($usersFirstLogin && !$userEmailSpecified) {
                  // If the user is logging in for the first time and does *not* have an e-mail address set,
                  // welcome the user and inform them that he/she needs to set an e-mail address.

                  $profile_editor_init_flag = 'XDMoD.ProfileEditorConstants.WELCOME_EMAIL_NEEDED';

               }
            }
            if(!$userEmailSpecified) {
               // Regardless of whether the user is logging in for the first time or not, the lack of
               // an e-mail address requires attention
               $profile_editor_init_flag = 'XDMoD.ProfileEditorConstants.EMAIL_NEEDED';
            }
            // ==============================================

            if (!empty($profile_editor_init_flag)) {

         ?>

            xsedeProfilePrompt = function() {

               (function() {

                  var profileEditor = new XDMoD.ProfileEditor();
                  profileEditor.init();

               }).defer(1000);

            };

         <?php } ?>
         <?php endif; ?>

      </script>

      <?php if ($userLoggedIn): ?>
      <script type="text/javascript">Ext.onReady(xdmodviewer.init, xdmodviewer);</script>
      <?php endif; ?>

   </head>

   <body>

      <!-- Fields required for history management -->
      <form id="history-form" class="x-hidden">
         <input type="hidden" id="x-history-field" />
         <iframe id="x-history-frame"></iframe>
      </form>

      <div id="viewer"> </div>

      <noscript>
         <?php xd_web_message\displayMessage('XDMoD requires JavaScript, which is currently disabled in your browser.'); ?>
      </noscript>

      <?php if (!$userLoggedIn): ?>
      <br /><br /><br /><br /><br />
      <input type="hidden" id="direct_to" />
      <?php endif; ?>
   </body>

</html>

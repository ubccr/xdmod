<?php

require_once __DIR__ . '/../../configuration/linker.php';
require_once 'user_check.php';

if (isset($_POST['direct_to'])) {
  header('Location: ' . $_POST['direct_to']);
  exit;
}

// Set REST cookies.
\xd_rest\setCookies();

?>
<!DOCTYPE html>
<html>
<head>

  <?php
  if(preg_match('/(?i)msie [1-8]/',$_SERVER['HTTP_USER_AGENT']))
  {
    echo "<meta http-equiv=\"X-UA-Compatible\" content=\"IE=EmulateIE7\" />"."\n";
  }
  ?>

  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title>XDMoD Internal Dashboard</title>
  <link rel="icon" href="../favicon.ico" />

  <link rel="stylesheet" type="text/css" href="css/dashboard.css">
  <link rel="stylesheet" type="text/css" href="css/management.css">
  <link rel="stylesheet" type="text/css" href="css/AdminPanel.css" />
  <script type="text/javascript" src="../gui/lib/oldie-console-patch.js"></script>
  <script type="text/javascript" src="../gui/lib/oldie-array-methods-patch.js"></script>
  <script type="text/javascript" src="../gui/lib/ie-object-values-polyfill.js"></script>
  <?php ExtJS::loadSupportScripts('../gui/lib'); ?>
  <script type="text/javascript" src="../gui/lib/ext-oldie-history-patch.js"></script>
  <script type="text/javascript" src="../gui/lib/jquery/jquery-1.12.4.min.js"></script>

  <script type="text/javascript">
    jQuery.noConflict();
  </script>

  <link rel="stylesheet" type="text/css" href="../gui/css/viewer.css">

  <!-- Non-GUI JS Class Definitions -->

  <script type="text/javascript" src="../js_classes/DateUtilities.js"></script>
  <script type="text/javascript" src="../js_classes/StringUtilities.js"></script>
  <script type="text/javascript" src="js/messaging.js"></script>
  <script type="text/javascript" src="js/DashboardStore.js"></script>
  <script type="text/javascript" src="../gui/lib/MessageWindow.js"></script>
  <script type="text/javascript" src="../gui/js/CCR.js"></script>
  <script type="text/javascript" src="../gui/js/ContainerMask.js"></script>
  <script type="text/javascript" src="../gui/js/TGUserDropDown.js"></script>
  <script type="text/javascript" src="../gui/js/InstitutionDropDown.js"></script>
  <script type="text/javascript" src="../gui/lib/CheckColumn.js"></script>

  <script type="text/javascript">
    var dashboard_user_full_name = <?php echo json_encode($user->getFormalName()); ?>;
  </script>

  <!-- Globals -->

  <script type="text/javascript" src="../gui/js/Error.js.php"></script>
  <script type="text/javascript" src="../gui/js/globals.js"></script>
  <script type="text/javascript" src="../gui/js/StringExtensions.js"></script>

  <!-- Plugins -->

  <script type="text/javascript" src="../gui/js/plugins/ContextSensitiveHelper.js"></script>
  <script type="text/javascript" src="../gui/js/plugins/CollapsedPanelTitlePlugin.js"></script>

  <!-- Libraries -->

  <script type="text/javascript" src="../gui/js/libraries/utilities.js"></script>

  <script type="text/javascript" src="../gui/js/SessionManager.js"></script>


  <!-- RESTProxy -->

  <script type="text/javascript" src="../gui/js/RESTProxy.js"></script>

  <script type="text/javascript">
      <?php \xd_rest\printJavascriptVariables(); ?>
  </script>
  <script type="text/javascript" src="../gui/js/REST.js"></script>

  <link rel="stylesheet" type="text/css" href="../gui/css/MultiSelect.css"/>
  <link rel="stylesheet" type="text/css" href="../gui/lib/extjs/examples/ux/css/Spinner.css" />
  <link rel="stylesheet" type="text/css" href="../gui/lib/extjs/examples/ux/css/LockingGridView.css" />

  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/LockingGridView.js"></script>

  <script type="text/javascript" src="../gui/lib/MultiSelect.js"></script>
  <script type="text/javascript" src="../gui/lib/ItemSelector.js"></script>

  <script type="text/javascript" src="../gui/lib/NumberFormat.js"></script>
  <script type="text/javascript" src="../gui/js/multiline-tree-nodes.js"></script>

  <script type="text/javascript" src="../gui/lib/printer/Printer-all.js"></script>

  <script type="text/javascript" src="../gui/js/LoginPrompt.js"></script>

  <script type="text/javascript" src="../gui/js/ContainerBodyMask.js"></script>

  <link rel="stylesheet" type="text/css" href="../gui/css/common.css" />
  <!--[if lte IE 9]>
  <link rel="stylesheet" type="text/css" href="../gui/css/common_ie9.css" />
  <![endif]-->
  <!--[if lte IE 8]>
  <link rel="stylesheet" type="text/css" href="../gui/css/common_ie8.css" />
  <![endif]-->

  <script type="text/javascript" src="../gui/lib/highcharts/js/highcharts.src.js"></script>
  <script type="text/javascript" src="../gui/lib/highcharts/js/highcharts-more.js"></script>
  <script type="text/javascript" src="../gui/lib/highchartsDateformats.src.js"></script>

  <script type="text/javascript" src="../gui/js/HighChartPanel.js"></script>

  <link rel="stylesheet" type="text/css" href="../gui/css/ChartDragDrop.css" />

  <script type="text/javascript" src="../gui/js/CustomJsonStore.js"></script>
  <script type="text/javascript" src="../gui/lib/Portal.js"></script>
  <script type="text/javascript" src="../gui/lib/PortalColumn.js"></script>
  <script type="text/javascript" src="../gui/lib/Portlet.js"></script>

  <script type="text/javascript" src="../gui/js/RESTTree.js"></script>
  <script type="text/javascript" src="../gui/js/BufferView.js"></script>
  <script type="text/javascript" src="../gui/lib/Spinner.js"></script>
  <script type="text/javascript" src="../gui/lib/SpinnerField.js"></script>
  <script type="text/javascript" src="../gui/js/CustomCheckItem.js"></script>
  <script type="text/javascript" src="../gui/js/CustomDateField.js"></script>
  <script type="text/javascript" src="../gui/js/CustomSplitButton.js"></script>
  <script type="text/javascript" src="../gui/js/CustomRowNumberer.js"></script>
  <script type="text/javascript" src="../gui/js/DynamicGridPanel.js"></script>
  <script type="text/javascript" src="../gui/js/DurationToolbar.js"></script>
  <script type="text/javascript" src="../gui/js/ChartConfigMenu.js"></script>
  <script type="text/javascript" src="../gui/js/ChartToolbar.js"></script>
  <script type="text/javascript" src="../gui/js/DrillDownMenu.js"></script>
  <script type="text/javascript" src="../gui/js/ChartDragDrop.js"></script>
  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/DataView-more.js"></script>
  <script type="text/javascript" src="../gui/js/FilterDimensionPanel.js"></script>

  <script type="text/javascript" src="../gui/js/CustomMenu.js"></script>
  <script type="text/javascript" src="../gui/js/AddDataPanel.js"></script>

  <script type="text/javascript" src="../gui/js/Viewer.js"></script>

  <script type="text/javascript" src="../gui/lib/RowExpander.js"></script>

  <!-- User Management Panel -->

  <script type="text/javascript" src="js/admin_panel/RoleGrid.js"></script>
  <script type="text/javascript" src="js/admin_panel/AclGrid.js"></script>
  <script type="text/javascript" src="js/admin_panel/SectionNewUser.js"></script>
  <script type="text/javascript" src="js/admin_panel/SectionExistingUsers.js"></script>
  <script type="text/javascript" src="js/admin_panel/AdminPanel.js"></script>
  <script type="text/javascript" src="js/UserManagement/Panel.js"></script>

  <script type="text/javascript" src="js/CommentEditor.js"></script>
  <script type="text/javascript" src="js/AccountRequests.js"></script>

  <script type="text/javascript" src="js/RecipientVerificationPrompt.js"></script>
  <script type="text/javascript" src="js/BatchMailClient.js"></script>
  <script type="text/javascript" src="js/CurrentUsers.js"></script>

  <script type="text/javascript" src="js/ExceptionLister.js"></script>

  <script type="text/javascript" src="js/UserStats.js"></script>

  <!-- Summary Panel -->

  <script type="text/javascript" src="js/Summary/ConfigStore.js"></script>
  <script type="text/javascript" src="js/Summary/PortletsStore.js"></script>
  <script type="text/javascript" src="js/Summary/Portlet.js"></script>
  <script type="text/javascript" src="js/Summary/Portal.js"></script>
  <script type="text/javascript" src="js/Summary/TabPanel.js"></script>
  <script type="text/javascript" src="js/DashboardTools.js"></script>

  <script type="text/javascript" src="js/Log/SummaryStore.js"></script>
  <script type="text/javascript" src="js/Log/SummaryPortlet.js"></script>
  <script type="text/javascript" src="js/Log/LevelsStore.js"></script>
  <script type="text/javascript" src="js/Log/Store.js"></script>
  <script type="text/javascript" src="js/Log/GridPanel.js"></script>
  <script type="text/javascript" src="js/Log/TabPanel.js"></script>

  <script type="text/javascript" src="js/UsersSummary/Store.js"></script>
  <script type="text/javascript" src="js/UsersSummary/Portlet.js"></script>

  <link rel="stylesheet" type="text/css" href="../gui/lib/extjs/examples/ux/css/ColumnHeaderGroup.css"/>
  <link rel="stylesheet" type="text/css" href="../gui/css/GroupTab.css"/>

  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/ColumnHeaderGroup.js"></script>
  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/GroupTabPanel.js"></script>
  <script type="text/javascript" src="../gui/lib/extjs/examples/ux/GroupTab.js"></script>

  <script type="text/javascript" src="../gui/js/ExportPanel.js"></script>
  <script type="text/javascript" src="../gui/js/PortalModule.js"></script>

  <script type="text/javascript" src="js/Dashboard/Factory.js"></script>
  <script type="text/javascript" src="js/Dashboard/MenuStore.js"></script>
  <script type="text/javascript" src="js/Dashboard/FramePanel.js"></script>
  <script type="text/javascript" src="js/Dashboard/Viewport.js"></script>

  <?php echo \OpenXdmod\Assets::generateAssetTags('internal_dashboard'); ?>

    <script type="text/javascript">
    <?php
    $features = xd_utilities\getConfigurationSection('features');
    // Convert array values to boolean
    array_walk(
        $features,
        function (&$v) {
            $v = ($v == 'on');
        }
    );

    print "CCR.xdmod.features = ".json_encode($features).";\n";
    ?>
    </script>

  <script type="text/javascript" src="js/dashboard.js"></script>

  <?php /* App Kernel code. */ ?>
  <?php if (xd_utilities\getConfiguration('features', 'appkernels') == 'on'): ?>
  <?php
    if(isset($_GET['op']))
    {
        if($_GET['op']=='ak_instance')
        {
          $instance_id=$_GET['instance_id'];
          echo <<< END
<script type="text/javascript">
    Ext.onReady(function () {
    new XDMoD.AppKernel.InstanceWindow({instanceId:$instance_id}).show();
}, window, true);
</script>
END;
        }
    }
  ?>
  <?php endif; ?>
</head>
<body></body>
</html>

<?php

use CCR\XDReportManager;

   // Operation: user_admin->empty_report_image_cache

   \xd_security\assertDashboardUserLoggedIn();
   \xd_security\assertParameterSet('uid', RESTRICTION_UID);
   
   try {

      $target_user = XDUser::getUserByID($_POST['uid']);

      if ($target_user == NULL) {
         \xd_response\presentError("user_does_not_exist");
      }

      $chart_pool = new XDChartPool($target_user);
      $chart_pool->emptyCache();

      $report_manager = new XDReportManager($target_user);
      $report_manager->emptyCache();
            
      $displayUsername = $target_user->isXSEDEUser() ? $target_user->getXSEDEUsername() : $target_user->getUsername();

      $returnData['success'] = true;
      $returnData['message'] = "The report image cache for user <b>$displayUsername</b> has been emptied";

      xd_controller\returnJSON($returnData);
   
   }
   catch(Exception $e) {
      
      \xd_response\presentError($e->getMessage());

   }

?>

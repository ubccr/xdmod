<?php
 
   try {
   
      $user = \xd_security\getLoggedInUser();
   
      $rm = new XDReportManager($user);
   
      $report_id_array = explode (";", $_POST['selected_report']);
      
      foreach ( $report_id_array as $report_id ) {
         $rm->removeReportCharts($report_id);
         $rm->removeReportbyID($report_id);
      }
   
      $returnData['action'] = 'remove_report_by_id';
      $returnData['success'] = true;
   
      \xd_controller\returnJSON($returnData);

   }
   catch (SessionExpiredException $see) {
      // TODO: Refactor generic catch block below to handle specific exceptions,
      //       which would allow this block to be removed.
      throw $see;
   }
	catch (Exception $e) {

      \xd_response\presentError($e->getMessage());
	    
	}

?>
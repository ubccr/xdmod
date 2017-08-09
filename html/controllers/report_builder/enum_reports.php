<?php

   try {
      
   	$user = \xd_security\getLoggedInUser();
   
   	$rm = new XDReportManager($user);
   
   	$returnData['status'] = 'success';
   	
   	$reports_in_other_roles = $rm->enumReportsUnderOtherRoles();
   	
   	$returnData['has_reports_in_other_roles'] = (count($reports_in_other_roles) > 0);
   	$returnData['reports_in_other_roles'] = $reports_in_other_roles;
   	
   	$returnData['queue'] = $rm->fetchReportTable();
   
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
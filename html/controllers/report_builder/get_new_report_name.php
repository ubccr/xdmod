<?php

   try {
   
   	$user = \xd_security\getLoggedInUser();
   	
   	$rm = new XDReportManager($user);
   	
   	$response = array();
   	
   	$response['success'] = true;
   	$response['report_name']  = $rm->generateUniqueName();
   		
   	\xd_controller\returnJSON($response);
	
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

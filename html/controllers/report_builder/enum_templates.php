<?php

   try {
   
      $user = \xd_security\getLoggedInUser();
   
      $templates = XDReportManager::enumerateReportTemplates($user->getRoles());
      
      $returnData['status'] = 'success';
      $returnData['success'] = true;
      $returnData['templates'] = $templates;
      $returnData['count'] = count($templates);
   
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

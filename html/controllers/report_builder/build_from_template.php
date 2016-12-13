<?php

   \xd_security\assertParametersSet(array(
      'template_id',
      'resource_provider'
   ));
   
   try {
   
      $user = \xd_security\getLoggedInUser();

      $template = XDReportManager::retrieveReportTemplate($user, $_POST['template_id']);
            
      $template->buildReportFromTemplate($_REQUEST);
            
      $returnData['success'] = true;

   }
   catch (SessionExpiredException $see) {
      // TODO: Refactor generic catch block below to handle specific exceptions,
      //       which would allow this block to be removed.
      throw $see;
   }
	catch (Exception $e) {

	    \xd_response\presentError($e->getMessage());
	    
	}

	\xd_controller\returnJSON($returnData);

?>

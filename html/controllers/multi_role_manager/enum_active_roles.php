<?php

   try {
   
      $activeUser = \xd_security\getLoggedInUser();
   
   	$returnData = array();
   		
   	$availableRoles = $activeUser->enumAllAvailableRoles();
            
      foreach ($availableRoles as $role) {
         
         if ($role['is_active'] == true) {
   
            $returnData['active_role_id'] = $role['param_value'];
            $returnData['active_role_description'] = $role['description'];
         
            break;
            
         }//if ($role['is_active'] == true)
      
      }//foreach ($availableRoles as $role)
      
      // ===========================================        
   
      if (isset($_REQUEST['altview'])) {
         
         /*
         print "User ID Logged In: ".$activeUser->getUserId();
         print "<br /><br />\n";
         */
         
         \xd_debug\dumpQueryResultsAsTable($availableRoles);            
         exit;
         
   	}//if (isset($_REQUEST['altview']))
   	
   	//\xd_debug\dumpArray($availableRoles);
   	
   	$returnData['success'] = true;
    	$returnData['count'] = count($availableRoles);
   	$returnData['roles'] = $availableRoles;
   	
   	echo json_encode($returnData);
   	
	}
   catch (SessionExpiredException $see) {
      // TODO: Refactor generic catch block below to handle specific exceptions,
      //       which would allow this block to be removed.
      throw $see;
   }
	catch (\Exception $e) {

	    \xd_response\presentError($e->getMessage());
	    
	}
	
?>
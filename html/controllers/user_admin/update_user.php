<?php

   // Operation: user_admin->update_user
	
   $params = array('uid' => RESTRICTION_UID);

   $isValid = xd_security\secureCheck($params, 'POST');
	
   if (!$isValid) {
      $returnData['success'] = false;
      $returnData['status'] = 'invalid_id_specified';
      xd_controller\returnJSON($returnData);
   };
	
   // -----------------------------
	
   $user_to_update = XDUser::getUserByID($_POST['uid']);

   if ($user_to_update == NULL) {
      $returnData['success'] = false;
      $returnData['status'] = 'user_does_not_exist';
      xd_controller\returnJSON($returnData);
   }
	
   // -----------------------------
	
   $params = array(
      'first_name'    => RESTRICTION_FIRST_NAME,
      'last_name'     => RESTRICTION_LAST_NAME,
      'assigned_user' => RESTRICTION_ASSIGNMENT,
      'is_active'     => RESTRICTION_ACTIVE_FLAG,
      'user_type'     => RESTRICTION_GROUP
   );

   \xd_security\assertEmailParameterSet('email_address');
		
   $qualifyingParams = xd_security\secureCheck($params, 'POST', false);
	
   if ($qualifyingParams == 0) {
      $returnData['success'] = false;
      $returnData['status'] = 'need_update_information';
      xd_controller\returnJSON($returnData);
   }

   // -----------------------------
   
   $me = XDUser::getUserByID($_SESSION['xdDashboardUser']);

   if ($me->getUserID() == $user_to_update->getUserID()) {

      if (isset($_POST['is_active'])){
         $returnData['success'] = false;
         $returnData['status'] = 'You are not allowed to disable your own account.';
         xd_controller\returnJSON($returnData);
      }

   	if (isset($_POST['roles'])) {
   	
         $role_config = json_decode($_POST['roles'], true);
   	  
         if (isset($role_config['mainRoles'])){
   
            if (!in_array(ROLE_ID_MANAGER, $role_config['mainRoles'])) {
               $returnData['success'] = false;
               $returnData['status'] = 'You are not allowed to revoke manager access from yourself.';
               xd_controller\returnJSON($returnData);
            }
   
         }
         
      }//if (isset($_POST['roles'])) 

   } 

   if (isset($_POST['first_name']))       $user_to_update->setFirstName($_POST['first_name']);
   if (isset($_POST['last_name']))        $user_to_update->setLastName($_POST['last_name']);
   
   if (isset($_POST['email_address'])) {
      
      $email_address = (strlen($_POST['email_address']) > 0) ? $_POST['email_address'] : NO_EMAIL_ADDRESS_SET;

      if ( ($user_to_update->getUserType() != XSEDE_USER_TYPE) && ($email_address == NO_EMAIL_ADDRESS_SET) ) {
            $returnData['success'] = false;
            $returnData['status'] = 'This XDMoD user must have an e-mail address set.';
            xd_controller\returnJSON($returnData);        
      }
      
      $user_to_update->setEmailAddress($email_address);
   
   }
   
   if (isset($_POST['assigned_user']))    $user_to_update->setPersonID($_POST['assigned_user']);				
   if (isset($_POST['is_active']))        $user_to_update->setAccountStatus($_POST['is_active'] == 'y' ? ACTIVE : INACTIVE);
   
   if (isset($_POST['user_type'])) {
   
      if ($user_to_update->getUserType() != XSEDE_USER_TYPE) {
      
         $user_to_update->setUserType($_POST['user_type']);

      }
            
   }


// ===========================================

	if (isset($_POST['roles'])) {
	
	  $role_config = json_decode($_POST['roles'], true);
	  
	  $required_role_config_items = array('mainRoles', 'primaryRole',
	                                      'centerDirectorSites', 'primaryCenterDirectorSite', 
	                                      'centerStaffSites', 'primaryCenterStaffSite');
	  
	  $diff = array_diff($required_role_config_items, array_keys($role_config));
	  
	  // If $diff is 0, then all required role-related parameters are supplied

	  if (count($diff) == 0) {
	  
         if (empty($role_config['mainRoles']))     $role_config['mainRoles'] = array();
         
         if (isset($role_config['mainRoles']))     $user_to_update->setRoles($role_config['mainRoles']);
         // -----------------------------
         $user_to_update->setOrganizations($role_config['centerDirectorSites'], ROLE_ID_CENTER_DIRECTOR, false);
 
         // -----------------------------
         $user_to_update->setOrganizations($role_config['centerStaffSites'], ROLE_ID_CENTER_STAFF, false);
      }
     else {
	  
         $required_params = implode(', ', $diff);
   
         $returnData['success'] = false;
         $returnData['status'] = "Role config items required: $required_params";
         xd_controller\returnJSON($returnData);

	  }  
	  
	}//if (isset($_POST['roles'])) {
   
   // -----------------------------
       
   if (isset($_POST['institution'])) {

      if ($_POST['institution'] == -1) {
         $user_to_update->disassociateWithInstitution();
      }
      else {
         $user_to_update->setInstitution($_POST['institution'], ($role_config['primaryRole'] == ROLE_ID_CAMPUS_CHAMPION));	
      }

   }//if (isset($_POST['institution']))
   
   // -----------------------------

   try {
      $user_to_update->saveUser();
   }
   catch(Exception $e) {
      $returnData['success'] = false;
      $returnData['status'] = $e->getMessage();
      xd_controller\returnJSON($returnData);	
   }

   $returnData['success'] = true;
   
   $statusPrefix = $user_to_update->isXSEDEUser() ? 'XSEDE ' : '';
   $displayUsername = $user_to_update->isXSEDEUser() ? $user_to_update->getXSEDEUsername() : $user_to_update->getUsername();
   
   $returnData['status'] = $statusPrefix."User <b>$displayUsername</b> updated successfully";
   
   $returnData['username'] = $user_to_update->getUsername();
   $returnData['user_type'] = $user_to_update->getUserType();   //if isset()...
   
   xd_controller\returnJSON($returnData);	

?>

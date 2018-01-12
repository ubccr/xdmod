<?php

// Operation: user_admin->update_user

use Models\Services\Acls;
use Models\Acl;

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

   	if (isset($_POST['acls'])) {
   	
         $role_config = json_decode($_POST['acls'], true);
         if (!array_key_exists(ROLE_ID_MANAGER, $role_config)) {
                $returnData['success'] = false;
                $returnData['status'] = 'You are not allowed to revoke manager access from yourself.';
                xd_controller\returnJSON($returnData);
         }
      }//if (isset($_POST['acls']))

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
    // Make sure that we're not attempting to enable / disable the user before
    // processing 'acls'
    if (!isset($_POST['is_active'])) {
        if (isset($_POST['acls'])) {

            $acls = json_decode($_POST['acls'], true);
            if (count($acls) < 1) {
                \xd_response\presentError("Acl information is required");
            }

            // Checking for an acl set that only contains feature acls.
            // Feature acls are acls that only provide access to an XDMoD feature and
            // are not used for data access.
            $aclNames = array();
            $featureAcls = Acls::getAclsByTypeName('feature');
            $tabAcls = Acls::getAclsByTypeName('tab');
            $uiOnlyAcls = array_merge($featureAcls, $tabAcls);
            if (count($uiOnlyAcls) > 0) {
                $aclNames = array_reduce(
                    $uiOnlyAcls,
                    function ($carry, Acl $item) {
                        $carry []= $item->getName();
                        return $carry;
                    },
                    array()
                );
            }
            $diff = array_diff(array_keys($acls), $aclNames);
            $found = !empty($diff);
            if (!$found) {
                \xd_response\presentError('Please include a non-feature acl ( i.e. User, PI etc. )');
            }

            $user_to_update->setAcls(array());
            foreach ($acls as $aclName => $centers) {
                $acl = Acls::getAclByName($aclName);
                $user_to_update->addAcl($acl);
                // Make sure to set organizations if there are any.
                if (count($centers) > 0) {
                    $centerConfig = array();
                    $count = 0;
                    foreach($centers as $center) {
                        if ($count === 0 ) {
                            $config = array('primary' => 1, 'active' => 1);
                        } else {
                            $config = array('primary' => 0, 'active' => 0);
                        }
                        $centerConfig[$center] = $config;
                        $count += 1;
                    }
                    $user_to_update->setOrganizations($centerConfig, $aclName);
                }
            }
        } else {
            \xd_response\presentError("Acl information is required");
        } // if (isset($_POST['acls'])) {
    }

   // -----------------------------
       
   if (isset($_POST['institution'])) {

      if ($_POST['institution'] == -1) {
         $user_to_update->disassociateWithInstitution();
      }
      else {
         $user_to_update->setInstitution($_POST['institution'], array_key_exists(ROLE_ID_CAMPUS_CHAMPION, $acls));
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

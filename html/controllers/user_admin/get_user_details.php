<?php

	// Operation: user_admin->get_user_details

use Models\Services\Acls;

\xd_security\assertParameterSet('uid', RESTRICTION_UID);

	// -----------------------------
	
	$selected_user = XDUser::getUserByID($_POST['uid']);
	
	if ($selected_user == NULL){
      \xd_response\presentError("user_does_not_exist");
	}			
	
	// -----------------------------
	
	$userDetails = array();
	
	$userDetails['username'] = $selected_user->getUsername();
	$userDetails['formal_name'] = $selected_user->getFormalName();
	
	$userDetails['time_created'] = $selected_user->getCreationTimestamp();
	$userDetails['time_updated'] = $selected_user->getUpdateTimestamp();
	$userDetails['time_last_logged_in'] = $selected_user->getLastLoginTimestamp();
	
	$userDetails['email_address'] = $selected_user->getEmailAddress();
	
	if ($userDetails['email_address'] == NO_EMAIL_ADDRESS_SET) {
      $userDetails['email_address'] = '';
	}
	
	$userDetails['assigned_user_id'] = $selected_user->getPersonID(TRUE);

	//$userDetails['provider'] = $selected_user->getOrganization();
	$userDetails['institution'] = $selected_user->getInstitution();
	
	$userDetails['user_type'] = $selected_user->getUserType();
			
	$obj_warehouse = new XDWarehouse();
	
	$userDetails['institution_name'] = $obj_warehouse->resolveInstitutionName($userDetails['institution']);
	
	$userDetails['assigned_user_name'] = $obj_warehouse->resolveName($userDetails['assigned_user_id']);

   if ($userDetails['assigned_user_name'] == NO_MAPPING) {
      $userDetails['assigned_user_name'] = '';
   }	

	$userDetails['is_active'] = $selected_user->getAccountStatus() ? 'active' : 'disabled' ;

    $acls = Acls::listUserAcls($selected_user);
    $populatedAcls = array_reduce(
        $acls,
        function($carry, $item) use($selected_user) {
            $aclName = $item['name'];
            $aclCenters = array();
            if ($item['requires_center'] == true) {
                $aclCenters = Acls::getDescriptorParamValues(
                    $selected_user,
                    $aclName,
                    'provider'
                );
            }

            $carry[$aclName] = $aclCenters;

            return $carry;
        },
        array()
    );

	$userDetails['acls'] = $populatedAcls;

	$returnData['user_information'] = $userDetails;	
	$returnData['status'] = 'success';
	$returnData['success'] = true;
		
	\xd_controller\returnJSON($returnData);
			
?>
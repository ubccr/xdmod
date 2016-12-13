<?php

   \xd_security\assertParameterSet('member_id', RESTRICTION_UID);
   
	// -----------------------------
	
	$member = XDUser::getUserByID($_POST['member_id']);
	
	if ($member == NULL){
      \xd_response\presentError('user_does_not_exist');
	}			
	
	// -----------------------------

   try {
   
      $active_user = \xd_security\getLoggedInUser();
      
      $member_organizations = $member->getOrganizationCollection();
            
      if (!in_array($active_user->getActiveOrganization(), $member_organizations)) {
         \xd_response\presentError('center_mismatch_between_member_and_director');
      }

      $active_user->getActiveRole()->downgradeStaffMember($member);
      $returnData['success'] = true; 
      
   }
   catch (SessionExpiredException $see) {
      // TODO: Refactor generic catch block below to handle specific exceptions,
      //       which would allow this block to be removed.
      throw $see;
   }
   catch (\Exception $e){
      \xd_response\presentError($e->getMessage());
   }
       
   echo json_encode($returnData);
   	
?>
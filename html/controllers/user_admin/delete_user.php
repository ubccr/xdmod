<?php

   // Operation: user_admin->delete_user

   $logged_in_user = \xd_security\assertDashboardUserLoggedIn();
   
   \xd_security\assertParameterSet('uid', RESTRICTION_UID);

   try {

      $user_to_remove = XDUser::getUserByID($_POST['uid']);
   
      if ($user_to_remove == NULL) {
         \xd_response\presentError("user_does_not_exist");
      }
   
      if ($logged_in_user->getUsername() == $user_to_remove->getUsername()) {
         \xd_response\presentError("You are not allowed to delete your own account.");
      }
   
      // Remove all entries in this user's profile
      $profile = $user_to_remove->getProfile();
      $profile->clear();
       
      $username = $user_to_remove->getUsername();
   
      $statusPrefix = $user_to_remove->isFederatedUser() ? 'Federated ' : '';
      $displayUsername = $user_to_remove->getUsername();
      
      $user_to_remove->removeUser();
   
      $returnData['success'] = true;
      $returnData['message'] = $statusPrefix."User <b>$displayUsername</b> deleted from the portal";
   
      \xd_controller\returnJSON($returnData);

   }
   catch(Exception $e) {
      
      \xd_response\presentError($e->getMessage());

   }
   
?>
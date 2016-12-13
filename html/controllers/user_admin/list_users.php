<?php

	// Operation: user_admin->list_users

   \xd_security\assertParameterSet('group', RESTRICTION_GROUP);

	// -----------------------------

	$xda = new XDAdmin();

	$userListing = $xda->getUserListing($_POST['group']);

   $users = array();

	foreach($userListing as $currentUser) {

      $userData = explode(';', $currentUser['username']);

		$userEntry = array(
                           'id' => $currentUser['id'],
                           'username' => $userData[0],
                           'first_name' => $currentUser['first_name'],
                           'last_name' => $currentUser['last_name'],
                           'account_is_active' => $currentUser['account_is_active'],
                           'last_logged_in' => parseMicrotime($currentUser['last_logged_in'])
		                  );

		$users[] = $userEntry;

	}//foreach($userListing as $currentUser)

	// -----------------------------

	$returnData['success'] = true;
	$returnData['status'] = 'success';
	$returnData['users'] = $users;

	\xd_controller\returnJSON($returnData);

	// -----------------------------

   function parseMicrotime($mtime) {

      $time_frags = explode('.', $mtime);
      return $time_frags[0] * 1000;

   }//parseMicrotime

?>

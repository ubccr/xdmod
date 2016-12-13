<?php

	// Operation: user_auth->login
	
	$params = array('username' => RESTRICTION_USERNAME, 'password' => RESTRICTION_PASSWORD);
	
	$isValid = xd_security\secureCheck($params, 'POST');
	
	if (!$isValid) {
	    $returnData['status'] = 'credentials_not_specified';
	    xd_controller\returnJSON($returnData);
	}
	
	$user = XDUser::authenticate($_POST['username'], $_POST['password']);
	
	if (XDUser::isAuthenticated($user)) {
		    
		$_SESSION['xdUser'] = $user->getUserID();
	    
		$returnData['status'] = 'success';
		$returnData['first_name'] = $user->getFirstName();
		$returnData['account_is_active'] = ($user->getAccountStatus() == ACTIVE) ? 'true' : 'false';
	    
	}
	else{
	
	    $returnData['status'] = 'fail';
	    
	}
	
	xd_controller\returnJSON($returnData);

?>
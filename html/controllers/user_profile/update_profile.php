<?php

	// Operation: user_profile->update_profile
		
   $user_to_update = \xd_security\getLoggedInUser();

	// -----------------------------
	
	$params = array(
	    'first_name'        => RESTRICTION_FIRST_NAME,
	    'last_name'         => RESTRICTION_LAST_NAME,
	    'email_address'     => RESTRICTION_EMAIL,
		'password'          => RESTRICTION_PASSWORD,
		'field_of_science'  => RESTRICTION_FIELD_OF_SCIENCE
	);
	
	$qualifyingParams = xd_security\secureCheck($params, 'POST', false);
	
	if ($qualifyingParams == 0) {
	    $returnData['status'] = 'need_update_information';
	    xd_controller\returnJSON($returnData);
	};

	if (isset($_POST['first_name']))         $user_to_update->setFirstName($_POST['first_name']);
	if (isset($_POST['last_name']))          $user_to_update->setLastName($_POST['last_name']);
	if (isset($_POST['email_address']))      $user_to_update->setEmailAddress($_POST['email_address']);
	
	if (isset($_POST['password']))           $user_to_update->setPassword($_POST['password']);
	if (isset($_POST['field_of_science']))   $user_to_update->setFieldOfScience($_POST['field_of_science']);
			
	// -----------------------------
		
	try {
	
	    $user_to_update->saveUser();
	    
	}
	catch(Exception $e) {
	
	    $returnData['status'] = $e->getMessage();
	    xd_controller\returnJSON($returnData);	
	    
	}	
	
	$returnData['status'] = "success";

	xd_controller\returnJSON($returnData);	

?>
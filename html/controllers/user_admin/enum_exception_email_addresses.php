<?php
	
	// Operation: user_admin->enum_exception_email_addresses
	
	$xda = new XDAdmin();
			
	$email_addresses = $xda->enumerateExceptionEmailAddresses();

	// -----------------------------

	$returnData['success'] = true;
	$returnData['status'] = 'success';
	$returnData['email_addresses'] = $email_addresses;
			
	xd_controller\returnJSON($returnData);
			
?>
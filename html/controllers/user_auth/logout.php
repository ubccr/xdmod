<?php

	// Operation: user_auth->logout
	
	session_destroy();
				
	$returnData['status'] = 'success';
				
	xd_controller\returnJSON($returnData);
				
?>
<?php

	/* @Controller: user_auth
	 *
	 *
	 * operation: params -----
	 *
	 *     login: username, password
	 *     logout: [none]
	 *     pass_reset: email_address
	 *     update_pass: rid, password
	 *
	 */
	        
	require_once __DIR__.'/../../configuration/linker.php';
    \xd_security\start_session();
	
	$returnData = [];
	
	// --------------------
	
	$controller = new XDController();
	
	$controller->registerOperation('login');
	$controller->registerOperation('logout');	
	$controller->registerOperation('pass_reset');
	$controller->registerOperation('session_check');
	$controller->registerOperation('update_pass');
			
	$controller->invoke('POST');

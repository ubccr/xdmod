<?php

	/* @Controller: user_profile
	 *
	 *
	 * operation: params -----
	 *
	 *     list_fields_of_science: [none]
	 *     fetch_profile: [none]
	 *     update_profile: at least one of the following: first_name, last_name, email_address, password, field_of_science
	 */
	        
	@session_start();
   session_write_close();

	require_once dirname(__FILE__).'/../../configuration/linker.php';
	
	$returnData = array();
	
	// --------------------
	
	$controller = new XDController(array(STATUS_LOGGED_IN));
	
	$controller->registerOperation('list_fields_of_science');
	$controller->registerOperation('fetch_profile');	
	$controller->registerOperation('update_profile');
				
	$controller->invoke('GET');
		
?>
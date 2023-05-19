<?php

	/* @Controller: role_manager
	 *
	 *
	 * operation: params -----
	 *
	 *     enum_center_staff_members: group (valid values: 'house', 'imported')
	 */

	        
	require_once dirname(__FILE__).'/../../configuration/linker.php';
    \xd_security\start_session();
    session_write_close();
	
	$returnData = array();
	
	// --------------------
	
	$controller = new XDController(array(STATUS_LOGGED_IN, STATUS_CENTER_DIRECTOR_ROLE));
	
	$controller->registerOperation('enum_center_staff_members');
	$controller->registerOperation('get_member_status');
	$controller->registerOperation('upgrade_member');
	$controller->registerOperation('downgrade_member');
					
	$controller->invoke('POST');

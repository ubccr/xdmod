<?php

	/* @Controller: public_interface
	 *
	 *
	 * operation: params -----
	 *

	 */
	        
    require_once dirname(__FILE__).'/../../configuration/linker.php';
    \xd_security\start_session();
   session_write_close();

	
	$returnData = array();
	
	// --------------------
	
	$controller = new XDController();
	
	$controller->registerOperation('get_public');
					
	$controller->invoke('REQUEST');

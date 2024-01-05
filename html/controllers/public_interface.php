<?php

	/* @Controller: public_interface
	 *
	 *
	 * operation: params -----
	 *

	 */
	        
    require_once __DIR__.'/../../configuration/linker.php';
    \xd_security\start_session();
   session_write_close();

	
	$returnData = [];
	
	// --------------------
	
	$controller = new XDController();
	
	$controller->registerOperation('get_public');
					
	$controller->invoke('REQUEST');

<?php

	/* @Controller: public_interface
	 *
	 *
	 * operation: params -----
	 *

	 */
	        
	@session_start();
   session_write_close();

	require_once dirname(__FILE__).'/../../configuration/linker.php';
	
	$returnData = array();
	
	// --------------------
	
	$controller = new XDController();
	
	$controller->registerOperation('get_public');
					
	$controller->invoke('REQUEST');
		
?>
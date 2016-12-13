<?php
       
   @session_start();
   session_write_close();
   	 
   require_once dirname(__FILE__).'/../../configuration/linker.php';

	$returnData = array();
	
	// --------------------
	
	$controller = new XDController();

	$controller->registerOperation('contact');	
	$controller->registerOperation('sign_up');
			
	$controller->invoke('POST');
		
?>

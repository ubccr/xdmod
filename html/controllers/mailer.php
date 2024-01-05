<?php
       
   require_once __DIR__.'/../../configuration/linker.php';

   \xd_security\start_session();
   session_write_close();
   	 

	$returnData = [];
	
	// --------------------
	
	$controller = new XDController();

	$controller->registerOperation('contact');	
	$controller->registerOperation('sign_up');
			
	$controller->invoke('POST');

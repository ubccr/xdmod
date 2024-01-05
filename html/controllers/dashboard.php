<?php

   // Based on the following article from Microsoft:
   // http://support.microsoft.com/kb/323308
   // Internet Explorer file downloads over SSL do not work with the cache control headers.  This issue 
   // would prevent image (PNG) export options from being recognized as PNGs (the Content-Type would resolve to 'HTML Document')
   // and would prohibit the downloading of such images to the user's local file system.
   
   // We can control the cache control HTTP headers sent to the client (specifically Internet Explorer browsers) such 
   // that 'no-store' and 'no-cache' are absent.  In doing so, we permit the client to cache the contents.
   
   if ( preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])
   ) {
      session_cache_limiter("private");
   }

	require_once __DIR__.'/../../configuration/linker.php';

    \xd_security\start_session();
	
	$returnData = [];
	
	// --------------------
	
	$controller = new XDController([STATUS_LOGGED_IN]);
	
	$controller->registerOperation('create_report');
	$controller->registerOperation('get_reports');
	$controller->registerOperation('save_reports');
	$controller->registerOperation('delete_report');
	$controller->invoke('REQUEST');

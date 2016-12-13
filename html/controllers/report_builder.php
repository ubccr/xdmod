<?php

   // Based on the following article from Microsoft:
   // http://support.microsoft.com/kb/323308
   // Internet Explorer file downloads over SSL do not work with the cache control headers.  This issue 
   // would prevent PDFs from being downloaded.
   
   // We can control the cache control HTTP headers sent to the client (specifically Internet Explorer browsers) such 
   // that 'no-store' and 'no-cache' are absent.  In doing so, we permit the client to cache the contents, and subsequently
   // allow for PDFs to be downloaded properly.
         
   if (
      isset($_REQUEST['operation']) &&
      ($_REQUEST['operation'] == 'download_report') &&
      preg_match('/MSIE/', $_SERVER['HTTP_USER_AGENT'])
   ) {
      session_cache_limiter("private");
   }

   @session_start();
   session_write_close();

   require_once dirname(__FILE__).'/../../configuration/linker.php';

	$returnData = array();
	
	set_time_limit(0);
	
	// --------------------
	
	$controller = new XDController(array(STATUS_LOGGED_IN));

	$controller->registerOperation('enum_available_charts');
	$controller->registerOperation('enum_reports');
	//$controller->registerOperation('get_charts');
	$controller->registerOperation('get_new_report_name');		
   $controller->registerOperation('get_preview_data');	
	$controller->registerOperation('remove_chart_from_pool');
	$controller->registerOperation('remove_report_by_id');
   $controller->registerOperation('save_report');
	$controller->registerOperation('send_report');
	$controller->registerOperation('download_report');
	$controller->registerOperation('fetch_report_data');									

	$controller->registerOperation('enum_templates');	
	$controller->registerOperation('build_from_template');	
		 			
	$controller->invoke('REQUEST');
		
?>
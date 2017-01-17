<?php

    /* @Controller: chart_pool
	 *
	 *
	 * operation: params -----
	 *
	 *     add_to_queue:          chart_id, chart_title
	 *     remove_from_queue:     chart_id
	 */
            
    @session_start();
   session_write_close();

    require_once dirname(__FILE__).'/../../configuration/linker.php';
    
    $returnData = array();
    
    // --------------------
    
    $controller = new XDController(array(STATUS_LOGGED_IN));
    
    $controller->registerOperation('add_to_queue');
    $controller->registerOperation('remove_from_queue');
            
    $controller->invoke('POST');

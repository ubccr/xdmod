<?php

    /* @Controller: multi_role_manager
	 *
	 */

            
    @session_start();
    session_write_close();
    
    require_once dirname(__FILE__).'/../../configuration/linker.php';
    
    $returnData = array();
    
    // --------------------
    
    $controller = new XDController(array(STATUS_LOGGED_IN));
    
    $controller->registerOperation('enum_active_roles');
    $controller->registerOperation('set_active_role');
                    
    $controller->invoke('REQUEST');

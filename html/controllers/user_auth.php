<?php

    /* @Controller: user_auth
	 *
	 *
	 * operation: params -----
	 *
	 *     login: username, password
	 *     logout: [none]
	 *     pass_reset: email_address
	 *     update_pass: rid, password
	 *
	 */
            
    @session_start();

    require_once dirname(__FILE__).'/../../configuration/linker.php';
    
    $returnData = array();
    
    // --------------------
    
    $controller = new XDController();
    
    $controller->registerOperation('login');
    $controller->registerOperation('logout');
    $controller->registerOperation('pass_reset');
    $controller->registerOperation('session_check');
    $controller->registerOperation('update_pass');
            
    $controller->invoke('POST');

<?php

@session_start();

require_once dirname(__FILE__) . '/../../../configuration/linker.php';

$controller = new XDController(array(STATUS_LOGGED_IN, STATUS_MANAGER_ROLE), dirname(__FILE__));
$controller->registerOperation('get_menu');
$controller->invoke('REQUEST', 'xdDashboardUser');

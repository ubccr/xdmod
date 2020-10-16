<?php

require_once dirname(__FILE__) . '/../../../configuration/linker.php';

\xd_security\start_session();

$controller
    = new XDController(array(STATUS_LOGGED_IN, STATUS_MANAGER_ROLE), __DIR__);
$controller->registerOperation('get_config');
$controller->registerOperation('get_portlets');
$controller->invoke('REQUEST', 'xdDashboardUser');


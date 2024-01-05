<?php

require_once __DIR__ . '/../../../configuration/linker.php';

\xd_security\start_session();

$controller = new XDController([STATUS_LOGGED_IN, STATUS_MANAGER_ROLE], __DIR__);
$controller->registerOperation('get_menu');
$controller->invoke('REQUEST', 'xdDashboardUser');


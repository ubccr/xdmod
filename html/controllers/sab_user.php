<?php
/**
 * @Controller: sab_user (Science Advisory Board members)
 *
 * operation: params -----
 *     enum_tg_users: start, limit, [query], pi_only
 */

require_once __DIR__ . '/../../configuration/linker.php';

\xd_security\start_session();

$controller = new XDController(array(STATUS_LOGGED_IN, STATUS_MANAGER_ROLE));

$controller->registerOperation('enum_tg_users');

$session_variable
    = (isset($_POST['dashboard_mode']))
    ? 'xdDashboardUser'
    : 'xdUser';

$controller->invoke('POST', $session_variable);


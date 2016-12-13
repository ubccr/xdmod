<?php
/**
 * @Controller: sab_user (Science Advisory Board members)
 *
 * operation: params -----
 *     enum_tg_users: start, limit, [query], pi_only
 *     assign_assumed_person: person_id
 *     get_mapping: use_default
 */

@session_start();

require_once __DIR__ . '/../../configuration/linker.php';

$controller = new XDController(array(STATUS_LOGGED_IN));

$controller->registerOperation('enum_tg_users');
$controller->registerOperation('assign_assumed_person');
$controller->registerOperation('get_mapping');

$session_variable
    = (isset($_POST['dashboard_mode']))
    ? 'xdDashboardUser'
    : 'xdUser';

$controller->invoke('POST', $session_variable);


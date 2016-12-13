<?php

xd_security\assertDashboardUserLoggedIn();

try {
    $xdw = new XDWarehouse();

    if (!isset($_REQUEST['search_crit'])) {
        throw new Exception('No search criteria specified');
    }

    $users = $xdw->searchUsers(json_decode($_REQUEST['search_crit'], true));

    $returnData = array(
        'success' => true,
        'data'    => $users,
        'total'   => count($users),
    );

    xd_controller\returnJSON($returnData);
} catch (Exception $e) {
    xd_response\presentError($e->getMessage());
}

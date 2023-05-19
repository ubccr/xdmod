<?php
@require_once('common.php');

use DataWarehouse\Access\MetricExplorer;

// Attempt authentication by API token.
$user = \Models\Services\Tokens::authenticateToken();

// If token authentication failed then fall back to the standard session-based
// authentication method.
if ($user === null) {
    $user = \xd_security\detectUser(array(\XDUser::PUBLIC_USER));
}

$realmParameter = null;
try {
    $realmParameter = getRealm();
} catch (\Exception $e) {}

$realms = null;
if ($realmParameter !== null) {
    $realms = preg_split('/,\s*/', trim($realmParameter), null, PREG_SPLIT_NO_EMPTY);
}

xd_controller\returnJSON(MetricExplorer::getDimensionValues(
    $user,
    $_REQUEST['dimension_id'],
    $realms,
    getOffset(),
    getLimit(),
    getSearchText(),
    getSelectedFilterIds()
));

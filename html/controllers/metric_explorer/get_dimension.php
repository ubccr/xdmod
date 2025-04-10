<?php
@require_once('common.php');

use DataWarehouse\Access\MetricExplorer;
use Models\Services\Tokens;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

// Attempt authentication by API token.
try {
    $user = Tokens::authenticateToken();
} catch (UnauthorizedHttpException $e) {
    // If token authentication failed then fall back to the standard
    // session-based authentication method.
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

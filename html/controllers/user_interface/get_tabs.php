<?php

// Operation: user_interface->get_tabs

use Models\Services\Tabs;

$returnData = [];

try {
    $user = \xd_security\detectUser([XDUser::PUBLIC_USER]);

    $results = [];
    $tabs = Tabs::getTabs($user);
    foreach($tabs as $tab) {
        $results[] = ['tab' => $tab['name'], 'isDefault' => $tab['default'] ?? false, 'title' => $tab['title'], 'pos' => $tab['position'], 'permitted_modules' => $tab['permitted_modules'] ?? null, 'javascriptClass' => $tab['javascriptClass'], 'javascriptReference' => $tab['javascriptReference'], 'tooltip' => $tab['tooltip'] ?? '', 'userManualSectionName' => $tab['userManualSectionName']];
    }
    // Sort tabs
    usort(
        $results,
        fn($a, $b) => ($a['pos'] < $b['pos']) ? -1 : 1
    );

    $returnData = ['success'    => true, 'totalCount' => 1, 'message'    => '', 'data'       => [['tabs' => json_encode(array_values($results))]]];
} catch (SessionExpiredException $see)
{
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (Exception $e) {
    $returnData = ['success'    => false, 'totalCount' => 0, 'message'    => $e->getMessage(), 'stacktrace' => $e->getTrace(), 'data'       => []];
}

xd_controller\returnJSON($returnData);

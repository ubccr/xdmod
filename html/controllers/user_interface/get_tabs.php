<?php

// Operation: user_interface->get_tabs

use Models\Services\Tabs;

$returnData = array();

try {
    $user = \xd_security\detectUser(array(XDUser::PUBLIC_USER));

    $results = array();
    $tabs = Tabs::getTabs($user);
    foreach($tabs as $tab) {
        $results[] = array(
            'tab' => $tab['name'],
            'isDefault' => isset($tab['default']) ? $tab['default'] : false,
            'title' => $tab['title'],
            'pos' => $tab['position'],
            'permitted_modules' => isset($tab['permitted_modules']) ? $tab['permitted_modules'] : null,
            'javascriptClass' => $tab['javascriptClass'],
            'javascriptReference' => $tab['javascriptReference'],
            'tooltip' => isset($tab['tooltip']) ? $tab['tooltip'] : '',
            'userManualSectionName' => $tab['userManualSectionName'],
        );
    }
    // Sort tabs
    usort(
        $results,
        function ($a, $b) { return ($a['pos'] < $b['pos']) ? -1 : 1; }
    );

    $returnData = array(
        'success'    => true,
        'totalCount' => 1,
        'message'    => '',
        'data'       => array(
            array('tabs' => json_encode(array_values($results)))
        ),
    );
} catch (SessionExpiredException $see)
{
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (Exception $e) {
    $returnData = array(
        'success'    => false,
        'totalCount' => 0,
        'message'    => $e->getMessage(),
        'stacktrace' => $e->getTrace(),
        'data'       => array(),
    );
}

xd_controller\returnJSON($returnData);


<?php

try {
    $user = \xd_security\getLoggedInUser();

    $templates = XDReportManager::enumerateReportTemplates($user->getRoles());

    // We do not want to show the "Dashboard Tab Reports"
    foreach($templates as $key => $value){
        if ($value['name'] === 'Dashboard Tab Report') {
            unset($templates[$key]);
        }
    }

    $returnData['status'] = 'success';
    $returnData['success'] = true;
    $returnData['templates'] = $templates;
    $returnData['count'] = count($templates);

    \xd_controller\returnJSON($returnData);

} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (Exception $e) {
    \xd_response\presentError($e->getMessage());
}

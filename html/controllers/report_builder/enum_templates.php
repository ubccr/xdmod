<?php

try {
    $user = \xd_security\getLoggedInUser();

    $orig_templates = XDReportManager::enumerateReportTemplates($user->getRoles());

    // We do not want to show the "Dashboard Tab Reports" - copy to a new array to ensure
    // continuous indexes so it will be serialized to a json array not a json object.
    $templates = [];
    foreach($orig_templates as $key => $value){
        if ($value['name'] !== 'Dashboard Tab Report') {
            $templates[] = $value;
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

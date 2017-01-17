<?php

require_once('common.php');

$returnData = array();

try {
    $user = \xd_security\getLoggedInUser();
    
    $userProfile = $user->getProfile();
    $userProfile->setValue('filters', $_REQUEST['filters']);
    $userProfile->save();
} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (Exception $ex) {
    print_r($ex);
}

//xd_controller\returnJSON($returnData);

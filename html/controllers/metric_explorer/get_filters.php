<?php
require_once('common.php');

$returnData = array();

try {
    $user = \xd_security\getLoggedInUser();
   
    $userProfile = $user->getProfile();
    $filters = $userProfile->fetchValue('filters');
    if ($filters!= null) {
         $filtersArray = json_decode($filters);
         $returnData = array(
            'totalCount' => count($filtersArray),
            'message' => 'success',
            'data' => $filtersArray ,
            'success' => true);
    } else {
        $returnData = array(
            'totalCount' => 0,
            'message' => 'success',
            'data' => array(),
            'success' => true);
    }
} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (Exception $ex) {
    $returnData = array(
            'totalCount' => 0,
            'message' => $ex->getMessage(),
            'data' => array(),
            'success' => false);
}

xd_controller\returnJSON($returnData);

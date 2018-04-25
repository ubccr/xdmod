<?php

use Models\Services\Users;

try {

    $activeUser = \xd_security\getLoggedInUser();

    $members = Users::getUsersAssociatedWithCenter($activeUser->getUserID());

    $returnData = array();

    $returnData['success'] = true;
    $returnData['count'] = count($members);
    $returnData['members'] = $members;

    echo json_encode($returnData);

} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (\Exception $e) {

    \xd_response\presentError($e->getMessage());

}

?>
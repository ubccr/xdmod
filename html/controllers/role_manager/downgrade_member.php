<?php

use Models\Services\Users;

\xd_security\assertParameterSet('member_id', RESTRICTION_UID);

$member = XDUser::getUserByID($_POST['member_id']);

if ($member === null) {
    \xd_response\presentError('user_does_not_exist');
}

$returnData = array();
try {

    $activeUser = \xd_security\getLoggedInUser();
    $organization = $activeUser->getActiveOrganization();
    $memberUserId = $member->getUserID();

    // An eligible user must be associated with the currently logged in users center.
    if (!Users::userIsAssociatedWithCenter($memberUserId, $organization)) {
        \xd_response\presentError('center_mismatch_between_member_and_director');
    }

    //
    Users::demoteUserFromCenterStaff($member, $organization);

    $returnData['success'] = true;

} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (\Exception $e) {
    \xd_response\presentError($e->getMessage());
}

echo json_encode($returnData);

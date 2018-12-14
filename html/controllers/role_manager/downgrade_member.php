<?php

\xd_security\assertParameterSet('member_id', RESTRICTION_UID);

$member = XDUser::getUserByID($_POST['member_id']);

if ($member === null) {
    \xd_response\presentError('user_does_not_exist');
}

$returnData = array();
try {

    $activeUser = \xd_security\getLoggedInUser();

    // An eligible user must be associated with the currently logged in users center.
    if ($activeUser->getOrganizationID() !== $member->getOrganizationID()) {
        \xd_response\presentError('center_mismatch_between_member_and_director');
    }

    // Remove the center staff acl from the user.
    $member->setRoles(array_diff($member->getAcls(true), array(ROLE_ID_CENTER_STAFF)));

    // Save the acl changes.
    $member->saveUser();

    $returnData['success'] = true;

} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (\Exception $e) {
    \xd_response\presentError($e->getMessage());
}

echo json_encode($returnData);

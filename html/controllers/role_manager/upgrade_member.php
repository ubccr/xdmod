<?php

\xd_security\assertParameterSet('member_id', RESTRICTION_UID);

// -----------------------------

try {
    $returnData = array();

    $member = XDUser::getUserByID($_POST['member_id']);

    if ($member == NULL) {
        \xd_response\presentError('user_does_not_exist');
    }

    // -----------------------------

    $activeUser = \xd_security\getLoggedInUser();

    $memberCenters = Centers::listCentersForUser($member);
    $activeUserCenter = Centers::listCenterForUser($activeUser);
    $centerMismatch = false;
    foreach($activeUserCenter as $userCenter) {
        $centerMismatch = !in_array($userCenter, $memberCenters);
        if ($centerMismatch === true) {
            break;
        }
    }
    if ($centerMismatch === true) {
        \xd_response\presentError('center_mismatch_between_member_and_director');
    }

    $isCenterDirector = Centers::isCenterDirector($member, $activeUserCenter);
    if ($isCenterDirector === true) {
        \xd_response\presentError('User is already a center director of this center');
    }
    Centers::upgradeStaffMember($member);

    $returnData['success'] = true;
    $returnData['message'] = "has been upgraded to Center Director<br />(promoted by {$activeUser->getFormalName()})";

} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (\Exception $e) {

    \xd_response\presentError($e->getMessage());

}

echo json_encode($returnData);

<?php

\xd_security\assertParameterSet('member_id', RESTRICTION_UID);

// -----------------------------

$member = XDUser::getUserByID($_POST['member_id']);

if ($member == NULL) {
    \xd_response\presentError('user_does_not_exist');
}

// -----------------------------

try {
    $returnData = array();

    $activeUser = \xd_security\getLoggedInUser();

    $memberCenters = Centers::listCenterForUser($member);
    $activeUserCenters = Centers::listCenterForUser($activeUser);
    foreach($memberCenters as $memberCenter) {
        if (!in_array($memberCenter, $activeUserCenters)) {
            \xd_response\presentError('center_mismatch_between_member_and_director');
        }
    }

    Centers::downgradeStaffMember($member, $activeUserCenters);

    $returnData['success'] = true;

} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (\Exception $e) {
    \xd_response\presentError($e->getMessage());
}

echo json_encode($returnData);

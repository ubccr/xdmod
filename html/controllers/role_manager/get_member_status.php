<?php

\xd_security\assertParameterSet('member_id', RESTRICTION_UID);

// -----------------------------

$member = XDUser::getUserByID($_POST['member_id']);

if ($member == NULL) {
    \xd_response\presentError('user_does_not_exist');
}

// -----------------------------

try {
    $returnData = array(
        'success' => true,
        'message' => '',
        'eligible' => true
    );

    $active_user = \xd_security\getLoggedInUser();

    $memberStaffCenters = Centers::listCentersForUser($member);
    $activeUserStaffCenter = Centers::listCenterForUser($active_user);
    foreach($memberStaffCenters as $memberCenter) {
        if (!in_array($memberCenter, $activeUserCenters)) {
            \xd_response\presentError('center_mismatch_between_member_and_director');
        }
    }

    // -----------------------------
    $memberDirectorCenters = Centers::listCentersForUser($member, ROLE_ID_CENTER_DIRECTOR);
    $memberIsCenterDirector = array_reduce($activeUserStaffCenter, function($carry, $item) use ($memberDirectorCenters) {
        return $carry && in_array($item, $memberDirectorCenters);
    }, true);

    if ($memberIsCenterDirector === true) {
        $returnData['success'] = true;
        $returnData['message'] = 'is already a Center Director';
        $returnData['eligible'] = false;
    }
    echo json_encode($returnData);

} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (\Exception $e) {

    \xd_response\presentError($e->getMessage());

}

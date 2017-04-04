<?php

\xd_security\assertParameterSet('member_id', RESTRICTION_UID);

// -----------------------------

$member = XDUser::getUserByID($_POST['member_id']);

if ($member == NULL) {
    \xd_response\presentError('user_does_not_exist');
}

// -----------------------------

try {

    $active_user = \xd_security\getLoggedInUser();

    $memberStaffCenters = Centers::listCentersForUser($member);
    $activeUserStaffCenter = Centers::listCenterForUser($active_user);
    foreach($activeUserStaffCenter as $activeUserCenter) {
        if (!in_array($activeUserCenter, $memberStaffCenters)) {
            \xd_response\presentError('center_mismatch_between_member_and_director');
        }
    }
    /*$member_staff_organizations = $member->getOrganizationCollection(ROLE_ID_CENTER_STAFF);

    if (!in_array($active_user->getActiveOrganization(), $member_staff_organizations)) {
        \xd_response\presentError('center_mismatch_between_member_and_director');
    }*/

    // -----------------------------

    $member_director_organizations = $member->getOrganizationCollection(ROLE_ID_CENTER_DIRECTOR);

    if (in_array($active_user->getActiveOrganization(), $member_director_organizations)) {

        // This member is already capable of becoming a center director of this center

        $promoter = $member->getPromoter(ROLE_ID_CENTER_DIRECTOR, $active_user->getActiveOrganization());

        $returnData['success'] = false;
        $returnData['message'] = "is already a Center Director";

        if ($promoter != -1) {

            // This member was promoted to a Center Director by another user...

            if ($active_user->getUserId() == $promoter) {

                $returnData['success'] = true;
                $returnData['eligible'] = false;

            }

            $promoter_user = XDUser::getUserById($promoter);
            $promoter_name = $promoter_user->getFormalName();

            $returnData['message'] = "has been upgraded to Center Director<br />(promoted by $promoter_name)";

        }

        \xd_controller\returnJSON($returnData);

    }//if (in_array($active_user->getActiveOrganization(), $member_director_organizations))

    // -----------------------------

    $returnData['success'] = true;
    $returnData['message'] = '';
    $returnData['eligible'] = true;

    echo json_encode($returnData);

} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (\Exception $e) {

    \xd_response\presentError($e->getMessage());

}

?>
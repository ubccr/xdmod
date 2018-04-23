<?php

\xd_security\assertParameterSet('member_id', RESTRICTION_UID);

$member = XDUser::getUserByID($_POST['member_id']);

if ($member == null) {
    \xd_response\presentError('user_does_not_exist');
}

try {
    $activeUser = \xd_security\getLoggedInUser();

    $memberStaffOrganizations = $member->getOrganizationCollection(ROLE_ID_CENTER_STAFF);

    if (!in_array($activeUser->getActiveOrganization(), $memberStaffOrganizations)) {
        \xd_response\presentError('center_mismatch_between_member_and_director');
    }

    $memberDirectorOrganizations = $member->getOrganizationCollection(ROLE_ID_CENTER_DIRECTOR);

    if (in_array($activeUser->getActiveOrganization(), $memberDirectorOrganizations)) {

        // This member is already capable of becoming a center director of this center

        $promoter = $member->getPromoter(ROLE_ID_CENTER_DIRECTOR, $activeUser->getActiveOrganization());

        $returnData['success'] = false;
        $returnData['message'] = "is already a Center Director";

        if ($promoter != -1) {

            // This member was promoted to a Center Director by another user...

            if ($activeUser->getUserId() == $promoter) {

                $returnData['success'] = true;
                $returnData['eligible'] = false;

            }

            $promoterUser = XDUser::getUserById($promoter);
            $promoterName = $promoterUser->getFormalName();

            $returnData['message'] = "has been upgraded to Center Director<br />(promoted by $promoterName)";
        }

        \xd_controller\returnJSON($returnData);
    }//if (in_array($active_user->getActiveOrganization(), $member_director_organizations))

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

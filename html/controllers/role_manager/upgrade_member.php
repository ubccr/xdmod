<?php

use Models\Services\Users;
use Models\Services\Centers;

\xd_security\assertParameterSet('member_id', RESTRICTION_UID);

try {

    $member = XDUser::getUserByID($_POST['member_id']);

    if ($member === null) {
        \xd_response\presentError('user_does_not_exist');
    }

    $activeUser = \xd_security\getLoggedInUser();

    // Ensure that the user performing this operation is authorized
    if (!$activeUser->hasAcl(ROLE_ID_CENTER_DIRECTOR) || !$activeUser->getAccountStatus()) {
        \xd_controller\returnJSON(
            array(
                'success' => false,
                'message' => 'You are not authorized to perform this action'
            )
        );
    }

    $organization = $activeUser->getActiveOrganization();
    $memberUserId = $member->getUserID();

    // An eligible user must be associated with the currently logged in users center.
    if (!Users::userIsAssociatedWithCenter($memberUserId, $organization)) {
        \xd_response\presentError('center_mismatch_between_member_and_director');
    }

    // They must not already be a Center Director for the organization.
    if (Centers::hasCenterRelation($memberUserId, $organization, ROLE_ID_CENTER_DIRECTOR)) {
        $returnData['success'] = false;
        $returnData['message'] = "is a Center Director";
        \xd_controller\returnJSON($returnData);
    }

    // They must not be a Center Staff for the organization.
    // Although this makes them eligible for demotion.
    if (Centers::hasCenterRelation($memberUserId, $organization, ROLE_ID_CENTER_STAFF)) {
        $returnData['success'] = false;
        $returnData['message'] = "is already a Center Staff";
        \xd_controller\returnJSON($returnData);
    }

    Users::promoteUserToCenterStaff($member, $organization);
    $returnData['success'] = true;
    $returnData['message'] = "has been upgraded to Center Staff<br />(promoted by {$activeUser->getFormalName()})";

} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (\Exception $e) {

    \xd_response\presentError($e->getMessage());

}

echo json_encode($returnData);

?>
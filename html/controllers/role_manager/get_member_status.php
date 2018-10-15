<?php

use Models\Services\Centers;
use Models\Services\Users;

\xd_security\assertParameterSet('member_id', RESTRICTION_UID);

try {
    $member = XDUser::getUserByID($_POST['member_id']);

    if ($member === null) {
        \xd_response\presentError('user_does_not_exist');
    }

    $returnData = array(
        'success' => true,
        'message' => '',
        'eligible' => true
    );

    $activeUser = \xd_security\getLoggedInUser();
    $organization = $activeUser->getOrganizationID();
    $memberOrganization = $member->getOrganizationID();

    // An eligible user must be associated with the currently logged in users center.
    if ($memberOrganization !== $organization) {
        \xd_response\presentError('center_mismatch_between_member_and_director');
    }

    // They must not already be a Center Director for the organization.
    if ($memberOrganization === $organization && $member->hasAcl(ROLE_ID_CENTER_DIRECTOR)) {
        $returnData['success'] = false;
        $returnData['message'] = "is a Center Director";
        \xd_controller\returnJSON($returnData);
    }

    // This makes them ineligible for promotion, but eligible for demotion.
    if ($memberOrganization === $organization && $member->hasAcl(ROLE_ID_CENTER_STAFF)) {
        $returnData['eligible'] = false;
    }

    // They must be active
    if (!$member->getAccountStatus()) {
        $returnData['success'] = false;
        $returnData['message'] = "User is disabled";
        \xd_controller\returnJSON($returnData);
    }

    echo json_encode($returnData);
} catch (SessionExpiredException $see) {
    // TODO: Refactor generic catch block below to handle specific exceptions,
    //       which would allow this block to be removed.
    throw $see;
} catch (\Exception $e) {

    \xd_response\presentError($e->getMessage());

}

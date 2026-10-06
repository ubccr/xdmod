<?php

// Operation: sab_user->enum_tg_users

use Models\Services\Acls;

$params = array(
    'start'       => RESTRICTION_NUMERIC_POS,
    'limit'       => RESTRICTION_NUMERIC_POS,
    'pi_only'     => RESTRICTION_YES_NO
);

$isValid = xd_security\secureCheck($params, 'POST');

if (!$isValid) {
    $returnData = array(
        'success'          =>  false,
        'status'           => 'invalid_params_specified',
        'message'          => 'invalid_params_specified',
        'total_user_count' => 0,
    );
    xd_controller\returnJSON($returnData);
};

$xdw = new XDWarehouse();

$name_filter = (isset($_POST['query'])) ? $_POST['query'] : NULL;
$use_pi_filter = ($_POST['pi_only'] == 'y');

// Determine if the user accessing the controller is a Campus Champion

$university_id = NULL;

$user_session_variable
    = (isset($_POST['dashboard_mode']))
    ? 'xdDashboardUser'
    : 'xdUser';

$user = \XDUser::getUserByID($_SESSION[$user_session_variable]);

if (
   $user->hasAcl(ROLE_ID_CAMPUS_CHAMPION)
    && (!isset($_POST['userManagement']))
) {

    // Add an additional filter to eventually produce a listing of
    // individuals affiliated with the same university as this user.
    $university_id = Acls::getDescriptorParamValue($user, ROLE_ID_CAMPUS_CHAMPION, 'provider');
}

list($userCount, $users) = $xdw->enumerateGridUsers(
    $_POST['start'],
    $_POST['limit'],
    $name_filter,
    $use_pi_filter,
    $university_id
);

$entry_id = 0;

$userEntries = array();

foreach ($users as $currentUser) {
    $entry_id++;
    $userEntries[] = array(
        'id'          => $entry_id,
        'person_id'   => $currentUser['id'],
        'person_name' => $currentUser['long_name']
    );
}

$returnData = array(
    'success'          =>  true,
    'status'           => 'success',
    'message'          => 'success',
    'total_user_count' => $userCount,
    'users'            => $userEntries,
);

xd_controller\returnJSON($returnData);

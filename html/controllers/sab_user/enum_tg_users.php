<?php

// Operation: sab_user->enum_tg_users

$params = array(
    'start'       => RESTRICTION_NUMERIC_POS,
    'limit'       => RESTRICTION_NUMERIC_POS,
    'search_mode' => RESTRICTION_SEARCH_MODE,
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

$name_filter = (isset($_POST['query'])) ? $_POST['query'] : null;
$use_pi_filter = ($_POST['pi_only'] == 'y');

// Determine if the user accessing the controller is a Campus Champion

$university_id = null;

$user_session_variable
    = (isset($_POST['dashboard_mode']))
    ? 'xdDashboardUser'
    : 'xdUser';

$user = \XDUser::getUserByID($_SESSION[$user_session_variable]);

if ($user->getActiveRole()->getIdentifier() == ROLE_ID_CAMPUS_CHAMPION
    && (!isset($_POST['userManagement']))
) {
    // Add an additional filter to eventually produce a listing of
    // individuals affiliated with the same university as this user.
    $university_id = $user->getActiveRole()->getUniversityID();
}

if ($_POST['search_mode'] == 'formal_name') {
    $searchMethod = FORMAL_NAME_SEARCH;
}

if ($_POST['search_mode'] == 'username') {
    $searchMethod = USERNAME_SEARCH;
}

list($userCount, $users) = $xdw->enumerateGridUsers(
    $searchMethod,
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

    if ($searchMethod == FORMAL_NAME_SEARCH) {
        $personName
            = $currentUser['last_name']
            . ', '
            . $currentUser['first_name'];
        $personID = $currentUser['id'];
    }

    if ($searchMethod == USERNAME_SEARCH) {
        $personName = $currentUser['absusername'];

        // Append the absusername to the id so that each entry is guaranteed
        // to have a unique identifier (needed for dependent ExtJS combobox
        // (TGUserDropDown.js) to work properly regarding selections).
        $personID = $currentUser['id'] . ';' . $currentUser['absusername'];
    }

    $userEntries[] = array(
        'id'          => $entry_id,
        'person_id'   => $personID,
        'person_name' => $personName
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

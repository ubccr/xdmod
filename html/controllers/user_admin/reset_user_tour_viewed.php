<?php
$logged_in_user = \xd_security\assertDashboardUserLoggedIn();
$params = array('uid' => RESTRICTION_UID);

$isValid = xd_security\secureCheck($params, 'POST');
\xd_security\assertParameterSet('uid', RESTRICTION_UID);

if ($isValid === false) {
    \xd_response\presentError('invalid_id_specified');
};

$user = XDUser::getUserByID($_POST['uid']);
if ($user === null) {
    \xd_response\presentError("user_does_not_exist");
}

$storage = new \UserStorage($user, 'viewed_user_tour');
$storage->upsert(0, ['uid' => $_POST['uid'], 'viewedTour' => $_POST['viewed_user_tour']]);

\xd_controller\returnJSON(array(
    'success' => true,
    'total' => 1,
    'message' => 'This user will be now be propmted to view the New User Tour the next time they visit XDMoD'
));

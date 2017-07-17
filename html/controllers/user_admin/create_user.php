<?php

use CCR\MailWrapper;

// Operation: user_admin->create_user

$creator = \xd_security\assertDashboardUserLoggedIn();

\xd_security\assertParametersSet(array(
    'username'      => RESTRICTION_USERNAME,
    'first_name'    => RESTRICTION_FIRST_NAME,
    'last_name'     => RESTRICTION_LAST_NAME,
  //  'assignment'    => RESTRICTION_ASSIGNMENT,
    'user_type'     => RESTRICTION_GROUP
));

\xd_security\assertEmailParameterSet('email_address');

// -----------------------------

if (isset($_POST['roles'])) {
    $role_config = json_decode($_POST['roles'], true);

    //FB::log($role_config);

    $required_role_config_items = array(
        'mainRoles',
        'primaryRole',
        'centerDirectorSites',
        'primaryCenterDirectorSite',
        'centerStaffSites',
        'primaryCenterStaffSite',
    );

    $diff = array_diff($required_role_config_items, array_keys($role_config));

    if (count($diff) > 0) {
        $required_params = implode(', ', $diff);
        \xd_response\presentError("Role config items required: $required_params");
    }

}
else {
    \xd_response\presentError("Role information is required");
}

try {
    $password_chars = 'abcdefghijklmnopqrstuvwxyz!@#$%-_=+ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    $max_password_chars_index = strlen($password_chars) - 1;
    $password = '';
    for ($i = 0; $i < CHARLIM_PASSWORD; $i++) {
        $password .= $password_chars[mt_rand(0, $max_password_chars_index)];
    }

    $newuser = new XDUser(
        $_POST['username'],
        $password,
        $_POST['email_address'],
        $_POST['first_name'],
        '',
        $_POST['last_name'],
        $role_config['mainRoles'],
        $role_config['primaryRole'],
        NULL,
        $_POST['assignment']
    );

    $newuser->setUserType($_POST['user_type']);

    $newuser->saveUser();

    // =============================

    $centerDirectorConfig = array();

    foreach ($role_config['centerDirectorSites'] as $cdSite) {
        $primary = ($cdSite == $role_config['primaryCenterDirectorSite']);
        $centerDirectorConfig[$cdSite] = array(
            'active'  => $primary,
            'primary' => $primary,
        );
    }

    $newuser->setOrganizations($centerDirectorConfig, ROLE_ID_CENTER_DIRECTOR);

    // -----------------------------

    $centerStaffConfig = array();

    foreach ($role_config['centerStaffSites'] as $csSite) {
        $primary = ($csSite == $role_config['primaryCenterStaffSite']);
        $centerStaffConfig[$csSite] = array(
            'active' => $primary,
            'primary' => $primary,
        );
    }//foreach

    $newuser->setOrganizations($centerStaffConfig, ROLE_ID_CENTER_STAFF);

    // =============================

    if (isset($_POST['institution']) && $_POST['institution'] != -1) {
        $newuser->setInstitution($_POST['institution']);
    }

    // =============================

    $page_title = xd_utilities\getConfiguration('general', 'title');
    $site_address = xd_utilities\getConfigurationUrlBase('general', 'site_address');

    // -------------------

    $message = "Welcome to the $page_title.  Your account has been created.\n\n";
    $message .= "Your username is: ".$_POST['username']."\n\n";
    $message .= "Please visit the following page to create your password:\n\n";
    $message .= "${site_address}password_reset.php?mode=new&rid=".md5($newuser->getUsername().$newuser->getPasswordLastUpdatedTimestamp())."\n\n";
    $message .= "Once you have created a password, you will be directed to $site_address where you can then log in using your credentials.\n\n";

    $message .= "For assistance on using the portal, please consult the User Manual:\n";
    $message .= $site_address."user_manual\n\n";
    $message .= "The XDMoD Team";

    $recipient
        = (xd_utilities\getConfiguration('general', 'debug_mode') == 'on')
        ? xd_utilities\getConfiguration('general', 'debug_recipient')
        : $_POST['email_address'];

    $subject = "$page_title: Account Created";

    MailWrapper::sendMail(array(
        'body'=>$message,
        'subject'=>$subject,
        'toAddress'=>array(
            array('address'=>$recipient)
        )
    ));
}
catch (Exception $e) {
    \xd_response\presentError($e->getMessage());
}

// -----------------------------

if (isset($_REQUEST['account_request_id']) && !empty($_REQUEST['account_request_id'])) {
    $xda = new XDAdmin();
    $xda->updateAccountRequestStatus($_REQUEST['account_request_id'], $creator->getUsername());
}

$returnData['success'] = true;
$returnData['user_type'] = $_POST['user_type'];
$returnData['message'] = 'User <b>'.$_POST['username'].'</b> created successfully';

\xd_controller\returnJSON($returnData);

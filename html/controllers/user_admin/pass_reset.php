<?php

use CCR\MailWrapper;

// Operation: user_admin->pass_reset

$params = array('uid' => RESTRICTION_UID);

$isValid = xd_security\secureCheck($params, 'POST');

if (!$isValid) {
    $returnData['success'] = false;
    $returnData['status'] = 'invalid_id_specified';
    xd_controller\returnJSON($returnData);
};

// -----------------------------

$user_to_email = XDUser::getUserByID($_POST['uid']);

if ($user_to_email == NULL) {
    $returnData['success'] = false;
    $returnData['status'] = 'user_does_not_exist';
    xd_controller\returnJSON($returnData);
}

// -----------------------------

$page_title = \xd_utilities\getConfiguration('general', 'title');

// -------------------

try {
    $userName = $user_to_email->getUsername();

    $rid = $user_to_email->generateRID();

    $site_address
        = \xd_utilities\getConfigurationUrlBase('general', 'site_address');
    $resetUrl = "${site_address}password_reset.php?rid=$rid";

    MailWrapper::sendTemplate(
        'password_reset',
        array(
            'first_name'           => $user_to_email->getFirstName(),
            'username'             => $userName,
            'reset_url'            => $resetUrl,
            'expiration'           => date("%c %Z", explode('|', $rid)[1]),
            'maintainer_signature' => MailWrapper::getMaintainerSignature(),
            'subject'              => "$page_title: Password Reset",
            'toAddress'            => $user_to_email->getEmailAddress()
        )
    );

    $returnData['success'] = true;
    $returnData['status'] = "Password reset e-mail sent to user {$userName}";
    $returnData['message'] = $returnData['status'];
}
catch (Exception $e) {
    $returnData['success'] = false;
    $returnData['message'] = $e->getMessage();
}

xd_controller\returnJSON($returnData);


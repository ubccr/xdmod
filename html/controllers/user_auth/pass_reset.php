<?php

use CCR\MailWrapper;

// Operation: user_auth->pass_reset

$isValid = isset($_POST['email']) && xd_security\isEmailValid($_POST['email']);

if (!$isValid) {
    $returnData['status'] = 'invalid_email_address';
    xd_controller\returnJSON($returnData);
};

// -----------------------------

$user_to_email = XDUser::userExistsWithEmailAddress($_POST['email'], TRUE);

if ($user_to_email == INVALID) {
    $returnData['status'] = 'no_user_mapping';
    xd_controller\returnJSON($returnData);
}

if ($user_to_email == AMBIGUOUS) {
    $returnData['status'] = 'multiple_accounts_mapped';
    xd_controller\returnJSON($returnData);
}

$user_to_email = XDUser::getUserByID($user_to_email);

// -----------------------------

$page_title = \xd_utilities\getConfiguration('general', 'title');

// -------------------

try {
    $rid = $user_to_email->generateRID();

    $site_address
        = \xd_utilities\getConfigurationUrlBase('general', 'site_address');
    $resetUrl = "${site_address}password_reset.php?rid=$rid";

    MailWrapper::sendTemplate(
        'password_reset',
        array(
            'first_name'           => $user_to_email->getFirstName(),
            'username'             => $user_to_email->getUsername(),
            'reset_url'            => $resetUrl,
            'maintainer_signature' => MailWrapper::getMaintainerSignature(),
            'subject'              => "$page_title: Password Reset",
            'toAddress'            => $user_to_email->getEmailAddress()
        )
    );
    $returnData['success'] = true;
    $returnData['status']  = 'success';
}
catch (Exception $e) {
    $returnData['success'] = false;
    $returnData['message'] = $e->getMessage();
    $returnData['status']  = 'failure';
}

xd_controller\returnJSON($returnData);


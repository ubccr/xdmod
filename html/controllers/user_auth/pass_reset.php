<?php

// Operation: user_auth->pass_reset

//require_once dirname(__FILE__).'/../../../classes/MailTemplates.php';

$isValid = isset($_POST['email']) && xd_security\isEmailValid($_POST['email']);

if (!$isValid) {
    $returnData['status'] = 'invalid_email_address';
    xd_controller\returnJSON($returnData);
};

// -----------------------------

$user_to_email = XDUser::userExistsWithEmailAddress($_POST['email'], true);

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

$page_title = xd_utilities\getConfiguration('general', 'title');
$mailer_sender = xd_utilities\getConfiguration('mailer', 'sender_email');

$recipient
    = (xd_utilities\getConfiguration('general', 'debug_mode') == 'on')
    ? xd_utilities\getConfiguration('general', 'debug_recipient')
    : $user_to_email->getEmailAddress();

// -------------------

$mail = ZendMailWrapper::init();
$mail->setFrom($mailer_sender, 'XDMoD');
$mail->setSubject("$page_title: Password Reset");
$mail->addTo($recipient);

// -------------------

$message = MailTemplates::passwordReset($user_to_email);

$mail->setBodyText($message);

// -----------------------------

try {
    $status = $mail->send();
    $returnData['success'] = true;
    $returnData['status']  = 'success';
} catch (Exception $e) {
    $returnData['success'] = false;
    $returnData['message'] = $e->getMessage();
    $returnData['status']  = 'failure';
}

xd_controller\returnJSON($returnData);

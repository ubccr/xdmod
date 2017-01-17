<?php

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

if ($user_to_email == null) {
    $returnData['success'] = false;
    $returnData['status'] = 'user_does_not_exist';
    xd_controller\returnJSON($returnData);
}

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

// -------------------

try {
    $mail->send();
    $returnData['success'] = true;
    $returnData['status'] = "Password reset e-mail sent to user {$user_to_email->getUsername()}";
    $returnData['message'] = $returnData['status'];
} catch (Exception $e) {
    $returnData['success'] = false;
    $returnData['message'] = $e->getMessage();
}

xd_controller\returnJSON($returnData);

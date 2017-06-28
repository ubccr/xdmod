<?php

use CCR\MailWrapper;

// Operation: user_auth->pass_reset

//require_once dirname(__FILE__).'/../../../classes/MailWrapper.php';

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

$page_title = xd_utilities\getConfiguration('general', 'title');

$recipient
    = (xd_utilities\getConfiguration('general', 'debug_mode') == 'on')
    ? xd_utilities\getConfiguration('general', 'debug_recipient')
    : $user_to_email->getEmailAddress();

// -------------------

try {
    $message = MailWrapper::passwordReset($user_to_email);
    $subject = "$page_title: Password Reset";
    $mail = MailWrapper::initPHPMailer($properties = array('body'=>$message, 'subject'=>$subject, 'toAddress'=>$recipient, 'fromAddress'=>null, 'fromName'=>null, 'ifReplyAddress'=>false, 'bcc'=>false, 'attachment'=>false, 'fileName'=>'', 'attachment_file_name'=>'', 'type'=>''));

    // -----------------------------

    $status = $mail->send();
    $returnData['success'] = true;
    $returnData['status']  = 'success';
}
catch (Exception $e) {
    $returnData['success'] = false;
    $returnData['message'] = $e->getMessage();
    $returnData['status']  = 'failure';
}

xd_controller\returnJSON($returnData);


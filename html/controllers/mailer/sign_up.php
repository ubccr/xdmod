<?php

use CCR\MailWrapper;
use CCR\DB;

// Operation: mailer->sign_up

\xd_security\assertParametersSet(array(
    'first_name'             => RESTRICTION_FIRST_NAME,
    'last_name'              => RESTRICTION_LAST_NAME,
    'title'                  => RESTRICTION_NON_EMPTY,
    'organization'           => RESTRICTION_NON_EMPTY,
    'field_of_science'       => RESTRICTION_NON_EMPTY,
    'additional_information' => RESTRICTION_NON_EMPTY
));

\xd_security\assertEmailParameterSet('email');

// Check CAPTCHA if enabled.

$captcha_private_key = xd_utilities\getConfiguration(
    'mailer',
    'captcha_private_key'
);

if ($captcha_private_key !== '') {
    if (!isset($_POST['g-recaptcha-response'])){
        \xd_response\presentError('Recaptcha information not specified');
    }
    $recaptcha = new \ReCaptcha\ReCaptcha($captcha_private_key);
    $resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER["REMOTE_ADDR"]);
    if (!$resp->isSuccess()) {
        $errors = $resp->getErrorCodes();
        \xd_response\presentError('You must enter the words in the Recaptcha box properly.' . print_r($errors, 1));
    }
}

// Insert account request into database (so it appears in the internal
// dashboard under "XDMoD Account Requests").

$pdo = DB::factory('database');

$pdo->execute(
    "
        INSERT INTO AccountRequests (
            first_name,
            last_name,
            organization,
            title,
            email_address,
            field_of_science,
            additional_information,
            time_submitted,
            status,
            comments
        ) VALUES (
            :first_name,
            :last_name,
            :organization,
            :title,
            :email_address,
            :field_of_science,
            :additional_information,
            NOW(),
            'new',
            ''
        )
    ",
    array(
        'first_name'             => $_POST['first_name'],
        'last_name'              => $_POST['last_name'],
        'organization'           => $_POST['organization'],
        'title'                  => $_POST['title'],
        'email_address'          => $_POST['email'],
        'field_of_science'       => $_POST['field_of_science'],
        'additional_information' => $_POST['additional_information']
    )
);

// Create email.

$time_requested = date('D, F j, Y \a\t g:i A');
$organization   = ORGANIZATION_NAME;

$message = <<<"EOMSG"
The following person has signed up for an account on XDMoD:

Person Details ----------------------------------

Name:                     {$_POST['first_name']} {$_POST['last_name']}
E-Mail:                   {$_POST['email']}
Title:                    {$_POST['title']}
Organization:             {$_POST['organization']}

Time Account Requested:   $time_requested

Affiliation with $organization:

{$_POST['additional_information']}

EOMSG;

$response = array();

// Original sender's e-mail must be in the "fromAddress" field for the XDMoD Request Tracker to function
try {
    MailWrapper::sendMail(array(
        'body'         => $message,
        'subject'      => "[" . \xd_utilities\getConfiguration('general', 'title') . "] A visitor has signed up",
        'toAddress'    => \xd_utilities\getConfiguration('general', 'contact_page_recipient'),
        'fromAddress'  => $_POST['email'],
        'fromName'     => $_POST['last_name'] . ', ' . $_POST['first_name'],
        'replyAddress' => \xd_utilities\getConfiguration('mailer', 'sender_email')
    ));
    $response['success'] = true;
}
catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

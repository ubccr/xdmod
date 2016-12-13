<?php

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
    if (
        !isset($_POST["recaptcha_challenge_field"])
        || !isset($_POST["recaptcha_response_field"])
    ){
        \xd_response\presentError('Recaptcha information not specified');
    }

    $recaptcha_check = recaptcha_check_answer(
        $captcha_private_key,
        $_SERVER["REMOTE_ADDR"],
        $_POST["recaptcha_challenge_field"],
        $_POST["recaptcha_response_field"]
    );

    if (!$recaptcha_check->is_valid) {
        \xd_response\presentError(
            'You must enter the words in the Recaptcha box properly.'
        );
    };
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

$mail = ZendMailWrapper::init();

$recipient
    = (xd_utilities\getConfiguration('general', 'debug_mode') == 'on')
    ? xd_utilities\getConfiguration('general', 'debug_recipient')
    : xd_utilities\getConfiguration('general', 'contact_page_recipient');
$mail->addTo($recipient);

// Original sender's e-mail must be in the "From" field for the XDMoD
// Request Tracker to function
$mail->setFrom($_POST['email']);
$mail->setReplyTo($_POST['email']);

$title = xd_utilities\getConfiguration('general', 'title');
$mail->setSubject("[$title] A visitor has signed up");

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
$mail->setBodyText($message);

// Send email.

$response = array();

try {
    $mail->send();
    $response['success'] = true;
}
catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);


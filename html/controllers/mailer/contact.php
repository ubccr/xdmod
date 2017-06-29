<?php

use CCR\MailWrapper;

// Operation: mailer->contact

$response = array();

\xd_security\assertParametersSet(array(
  'name'              => RESTRICTION_FIRST_NAME,
  'message'           => RESTRICTION_NON_EMPTY,
  'username'          => RESTRICTION_NON_EMPTY,
  'token'             => RESTRICTION_NON_EMPTY,
  'timestamp'         => RESTRICTION_NON_EMPTY
));

\xd_security\assertEmailParameterSet('email');

// ----------------------------------------------------------

// If the user claims to be a public user, verify that they're not logged in.
// Otherwise, verify that their claimed username matches the logged in user.
$user_is_public = $_POST['username'] === '__public__';

if ($user_is_public) {
  $user_logged_in = false;
  try {
    \xd_security\getLoggedInUser();
    $user_logged_in = true;
  } catch (Exception $e) {}

  if ($user_logged_in) {
    \xd_response\presentError("Client claims to be public user but is logged in.");
  }
} else {
  $user = \xd_security\getLoggedInUser();
  if ($_POST['username'] !== $user->getUsername()) {
    \xd_response\presentError("Client claims to be a user other than the logged in user.");
  }
}

// ----------------------------------------------------------

$user_info = $user_is_public ? 'Public Visitor' : "Username:     ".$_POST['username'];
$reason = isset($_POST['reason']) ? $_POST['reason'] : 'contact';

// ----------------------------------------------------------

$captcha_private_key = xd_utilities\getConfiguration('mailer', 'captcha_private_key');

if ($captcha_private_key !== '' && !isset($_SESSION['xdUser'])) {
  if (!isset($_POST["recaptcha_challenge_field"]) || !isset($_POST["recaptcha_response_field"])){
    \xd_response\presentError('Recaptcha information not specified');
  }

  $recaptcha_check = recaptcha_check_answer(
    $captcha_private_key,
    $_SERVER["REMOTE_ADDR"],
    $_POST["recaptcha_challenge_field"],
    $_POST["recaptcha_response_field"]
  );

  if (!$recaptcha_check->is_valid) {
    \xd_response\presentError('You must enter the words in the Recaptcha box properly.');
  };
}

// ----------------------------------------------------------

$recipient
  = (xd_utilities\getConfiguration('general', 'debug_mode') == 'on')
  ? xd_utilities\getConfiguration('general', 'debug_recipient')
  : xd_utilities\getConfiguration('general', 'contact_page_recipient');

switch ($reason) {
  case 'wishlist':
    $subject = "[WISHLIST] Feature request sent from a portal visitor";
    $message_type = "feature request";
    break;

  default:
    $subject = "Message sent from a portal visitor";
    $message_type = "message";
    break;
}

$timestamp = date('m/d/Y, g:i:s A', $_POST['timestamp']);

$message = "Below is a $message_type from '{$_POST['name']}' ({$_POST['email']}):\n\n";
$message .= $_POST['message'];
$message .="\n------------------------\n\nSession Tracking Data:\n\n  ";
$message .="$user_info\n\n  Token:        {$_POST['token']}\n  Timestamp:    $timestamp";

try {
    //Original sender's e-mail must be in the 'fromAddress' field for the XDMoD Request Tracker to function
    $properties = array(
        'body'=>$message,
        'subject'=>$subject,
        'toAddress'=>$recipient,
        'fromAddress'=>$_POST['email'],
        'fromName'=>$_POST['name'],
        'ifReplyAddress'=>true,
        'bcc'=>false,
        'attachment'=>false,
        'fileName'=>'',
        'attachment_file_name'=>'',
        'type'=>''
    );

    MailWrapper::sendmail($properties);
}
catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    echo json_encode($response);
    exit;
}

// =====================================================

$message
    = "Hello, {$_POST['name']}\n\n"
    . "This e-mail is to inform you that the XDMoD Portal Team has received your $message_type, and will\n"
    . "be in touch with you as soon as possible.\n\n"
    . MailTemplates::getMaintainerSignature();

$Subject = "Thank you for your $message_type.";

// -------------------

try {
    $properties = array(
        'body'=>$message,
        'subject'=>$Subject,
        'toAddress'=>$_POST['email'],
        'fromAddress'=>null,
        'fromName'=>null,
        'ifReplyAddress'=>false,
        'bcc'=>false,
        'attachment'=>false,
        'fileName'=>'',
        'attachment_file_name'=>'',
        'type'=>''
    );

    MailWrapper::sendMail($properties);
}
catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    echo json_encode($response);
    exit;
}

// =====================================================

$response['success'] = true;

echo json_encode($response);


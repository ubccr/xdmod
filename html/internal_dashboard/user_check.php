<?php

require_once __DIR__ . '/../../configuration/linker.php';

@session_start();

if (isset($_POST['xdmod_username']) && isset($_POST['xdmod_password'])) {
    $user = XDUser::authenticate(
        $_POST['xdmod_username'],
        $_POST['xdmod_password']
    );

    if ($user == NULL) {
        denyWithMessage('Invalid login');
    }

    $user->postLogin();

    $_SESSION['xdDashboardUser'] = $user->getUserID();
}

// Check that the user has been set in the session.
if (!isset($_SESSION['xdDashboardUser'])){
    denyWithMessage('');
    exit;
}

// Retrieve user data.
try {
    $user = XDUser::getUserByID($_SESSION['xdDashboardUser']);
} catch(Exception $e) {
    denyWithMessage('There was a problem initializing your account.');
    exit;
}

// Check that the user exists.
if (!isset($user)) {

    // There is an issue with the account (most likely deleted while the
    // user was logged in, and the user refreshed the entire site)
    session_destroy();
    header("Location: splash.php");
    exit;
}

// Check that the user has access to the internal dashboard.
if ($user->isManager() == false) {
    denyWithMessage('You are not allowed access to this resource.');
    exit;
}

/**
 * Deny the user access and display a message.
 *
 * @param string $message
 */
function denyWithMessage($message)
{
    $referer
        = isset($_POST['direct_to'])
        ? $_POST['direct_to']
        : $_SERVER['SCRIPT_NAME'];
    $reject_response = $message;

    include 'splash.php';
    exit;
}

<?php
/**
 * REST related functions.
 */

namespace xd_rest;

/**
 * Get the user's REST token.
 *
 * @return string The user's REST token.
 */
function getToken()
{
    if (isset($_SESSION['session_token'])) {
        $token = $_SESSION['session_token'];
    } else if (isset($_SESSION['public_session_token'])) {
        $token = $_SESSION['public_session_token'];
    } else {
        $token = '';
    }

    return $token;
}

/**
 * Sets cookies necessary for use of the REST API by the browser client.
 */
function setCookies(): void
{
    // Obtain and set a cookie for the user's REST token.
    setcookie('xdmod_token', getToken(), ['expires' => 0, 'path' => '/', 'domain' => '', 'secure' => true, 'httponly' => true]);
}

/**
 * Prints variables necessary for use of the REST API by the browser client.
 */
function printJavascriptVariables(): void
{
    // Ensure the namespace is set up.
    echo "Ext.namespace('XDMoD.REST');\n\n";

    // Obtain and output the user's REST token.
    $token = getToken();
    echo "XDMoD.REST.token = '$token';\n";

    // Obtain and output the base URL for REST calls.
    $base_url_prefix = \xd_utilities\getConfiguration('rest', 'base');
    $base_url_version = \xd_utilities\getConfiguration('rest', 'version');
    $base_url = $base_url_prefix . $base_url_version;
    echo "XDMoD.REST.url = '$base_url';\n";
}

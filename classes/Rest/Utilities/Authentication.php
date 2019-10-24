<?php

namespace Rest\Utilities;

use Exception;
use Log;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

use \CCR\DB as DB;
use XDUser;

/**
 * Authentication utility class.
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 **/
class Authentication
{
    /**
     * A default key for retrieving a user token from a Silex Request header.
     *
     * @var string
     */
    const _DEFAULT_AUTH_TOKEN = 'Token';

    /**
     * A default key for retrieving the username from a Silex Request header.
     *
     * @var string
     */
    const _DEFAULT_AUTH_USER = 'php-auth-user';

    /**
     * A default key for retrieving a users' password from a Silex Request header.
     *
     * @var string
     */
    const _DEFAULT_AUTH_PASSWORD = 'php-auth-pw';

    /**
     * A default key for retrieving a user token from a Silex Requests' parameters.
     *
     * @var string
     */
    const _DEFAULT_TOKEN = 'token';

    /**
     * A default key for retrieving a users' username from a Silex Requests' parameters.
     *
     * @var string
     */
    const _DEFAULT_USER = 'username';

    /**
     * A default key for retrieving a users' password from a Silex Requests' parameters.
     *
     * @var string
     */
    const _DEFAULT_PASSWORD = 'password';

    /**
     * A default key for retrieving a user token from a Silex Request header.
     *
     * @var string
     */
    const _DEFAULT_COOKIE_TOKEN = 'xdmod_token';

    /**
     * This function will, with the provided Request object, retrieve the
     * authentication information ( if any ) and attempt to resolve the current
     * user from the credentials and ip address contained in the authentication
     * information.
     *
     * @param Request $request which will be used to authenticate the current
     *                         user.
     * @return null|XDUser null if auth info is missing, the current user
     *                     if they are found
     * @throws SessionExpiredException if the session variable 'xdInit' is not
     *         set.
     */
    public static function authenticateUser(Request $request)
    {
        $authInfo = self::getAuthenticationInfo($request);

        if (!isset($authInfo) || !isset($authInfo['ip'])) {
            return null;
        }

        // If the user provided a username and password, use those to determine
        // the user. If no token or a public token was provided, use the public
        // user. Otherwise, use the provided real token to determine the user.
        //
        // If the username and password were provided, the user may be trying
        // to get an authentication token, so this form of authentication takes
        // precedence over any provided token.
        if (isset($authInfo['username']) && isset($authInfo['password'])) {
            $user = XDUser::authenticate($authInfo['username'], $authInfo['password']);

            if ($user == null) {
                throw new HttpException(401, 'Invalid credentials specified.');
            }

            if ($user->getAccountStatus() == false) {
                throw new HttpException(403, 'This account is disabled.');
            }
        } elseif (!isset($authInfo['token']) || \xd_utilities\string_begins_with($authInfo['token'], 'public-')) {
            $user = XDUser::getPublicUser();
        } else {
            $user = self::resolveUserFromToken(
                $authInfo['token'],
                $authInfo['ip']
            );
        }

        return $user;

    }//authenticateUser

    /**
     * This function will attempt to retrieve the currently logged in users'
     * authentication information from the provided Request object. If a
     * Request object is not provided than an empty array is returned.
     *
     * @param Request $request
     * @return array of the form array(
     *         'username' => <user_name>,
     *         'password' => <password>,
     *         'token' => <token>,
     *         'ip' => <ip> )
     */
    public static function getAuthenticationInfo(Request $request)
    {
        if (!isset($request)) {
            return array();
        }

        try {
            $useBasicAuth = \xd_utilities\getConfiguration('rest', 'basic_auth') == 'on';
        } catch (Exception $e) {
            $useBasicAuth = false;
        }

        if ($useBasicAuth) {
            $username = $request->headers->get(Authentication::_DEFAULT_AUTH_USER);
            $password = $request->headers->get(Authentication::_DEFAULT_AUTH_PASSWORD);
        }

        if (!isset($username)) {
            $username = $request->get(Authentication::_DEFAULT_USER);
        }
        if (!isset($password)) {
            $password = $request->get(Authentication::_DEFAULT_PASSWORD);
        }

        $token = $request->get(Authentication::_DEFAULT_TOKEN);
        if (!isset($token)) {
            $token = $request->headers->get(Authentication::_DEFAULT_AUTH_TOKEN);
        }
        if (!isset($token)) {
            $token = $request->cookies->get(Authentication::_DEFAULT_COOKIE_TOKEN);
        }

        return array(
            'username' => $username,
            'password' => $password,
            'token' => $token,
            'ip' => $request->getClientIp()
        );
    } // _getAuthenticationInfo

    /**
     * This function will attempt to retrieve an instance of XDUser for the provided token, and ip_address.
     *
     * @param $token the session token that will be used to retrieve
     *                           the currently logged in user.
     * @param $ip_address the ip_address that is associated with this
     *                           authentication attempt.
     * @return XDUser
     * @throws Exception
     * @throws SessionExpiredException
     */
    private static function resolveUserFromToken(
        $token,
        $ip_address
    ) {
        @session_start();

        // TODO: A REST API should not depend on the consumer
        // sending a session cookie. The below block is for
        // handling session expiration in the browser. This
        // function and the client code should be refactored
        // to not depend on session-related code to detect
        // expired REST tokens.

        if (!isset($_SESSION['xdInit'])) {

            // Session died (token no longer valid);
            $msg = 'Token invalid or expired. '
                . 'You must authenticate before using this call.';
            throw new \SessionExpiredException($msg);
        }

        $session_id = session_id();

        // Without IP restriction ... relaxed, especially for
        // very mobile users (in which network hopping is
        // frequent)

        $resolver_query = "
            SELECT user_id
            FROM SessionManager
            WHERE session_token = :session_token
                AND session_id = :session_id
                AND init_time = :init_time
        ";
        $resolver_query_params = array(
            ':session_token' => $token,
            ':session_id' => $session_id,
            ':init_time' => $_SESSION['xdInit'],
        );

        $access_logfile = LOG_DIR . '/session_manager.log';

        $logConf = array('mode' => 0644);

        $sessionManagerLogger = Log::factory(
            'file',
            $access_logfile,
            'SESSION_MANAGER',
            $logConf
        );

        $sessionManagerLogger->log(
            $_SERVER['REMOTE_ADDR'] . ' QUERY ' . $resolver_query
            . ' PARAMS ' . json_encode($resolver_query_params)
        );

        $pdo = DB::factory('database');

        $user_check = $pdo->query(
            $resolver_query,
            $resolver_query_params
        );

        if (count($user_check) > 0) {
            $last_active_time = self::getMicrotime();

            $last_active_query = "
                UPDATE SessionManager
                SET last_active = :last_active
                WHERE session_token = :session_token
                    AND session_id = :session_id
                    AND ip_address = :ip_address
                    AND init_time = :init_time
            ";
            $pdo->execute($last_active_query, array(
                ':last_active' => $last_active_time,
                ':session_token' => $token,
                ':session_id' => $session_id,
                ':ip_address' => $ip_address,
                ':init_time' => $_SESSION['xdInit'],
            ));

            $user = XDUser::getUserByID($user_check[0]['user_id']);

            if ($user == null) {
                throw new \Exception('Invalid token specified');
            }

            return $user;
        } else {

            // An error occurred (session is intact, yet a
            // corresponding record pertaining to that session
            // does not exist in the DB)
            throw new \Exception('Invalid token specified');
        }
    }

    /**
     * Get the current epoch time in micro seconds.
     *
     * @return int
     */
    private static function getMicrotime()
    {
        list($usec, $sec) = explode(' ', microtime());
        return $usec + $sec;
    }
}

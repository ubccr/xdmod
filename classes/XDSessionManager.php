<?php
/**
 * XDMoD session management.
 *
 * @author Ryan J. Gentner
 */

use CCR\DB;

/**
 * Abstracts access to the following schema:
 *
 * CREATE TABLE `SessionManager` (
 *   `session_token` varchar(40) NOT NULL,
 *   `session_id` text NOT NULL,
 *   `user_id` int(11) unsigned NOT NULL,
 *   `ip_address` varchar(40) NOT NULL,
 *   `user_agent` varchar(255) NOT NULL,
 *   `init_time` varchar(100) NOT NULL,
 *   `last_active` varchar(100) NOT NULL,
 *   `used_logout` tinyint(1) unsigned DEFAULT NULL,
 *   PRIMARY KEY (`session_token`),
 *   KEY `user_id` (`user_id`)
 * ) ENGINE=MyISAM DEFAULT CHARSET=latin1;
 */
class XDSessionManager
{

    /**
     * Record a user login.
     *
     * @param XDUser $name User object.
     *
     * @return string Session token.
     */
    public static function recordLogin($user)
    {
        @session_start();

        $pdo = DB::factory('database');

        // Retrieve the exact time in which the login occurred.  This
        // timestamp is then be assigned to a session variable which
        // will be consulted any time a token needs to be mapped back to
        // an actual XDMoD Portal user (see resolveUserFromToken(...)).

        $init_time = self::getMicrotime();

        $session_id = session_id();
        $user_id = $user->getUserID();

        $session_token = md5($user_id . $session_id . $init_time);

        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $record_query = "
            INSERT INTO SessionManager (
                session_token,
                session_id,
                user_id,
                ip_address,
                user_agent,
                init_time,
                last_active,
                used_logout
            ) VALUES (
                :session_token,
                :session_id,
                :user_id,
                :ip_address,
                :user_agent,
                :init_time,
                :last_active,
                0
            )
        ";

        $pdo->execute($record_query, array(
            ':session_token' => $session_token,
            ':session_id'    => $session_id,
            ':user_id'       => $user_id,
            ':ip_address'    => $ip_address,
            ':user_agent'    => $user_agent,
            ':init_time'     => $init_time,
            ':last_active'   => $init_time,
        ));

        $_SESSION['xdInit'] = $init_time;
        $_SESSION['xdUser'] = $user_id;

        $_SESSION['session_token'] = $session_token;

        return $session_token;
    }

   /**
    * If the second argument to this function ($user_id_only) is set to
    * true, then resolveUserFromToken(...) will return the numerical
    * XDUser ID as opposed to an XDUser object instance.
    *
    * @param bool $restRequest True if this is a REST request.
    * @param bool $user_id_only True if only the user id should be used.
    *
    * @return XDUser
    */
    public static function resolveUserFromToken(
        $restRequest,
        $user_id_only = false
    ) {
        $token = $restRequest->getToken();
        $ip_address = $restRequest->getIPAddress();

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
            ':session_id'    => $session_id,
            ':init_time'     => $_SESSION['xdInit'],
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
                ':last_active'   => $last_active_time,
                ':session_token' => $token,
                ':session_id'    => $session_id,
                ':ip_address'    => $ip_address,
                ':init_time'     => $_SESSION['xdInit'],
            ));

            if ($user_id_only) {
                return $user_check[0]['user_id'];
            }

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
     * Log out a user.
     *
     * @param string $token User's session token.
     */
    public static function logoutUser($token = "")
    {
        @session_start();

        // If a session is still active and a token has been specified,
        // attempt to record the logout in the SessionManager table
        // (provided the supplied token is still 'valid' and a
        // corresponding record in SessionManager can be found)

        if (isset($_SESSION['xdInit']) && !empty($token)) {
            $session_id = session_id();
            $ip_address = $_SERVER['REMOTE_ADDR'];

            $logout_query = "
                UPDATE SessionManager
                SET used_logout = 1
                WHERE session_token = :session_token
                    AND session_id = :session_id
                    AND ip_address = :ip_address
                    AND init_time = :init_time
            ";
            $pdo = DB::factory('database');
            $pdo->execute($logout_query, array(
                ':session_token' => $token,
                ':session_id' => $session_id,
                ':ip_address' => $ip_address,
                ':init_time' => $_SESSION['xdInit'],
            ));
        }

        // Drop the session so that any REST calls requiring
        // authentication (via tokens) trip the first Exception as the
        // result of invoking resolveUserFromToken($token)
        session_destroy();
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

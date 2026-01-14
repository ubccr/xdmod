<?php
/**
 * XDMoD session management.
 *
 * @author Ryan J. Gentner
 */

use CCR\DB;

/**
 * Abstracts access to the moddb.SessionManager table
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
        if (session_status() === PHP_SESSION_NONE) {
            \xd_security\start_session();
        }

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
     * Log out a user.
     *
     * @param string $token User's session token.
     */
    public static function logoutUser($token = "")
    {
        if (session_status() === PHP_SESSION_NONE) {
            \xd_security\start_session();
        }

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

        try {
            $auth = new Authentication\SAML\XDSamlAuthentication();
            $auth->logout();
        } catch (InvalidArgumentException $ex) {
          // This will catch when apache or nginx have been set up
          // to to have an alternate saml configuration directory
          // that does not exist, so we ignore it as saml isnt set
          // up and we dont have to do anything with it
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

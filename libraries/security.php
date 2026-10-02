<?php
/**
 * Security related functions.
 */

namespace xd_security;

use Egulias\EmailValidator\Validation\RFCValidation;
use Exception;
use SessionExpiredException;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;
use XDUser;

class SessionSingleton
{

    /**
     * @var Session
     */
    private static $session;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        throw new Exception('No touchee! This is a static utility class, no instantiation please.');
    }

    /**
     * @return Session
     */
    public static function getSession(): Session
    {
        self::initSession();
        return self::$session;
    }

    /**
     * @return void
     */
    public static function initSession(): void
    {
        if (!isset(self::$session)) {
            @session_start();
            self::$session = new Session(new PhpBridgeSessionStorage());
            self::$session->start();
        }
    }


}

/**
 * Wrapper for the session_start that ensures that the secure
 * cookie flag is set for the session cookie.
 */
function start_session()
{
    switch (session_status()) {
        case PHP_SESSION_NONE:
            $cookieParams = session_get_cookie_params();
            session_set_cookie_params(
                $cookieParams['lifetime'],
                $cookieParams['path'],
                $cookieParams['domain'],
                true
            );
            SessionSingleton::initSession();
        case PHP_SESSION_ACTIVE:
        case PHP_SESSION_DISABLED:
        default:
    }

}

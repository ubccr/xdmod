<?php

namespace CCR;

use Exception;
use Psr\Log\LoggerInterface;
use Rest\Utilities\Authentication;

/**
 * This class is meant to provide logging functionality for use with the html controller operations akin to the logging
 * done for requests to REST stack endpoints.
 */
class RequestLogger
{
    /**
     * @var Logger|LoggerInterface
     */
    private $logger;

    public function __construct($ident = 'controller.log', $logLevel = \CCR\Log::INFO)
    {
        $this->logger = Log::factory($ident, array(
            'console' => false,
            'file' => false,
            'mail' => false,
            'dbLogLevel' => $logLevel
        ));
    }

    /**
     * Log request related data to the database.
     *
     * @param float $start the result of a call to `microtime(true)` that indicates the start of a request.
     * @param float $end the result of a call to `microtime(true)` that indicates the end of a request.
     * @param int $level at which level the log request should be made. Corresponds to \CCR\Log::[EMERG|ALERT|CRIT|ERR|WARNING|NOTICE|INFO|DEBUG]
     * @return void
     */
    public function log($start, $end, $level = \CCR\Log::INFO)
    {
        $authInfo = $this->getAuthenticationInfo();

        $retval = array(
            'message' => 'Route called',
            'path' => $this->array_get_or('REQUEST_URI', $_SERVER),
            'query' => $this->array_get_or('QUERY_STRING', $_SERVER),
            'referer' => $this->array_get_or('HTTP_REFERER', $_SERVER),
            'elapsed' => $end - $start,
            'post' => $_POST,
            'data' => array(
                'ip' => $this->array_get_or('REMOTE_ADDR', $_SERVER),
                'method' => $this->array_get_or('REQUEST_METHOD', $_SERVER),
                'host' => $this->array_get_or('REMOTE_HOST', $_SERVER),
                'port' => $this->array_get_or('REMOTE_PORT', $_SERVER),
                'username' => $authInfo['username'],
                'token' => $authInfo['token'],
                'timestamp' => null
            )
        );

        $requestTime = $this->array_get_or('REQUEST_TIME', $_SERVER);
        if (isset($requestTime)) {
            $retval['data']['timestamp'] = date("Y-m-d H:i:s", $requestTime);
        }

        $this->logger->log($level, $retval);
    }

    /**
     * Just a helper function to increase readability so there aren't ternaries all over the place.
     *
     * @param string $property
     * @param array $array
     * @param mixed|null $default
     * @return mixed|null
     */
    private function array_get_or($property, $array, $default=null)
    {
        return array_key_exists($property, $array) ? $array[$property] : $default;
    }

    /**
     * Retrieve username, token from the current request, if present.
     *
     * @return array
     */
    private function getAuthenticationInfo()
    {
        $username = null;
        $token = null;
        $headers = getallheaders();

        # Determine if basic auth is being used.
        $useBasicAuth = false;
        try {
            $useBasicAuth = \xd_utilities\getConfiguration('rest', 'basic_auth') === 'on';
        } catch (Exception $e) {
        }

        # If we're using basic auth, than the username value should be included in the headers.
        if ($useBasicAuth) {
            $username = array_key_exists(Authentication::_DEFAULT_USER, $headers)
                ? $headers[Authentication::_DEFAULT_USER]
                : null;
        }

        # If $username is still not set ( because we're not using basic auth ) than check for it in the request parameters.
        if (!isset($username)) {
            $username = array_key_exists(Authentication::_DEFAULT_USER, $_REQUEST)
                ? $_REQUEST[Authentication::_DEFAULT_USER]
                : null;
        }

        # Now that we have th username, find where they've hid the token value.
        $tokenProperties = array(Authentication::_DEFAULT_TOKEN, Authentication::_DEFAULT_AUTH_TOKEN, Authentication::_DEFAULT_COOKIE_TOKEN);
        $tokenSources = array($_REQUEST, $_COOKIE, $headers);
        foreach ($tokenProperties as $tokenProperty) {
            foreach ($tokenSources as $tokenSource) {
                $token = array_key_exists($tokenProperty, $tokenSource) ? $tokenSource[$tokenProperty] : null;
                if (isset($token)) {
                    break;
                }
            }
        }

        return array(
            'username' => $username,
            'token' => $token
        );
    }
}

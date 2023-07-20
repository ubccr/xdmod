<?php

namespace CCR;

use Exception;
use Psr\Log\LoggerInterface;
use Rest\Utilities\Authentication;

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

    public function log($start, $end, $level = \CCR\Log::INFO)
    {
        $authInfo = $this->getAuthenticationInfo();

        $retval = array(
            'message' => 'Route called',
            'path' => $_SERVER['REQUEST_URI'],
            'query' => $_SERVER['QUERY_STRING'],
            'referer' => $_SERVER['HTTP_REFERER'],
            'elapsed' => $end - $start,
            'post' => $_POST,
            'data' => array(
                'ip' => $_SERVER['REMOTE_ADDR'],
                'method' => $_SERVER['REQUEST_METHOD'],
                'host' => $_SERVER['REMOTE_HOST'],
                'port' => $_SERVER['REMOTE_PORT'],
                'username' => $authInfo['username'],
                'token' => $authInfo['token'],
                'timestamp' => date("Y-m-d H:i:s", $_SERVER['REQUEST_TIME'])
            )
        );

        $this->logger->log($level, $retval);
    }

    /**
     * @return array
     */
    private function getAuthenticationInfo()
    {
        $username = null;
        $token = null;

        $useBasicAuth = false;
        try {
            $useBasicAuth = \xd_utilities\getConfiguration('rest', 'basic_auth') == 'on';
        } catch (Exception $e) {
        }

        $headers = getallheaders();
        if ($useBasicAuth) {
            if (array_key_exists(Authentication::_DEFAULT_USER, $headers)) {
                $username = $headers[Authentication::_DEFAULT_USER];
            }
        }
        if (!isset($username)) {
            $username = $_REQUEST[Authentication::_DEFAULT_USER];
        }

        $tokenProperties = array(Authentication::_DEFAULT_TOKEN, Authentication::_DEFAULT_AUTH_TOKEN, Authentication::_DEFAULT_COOKIE_TOKEN);
        $tokenSources = array($_REQUEST, $_COOKIE, $headers);
        foreach($tokenProperties as $tokenProperty) {
            foreach($tokenSources as $tokenSource) {
                $token = $tokenSource[$tokenProperty];
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

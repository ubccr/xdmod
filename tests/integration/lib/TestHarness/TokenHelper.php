<?php

namespace TestHarness;

use CCR\DB;
use Models\Services\Tokens;

/**
 *
 */
class TokenHelper
{
    private static $ENDPOINT = 'rest/users/current/api/token';
    private $testInstance;
    private $testHelper;
    private $role;
    private $path;
    private $verb;
    private $params;
    private $data;
    private $expectedOutputs = array(
        'empty_token' => array(),
        'malformed_token' => array(),
        'invalid_token' => array(),
        'expired_token' => array(),
        'deleted_token' => array()
    );

    public function __construct(
        $testInstance,
        $testHelper,
        $role,
        $path,
        $verb,
        $params,
        $data,
        $endpointType,
        $authenticationType
    ) {
        $this->testInstance = $testInstance;
        $this->testHelper = $testHelper;
        $this->role = $role;
        $this->path = $path;
        $this->verb = $verb;
        $this->params = $params;
        $this->data = $data;
        if ('token_optional' === $authenticationType) {
            foreach (array_keys($this->expectedOutputs) as $type) {
                if ('controller' === $endpointType) {
                    $fileName = 'session_expired';
                } elseif ('rest' === $endpointType) {
                    $fileName = 'authentication_error';
                }
                $this->setExpectedErrorOutput($type, 401, $fileName);
            }
        } elseif ('token_required' === $authenticationType) {
            $this->setExpectedErrorOutput('empty_token', 400);
            $this->setExpectedErrorOutput('malformed_token', 400);
            $this->setExpectedErrorOutput('invalid_token', 403);
            $this->setExpectedErrorOutput('expired_token', 400);
            $this->setExpectedErrorOutput('deleted_token', 400);
        }
    }

    public function runEndpointTests($callback)
    {
        if ('pub' === $this->role) {
            self::runStandardEndpointTest('', 'empty_token');
            self::runStandardEndpointTest('asdf', 'malformed_token');
        } else {
            $this->testHelper->authenticate($this->role);
            $this->testHelper->delete(self::$ENDPOINT);
            $response = $this->testHelper->post(self::$ENDPOINT, null, null);
            $token = $response[0]['data']['token'];
            $userId = substr($token, 0, strpos($token, Tokens::DELIMITER));
            $this->testHelper->logout();
            self::runStandardEndpointTest(
                $userId . Tokens::DELIMITER . 'asdf',
                'invalid_token'
            );
            $callback($token);
            self::expireToken($userId);
            self::runStandardEndpointTest($token, 'expired_token');
            $this->testHelper->authenticate($this->role);
            $this->testHelper->delete(self::$ENDPOINT);
            $this->testHelper->logout();
            self::runStandardEndpointTest($token, 'deleted_token');
        }
    }

    public function runEndpointTest(
        $token,
        $outputFileName = null,
        $httpCode = null,
        $outputTestGroup = 'integration/rest/user/api_token',
        $validationType = 'exact'
    ) {
        $defaultOutput = $this->expectedOutputs['empty_token'];
        if (null === $outputFileName) {
            $outputFileName = $defaultOutput['file_name'];
        }
        if (null === $httpCode) {
            $httpCode = $defaultOutput['http_code'];
        }
        $authHeader = $this->testHelper->getheader('Authorization');
        $this->testHelper->addheader(
            'Authorization',
            Tokens::HEADER_KEY . ' ' . $token
        );
        if (null === $this->params) {
            $this->params = array();
        }
        $this->params[Tokens::HEADER_KEY] = $token;
        $responseBody = $this->testInstance->makeRequest(
            $this->testHelper,
            $this->path,
            $this->verb,
            $this->params,
            $this->data,
            $httpCode,
            'application/json',
            $outputTestGroup,
            $outputFileName,
            $validationType
        );
        unset($this->params[Tokens::HEADER_KEY]);
        $this->testHelper->addheader('Authorization', $authHeader);
        return $responseBody;
    }

    /**
     * A helper function that will allow us to test the expiration of a token.
     *
     * Note: We need to directly access the database as we do not have an
     * endpoint for expiring a token.
     *
     * @param string $userId the userId whose token should be expired.
     *
     * @return bool true if the token was successfully expired.
     *
     * @throws Exception if there is a problem parsing the the provided
     *                   $rawToken.
     * @throws Exception if there is a problem connecting to or executing the
     *                   update statement against the database.
     */
    public static function expireToken($userId)
    {
        $db = DB::factory('database');
        $query = 'UPDATE moddb.user_tokens SET expires_on = SUBDATE(NOW(), 1)'
            . ' WHERE user_id = :user_id';
        $params = array(':user_id' => $userId);
        return $db->execute($query, $params) === 1;
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    private function setExpectedErrorOutput($type, $httpCode, $fileName = null)
    {
        if (null === $fileName) {
            $fileName = $type;
        }
        $this->expectedOutputs[$type] = array(
            'http_code' => $httpCode,
            'file_name' => $fileName
        );
    }

    private function runStandardEndpointTest($token, $type)
    {
        $this->runEndpointTest(
            $token,
            $this->expectedOutputs[$type]['file_name'],
            $this->expectedOutputs[$type]['http_code']
        );
    }
}

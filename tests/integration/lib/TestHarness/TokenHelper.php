<?php

namespace TestHarness;

use CCR\DB;
use Models\Services\Tokens;

/**
 *
 */
class TokenHelper
{
    private $testInstance;
    private $testHelper;
    private $role;
    private $path;
    private $verb;
    private $params;
    private $data;
    private $errorHttpCode;
    private $errorOutputFileName;

    public function __construct(
        $testInstance,
        $testHelper,
        $role,
        $path,
        $verb,
        $params,
        $data,
        $errorHttpCode,
        $errorOutputFileName
    ) {
        $this->testInstance = $testInstance;
        $this->testHelper = $testHelper;
        $this->role = $role;
        $this->path = $path;
        $this->verb = $verb;
        $this->params = $params;
        $this->data = $data;
        $this->errorHttpCode = $errorHttpCode;
        $this->errorOutputFileName = $errorOutputFileName;
    }

    public function runEndpointTests($callback)
    {
        if ('pub' === $this->role) {
            self::runEndpointTest('');
            self::runEndpointTest('asdf');
        } else {
            $this->testHelper->authenticate($this->role);
            $this->testHelper->delete('rest/users/current/api/token');
            $response = $this->testHelper->post(
                'rest/users/current/api/token',
                null,
                null
            );
            $token = $response[0]['data']['token'];
            $userId = substr($token, 0, strpos($token, Tokens::DELIMITER));
            $this->testHelper->logout();
            self::runEndpointTest($userId . Tokens::DELIMITER . 'asdf');
            $callback($token);
            self::expireToken($userId);
            self::runEndpointTest($token);
            $this->testHelper->authenticate($this->role);
            $this->testHelper->delete('rest/users/current/api/token');
            $this->testHelper->logout();
            self::runEndpointTest($token);
        }
    }

    public function runEndpointTest(
        $token,
        $httpCode = null,
        $outputTestGroup = 'integration/rest/user/api_token',
        $outputFileName = null
    ) {
        if (null === $httpCode) {
            $httpCode = $this->errorHttpCode;
        }
        if (null === $outputFileName) {
            $outputFileName = $this->errorOutputFileName;
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
        $this->testInstance->makeRequest(
            $this->testHelper,
            $this->path,
            $this->verb,
            $this->params,
            $this->data,
            $httpCode,
            'application/json',
            $outputTestGroup,
            $outputFileName,
            'exact'
        );
        unset($this->params[Tokens::HEADER_KEY]);
        $this->testHelper->addheader('Authorization', $authHeader);
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
}

<?php

namespace TestHarness;

use CCR\DB;
use CCR\Json;
use Exception;

/**
 *
 */
class TokenHelper
{

    /**
     * @var XdmodTestHelper
     */
    private $helper;

    /**
     * @var TestFiles
     */
    private $testFiles;


    /**
     *
     * @param XdmodTestHelper $helper
     * @param TestFiles $testFiles
     * @throws Exception
     */
    public function __construct($helper = null, $testFiles = null)
    {
        // if we aren't passed an XdmodTestHelper instance then create one ourselves.
        if (!isset($helper)) {
            $helper = new XdmodTestHelper();
        }

        $this->helper = $helper;

        if (!isset($testFiles)) {
            $testFiles = new TestFiles(__DIR__ . '/../../../');
        }
        $this->testFiles = $testFiles;
    }

    /**
     * Attempt to retrieve the metadata about the currently logged in users API Token. If any of the $expected* arguments
     * are provided then the function will attempt to validate that they match what is returned by the endpoint.
     *
     * @param int $expectedHttpCode
     * @param string $expectedContentType
     * @param string $expectedSchemaFileName
     * @return mixed
     * @throws Exception
     */
    public function getAPIToken($expectedHttpCode = null, $expectedContentType = null, $expectedSchemaFileName = null)
    {
        return $this->makeRequest(
            'Get API Token',
            'rest/users/current/api/token',
            'get',
            null,
            null,
            $expectedHttpCode,
            $expectedContentType,
            $expectedSchemaFileName
        );
    }

    /**
     * Attempt to create a new API Token for the currently logged in user.
     *
     * If any of the $expected* arguments are included than they will be used to validate the information returned from
     * the endpoint.
     *
     * @param $expectedHttpCode
     * @param $expectedContentType
     * @param $expectedSchemaName
     * @return object containing the api token value
     * @throws Exception
     */
    public function createAPIToken($expectedHttpCode = null, $expectedContentType = null, $expectedSchemaName = null)
    {
        return $this->makeRequest(
            'Create API Token',
            'rest/users/current/api/token',
            'post',
            null,
            null,
            $expectedHttpCode,
            $expectedContentType,
            $expectedSchemaName
        );
    }


    /**
     * Attempt to revoke the API token for the currently logged in user.
     *
     * If any of the $expected* arguments are included than they will be used to validate the information returned from
     * the endpoint.
     *
     *
     * @param int $expectedHttpCode
     * @param string $expectedContentType
     * @param string $expectedSchemaFileName
     * @return mixed the response body
     * @throws Exception
     */
    public function revokeAPIToken($expectedHttpCode = null, $expectedContentType = null, $expectedSchemaFileName = null)
    {
        return $this->makeRequest(
            'Revoke API Token',
            'rest/users/current/api/token',
            'delete',
            null,
            null,
            $expectedHttpCode,
            $expectedContentType,
            $expectedSchemaFileName
        );
    }

    /**
     * @param string $endPointDescription
     * @param string $url
     * @param string $verb
     * @param array|null $params
     * @param array|null $data
     * @param int|null $expectedHttpCode
     * @param string|null $expectedContentType
     * @param string|null $expectedSchemaFileName
     * @return mixed
     * @throws Exception
     */
    public function makeRequest(
        $endPointDescription,
        $url,
        $verb,
        $params = null,
        $data = null,
        $expectedHttpCode = null,
        $expectedContentType = null,
        $expectedSchemaFileName = null
    ) {
        $response = null;
        switch ($verb) {
            case 'get':
            case 'put':
                $response = $this->helper->$verb($url, $params);
                break;
            case 'post':
            case 'delete':
                $response = $this->helper->$verb($url, $params, $data);
                break;
        }
        $actualHttpCode = isset($response) ? $response[1]['http_code'] : null;
        $actualContentType = isset($response) ? $response[1]['content_type'] : null;
        $actualResponseBody = isset($response) ? $response[0] : array();

        if (isset($expectedHttpCode) && $expectedHttpCode !== $actualHttpCode) {
            print_r($response);
            throw new Exception(
                sprintf(
                    'HTTP Code does not match. Expected: %s Received: %s',
                    $expectedHttpCode,
                    $actualHttpCode
                )
            );
        }
        if (isset($expectedContentType) && $expectedContentType !== $actualContentType) {
            print_r($response);
            throw new Exception(
                sprintf(
                    'HTTP Content Type does not match. Expected: %s Received: %s',
                    $expectedContentType,
                    $actualContentType
                )
            );
        }

        $actual = json_decode(json_encode($actualResponseBody));

        if (isset($expectedSchemaFileName)) {
            $validator = new \JsonSchema\Validator();
            $expectedSchema = Json::loadFile(
                $this->testFiles->getFile('schema/integration', $expectedSchemaFileName, ''),
                false
            );

            $validator->validate($actual, $expectedSchema);

            if (!$validator->isValid()) {
                throw new Exception(sprintf(
                    "%s response is in an invalid format.\nExpected:%s\nReceived %s",
                    $endPointDescription,
                    json_encode($expectedSchema, JSON_PRETTY_PRINT),
                    var_export($actual, true)
                ));
            }
        }

        return $actual;
    }

    /**
     * A helper function that will allow us to test the expiration of a token.
     *
     * Note: We need to directly access the database as we do not have an endpoint for expiring
     * a token.
     *
     * @param string $userId the userId whose token should be expired.
     *
     * @return bool true if the token was successfully expired.
     *
     * @throws Exception if there is a problem parsing the the provided $rawToken.
     * @throws Exception if there is a problem connecting to or executing the update statement against the database.
     */
    public function expireToken($userId)
    {
        $db = DB::factory('database');
        $query = 'UPDATE moddb.user_tokens SET expires_on = NOW() WHERE user_id = :user_id';
        $params = array(':user_id' => $userId);
        return $db->execute($query, $params) === 1;
    }


    /**
     * Calls `authenticate($user)` on this TokenHelper instances XdmodTestHelper.
     *
     * @param string $user
     * @return void
     * @throws Exception
     */
    public function authenticate($user)
    {
        $this->helper->authenticate($user);
    }

    /**
     * Calls `logout` on this TokenHelper instances XdmodTestHelper.
     * @return void
     */
    public function logout()
    {
        $this->helper->logout();
    }

    /**
     * Retrieve the XdmodTestHelper used by the TokenHelper.
     * @return XdmodTestHelper
     */
    public function getHelper()
    {
        return $this->helper;
    }
}

<?php

namespace TestHarness;

use CCR\DB;
use CCR\Json;
use Exception;
use IntegrationTests\BaseTest;

/**
 *
 */
abstract class TokenHelper
{
    /**
     * Attempt to retrieve the metadata about the currently logged in users API Token. If any of the $expected* arguments
     * are provided then the function will attempt to validate that they match what is returned by the endpoint.
     *
     * @param BaseTest $testInstance
     * @param XdmodTestHelper $testHelper
     * @param int $expectedHttpCode
     * @param string $expectedContentType
     * @param string $expectedSchemaFileName
     * @return mixed
     * @throws Exception
     */
    public static function getAPIToken(
        $testInstance,
        $testHelper,
        $expectedHttpCode = null,
        $expectedContentType = null,
        $expectedSchemaFileName = null
    ) {
        return self::makeRequest(
            $testInstance,
            $testHelper,
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
     * @param BaseTest $testInstance
     * @param XdmodTestHelper $testHelper
     * @param $expectedHttpCode
     * @param $expectedContentType
     * @param $expectedSchemaFileName
     * @return object containing the api token value
     * @throws Exception
     */
    public static function createAPIToken(
        $testInstance,
        $testHelper,
        $expectedHttpCode = null,
        $expectedContentType = null,
        $expectedSchemaFileName = null
    ) {
        return self::makeRequest(
            $testInstance,
            $testHelper,
            'rest/users/current/api/token',
            'post',
            null,
            null,
            $expectedHttpCode,
            $expectedContentType,
            $expectedSchemaFileName
        );
    }


    /**
     * Attempt to revoke the API token for the currently logged in user.
     *
     * If any of the $expected* arguments are included than they will be used to validate the information returned from
     * the endpoint.
     *
     *
     * @param BaseTest $testInstance
     * @param XdmodTestHelper $testHelper
     * @param int $expectedHttpCode
     * @param string $expectedContentType
     * @param string $expectedSchemaFileName
     * @return mixed the response body
     * @throws Exception
     */
    public static function revokeAPIToken(
        $testInstance,
        $testHelper,
        $expectedHttpCode = null,
        $expectedContentType = null,
        $expectedSchemaFileName = null
    ) {
        return self::makeRequest(
            $testInstance,
            $testHelper,
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
     * @param BaseTest $testInstance
     * @param XdmodTestHelper $testHelper
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
    public static function makeRequest(
        $testInstance,
        $testHelper,
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
                $response = $testHelper->$verb($url, $params);
                break;
            case 'post':
            case 'delete':
                $response = $testHelper->$verb($url, $params, $data);
                break;
        }
        $actualHttpCode = isset($response) ? $response[1]['http_code'] : null;
        $actualContentType = isset($response) ? $response[1]['content_type'] : null;
        $actualResponseBody = isset($response) ? $response[0] : array();

        if (isset($expectedHttpCode)) {
            // Note $expectedHttpCode was changed to support being an array due to el7 returning 400 where el8 returns
            // 401.
            if (is_numeric($expectedHttpCode)) {
                $testInstance->assertSame($actualHttpCode, $expectedHttpCode);
            } elseif (is_array($expectedHttpCode)) {
                $testInstance->assertContains(
                    $actualHttpCode,
                    $expectedHttpCode
                );
            }
        }
        if (isset($expectedContentType)) {
            $testInstance->assertSame(
                $actualContentType,
                $expectedContentType
            );
        }

        $actual = json_decode(json_encode($actualResponseBody));

        if (isset($expectedSchemaFileName)) {
            $testInstance->validateJson(
                $actual,
                'schema/integration',
                $expectedSchemaFileName,
                ''
            );
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
    public static function expireToken($userId)
    {
        $db = DB::factory('database');
        $query = 'UPDATE moddb.user_tokens SET expires_on = NOW() WHERE user_id = :user_id';
        $params = array(':user_id' => $userId);
        return $db->execute($query, $params) === 1;
    }
}

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
     * @param int|null $expectedHttpCode
     * @param string|null $expectedContentType
     * @param string|null $expectedSchemaFileName
     * @return mixed the response body
     */
    public static function getAPIToken(
        $testInstance,
        $testHelper,
        $expectedHttpCode = null,
        $expectedContentType = null,
        $expectedSchemaFileName = null
    ) {
        return $testInstance->makeRequest(
            $testHelper,
            'rest/users/current/api/token',
            'get',
            null,
            null,
            $expectedHttpCode,
            $expectedContentType,
            'schema/integration',
            $expectedSchemaFileName,
            ''
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
     * @param int|null $expectedHttpCode
     * @param string|null $expectedContentType
     * @param string|null $expectedSchemaFileName
     * @return mixed the response body
     */
    public static function createAPIToken(
        $testInstance,
        $testHelper,
        $expectedHttpCode = null,
        $expectedContentType = null,
        $expectedSchemaFileName = null
    ) {
        return $testInstance->makeRequest(
            $testHelper,
            'rest/users/current/api/token',
            'post',
            null,
            null,
            $expectedHttpCode,
            $expectedContentType,
            'schema/integration',
            $expectedSchemaFileName,
            ''
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
     * @param int|null $expectedHttpCode
     * @param string|null $expectedContentType
     * @param string|null $expectedSchemaFileName
     * @return mixed the response body
     */
    public static function revokeAPIToken(
        $testInstance,
        $testHelper,
        $expectedHttpCode = null,
        $expectedContentType = null,
        $expectedSchemaFileName = null
    ) {
        return $testInstance->makeRequest(
            $testHelper,
            'rest/users/current/api/token',
            'delete',
            null,
            null,
            $expectedHttpCode,
            $expectedContentType,
            'schema/integration',
            $expectedSchemaFileName,
            ''
        );
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

<?php

namespace IntegrationTests;

use CCR\DB;
use Models\Services\Tokens;
use TestHarness\XdmodTestHelper;

/**
 * Provides methods for testing API token authentication for HTTP endpoints.
 */
abstract class TokenAuthTest extends BaseTest
{
    /**
     * HTTP path for endpoint that creates, reads, and deletes API tokens.
     */
    const TOKEN_CRD_ENDPOINT = 'rest/users/current/api/token';

    /**
     * Valid, expired, and revoked tokens for each of the non-public base roles
     * (@see BaseTest::getBaseRoles()). Generated as a 2D array when it is
     * first needed (@see self::getToken()) and stored statically for use by
     * all tests that need the tokens. Indexed first by string role (e.g.,
     * 'cd') and then by string token type (e.g., 'valid_token').
     */
    private static $tokens = null;

    /**
     * User IDs for each of the base roles (@see BaseTest::getBaseRoles()),
     * used to expire and unexpire tokens. Generated when they are first needed
     * (@see self::getToken()) and stored statically for use by all tests that
     * need them.
     */
    private static $userIds = [];

    /**
     * A dataProvider for testing token authentication on a given endpoint.
     * Provides data for testing empty and malformed tokens using the public
     * user and for testing invalid, valid, expired, and revoked tokens for
     * each of the base roles (@see BaseTest::getBaseRoles()).
     */
    public function provideTokenAuthTestData()
    {
        $testData = [
            ['pub', 'empty_token'],
            ['pub', 'malformed_token']
        ];
        foreach (parent::getBaseRoles() as $role) {
            if ('pub' === $role) {
                continue;
            }
            $testData[] = [$role, 'invalid_token'];
            $testData[] = [$role, 'expired_token'];
            $testData[] = [$role, 'revoked_token'];
            $testData[] = [$role, 'valid_token'];
        }
        return $testData;
    }

    /**
     * Make an HTTP request to an endpoint involving API token authentication
     * and validate the response.
     *
     * A JSON object is loaded from the provided test input artifact file to
     * specify how to make the HTTP request. This file needs to have the
     * required keys (@see BaseTest::REQUIRED_ENDPOINT_TEST_KEYS['input']) as
     * well as an 'endpoint_type' key whose value is either 'controller' or
     * 'rest' and an 'authentication_type' key whose value is either
     * 'token_optional' or 'token_required'.
     *
     * If this is testing an error in authentication, the expected response
     * status codes and bodies are defined in the test artifact file
     * integration/token_auth/output/errors.json. If this is instead testing a
     * successful authentication, the expected response status code and body
     * must be defined in the provided test output artifact file (@see
     * BaseTest::REQUIRED_ENDPOINT_TEST_KEYS['output']).
     *
     * @param string $role the user role to use when authenticating.
     * @param string $tokenType the type of token being provided
     *                          (@see provideTokenAuthTestData()); if testing
     *                          an authentication error, this is also the key
     *                          in the test output artifact that describes the
     *                          expected response.
     * @param string $testGroup the directory containing the JSON test input
     *                          artifact that describes the HTTP request to be
     *                          made, and, if $tokenType is 'valid_token', the
     *                          output artifact that describes how to validate
     *                          the HTTP response body.
     * @param string $fileName the name of the JSON test input artifact file
     *                         that describes the HTTP request to be made, and,
     *                         if $tokenType is 'valid_token', the output
     *                         artifact that describes how to validate the HTTP
     *                         response body.
     * @param string|null $testKey if the test artifact files contain multiple
     *                             keys, a single key must be provided to
     *                             indicate which test to run, and this key
     *                             must be present in both the input and output
     *                             test artifacts.
     * @return mixed the decoded JSON response body of the request.
     * @throws Exception if the role is unrecognized or there is an error
     *                   parsing the input or output files, making HTTP
     *                   requests, or validating the response.
     */
    public function runTokenAuthTest(
        $role,
        $tokenType,
        $testGroup,
        $fileName,
        $testKey = null
    ) {
        // Load the test input artifact.
        $input = parent::loadJsonTestArtifact($testGroup, $fileName, 'input');

        // If the test input artifact contains multiple test keys, load the
        // test from the provided key, and store the path to the artifact file
        // so it can be displayed in test assertion failure messages.
        if (!is_null($testKey)) {
            $path = $input['$path'] . "#$testKey";
            $input = $input[$testKey];
            $input['$path'] = $path;
        }

        // Make sure the input object has the additional required keys.
        parent::assertRequiredKeys(
            ['endpoint_type', 'authentication_type'],
            $input,
            '$input'
        );

        if ('valid_token' === $tokenType) {
            $token = self::getToken($role, 'valid_token');

            // Load the test output artifact that describes the successful
            // authentication response.
            $output = parent::loadJsonTestArtifact(
                $testGroup,
                $fileName,
                'output'
            );
        } else {
            // Construct the desired type of token.
            if ('empty_token' === $tokenType) {
                $token = '';
            } elseif ('malformed_token' === $tokenType) {
                $token = 'asdf';
            } elseif ('invalid_token' === $tokenType) {
                $token = self::getToken($role, 'invalid_token');
            } elseif ('expired_token' === $tokenType) {
                // Expire the token (it will be unexpired at the end of this
                // test).
                self::updateTokenExpirationDate(
                    self::$userIds[$role],
                    'SUBDATE(NOW(), 1)'
                );
                // Load the token for use in this test.
                $token = self::getToken($role, 'valid_token');
            } elseif ('revoked_token' === $tokenType) {
                $token = self::getToken($role, 'revoked_token');
            } else {
                throw new Exception(
                    'Unknown value for $tokenType: "' . $tokenType . '".'
                );
            }

            // If the endpoint can authenticate via a method other than API
            // token (i.e., its 'authentication_type' is 'token_optional'), the
            // response on authentication failure will be different depending
            // on whether the endpoint is defined in 'html/controllers' (in
            // which case it will be 'Session Expired') or 'classes/Rest' (in
            // which case it will be 'authentication error').
            if ('token_optional' === $input['authentication_type']) {
                if ('controller' === $input['endpoint_type']) {
                    $testKey = 'session_expired';
                } elseif ('rest' === $input['endpoint_type']) {
                    $testKey = 'authentication_error';
                } else {
                    throw new Exception(
                        "Unknown value for endpoint_type:"
                        . " '$input[endpoint_type]'."
                    );
                }
            // If the endpoint requires a token for authentication, there is a
            // separate key in the output test artifact for each token type.
            } elseif ('token_required' === $input['authentication_type']) {
                $testKey = $tokenType;
            } else {
                throw new Exception(
                    'Unknown value for authentication_type:'
                    . " '$input[authentication_type]'."
                );
            }

            // Load the test output artifact that describes how to validate the
            // authentication error response.
            $output = parent::loadJsonTestArtifact(
                'integration/token_auth',
                'errors',
                'output'
            );
        }

        // Store the path to the artifact file so it can be displayed in test
        // assertion failure messages.
        if (is_null($testKey)) {
            $output['body']['$path'] = $output['$path'];
        } else {
            $path = $output['$path'];
            // Load the specific output object for the given test key.
            $output = $output[$testKey];
            $output['body']['$path'] = "$path#$testKey";
        }

        // Do one request with the token in both the header and the query
        // parameters (because the Apache server eats the 'Authorization'
        // header on EL7) and one request with the token only in the query
        // parameters, to make sure the result is the same.
        $actualBodies = [];
        foreach (['token_in_header', 'token_not_in_header'] as $mode) {
            // Construct a test helper for making the request.
            $helper = new XdmodTestHelper();

            // Add the token to the header.
            if ('token_in_header' === $mode) {
                $helper->addheader(
                    'Authorization',
                    Tokens::HEADER_KEY . ' ' . $token
                );
            }

            // Add the token to the query parameters.
            parent::assertRequiredKeys(['params'], $input, '$input');
            if (is_null($input['params'])) {
                $input['params'] = [];
            }
            $input['params'][Tokens::HEADER_KEY] = $token;

            // Make the request and validate the response.
            $actualBodies[$mode] = parent::requestAndValidateJson(
                $helper,
                $input,
                $output
            );
        }
        $this->assertSame(
            json_encode($actualBodies['token_in_header']),
            json_encode($actualBodies['token_not_in_header']),
            json_encode(
                $actualBodies['token_in_header'],
                JSON_PRETTY_PRINT
            )
            . "\n"
            . json_encode(
                $actualBodies['token_not_in_header'],
                JSON_PRETTY_PRINT
            )
        );
        // If the token is expired, unexpire it.
        if ('expired_token' === $tokenType) {
            self::updateTokenExpirationDate(
                self::$userIds[$role],
                'DATE_ADD(NOW(), INTERVAL 1 DAY)'
            );
        }
        return $actualBodies['token_in_header'];
    }

    /**
     * Nullify the list of generated tokens. This is useful if another test
     * invalidated them, e.g.,
     * Controllers\UserControllerProviderTest::testAPITokensCRD().
     */
    public static function nullifyTokens()
    {
        self::$tokens = null;
    }

    /**
     * Given a test input artifact containing multiple keys, generate test data
     * that can be returned by a dataProvider. The test data contains each
     * role, token type, and — if the token type is 'valid_token' — test key.
     * For error token types, the test key is set only to 'defaults'.
     *
     * @param string $testGroup the test group of the test input artifact.
     * @param string $fileName the file name of the test input artifact.
     * @return array the array of test data.
     */
    protected static function provideTokenAuthTestDataWithMultipleKeys(
        $testGroup,
        $fileName
    ) {
        // Get the test keys out of the test input artifact file.
        $input = parent::loadJsonTestArtifact($testGroup, $fileName, 'input');
        unset($input['$path']);
        $testKeys = array_keys($input);

        // Build the arrays of test data.
        $testData = [];
        foreach (self::provideTokenAuthTestData() as $roleAndTokenType) {
            list($role, $tokenType) = $roleAndTokenType;
            if ('valid_token' === $tokenType) {
                foreach ($testKeys as $testKey) {
                    $testData["$role-$tokenType-$testKey"] = [
                        $role,
                        $tokenType,
                        $testKey
                    ];
                }
            } else {
                $testData["$role-$tokenType-defaults"] = [
                    $role,
                    $tokenType,
                    'defaults'
                ];
            }
        }
        return $testData;
    }

    /**
     * Return the generated token for the given role and token type.
     *
     * @param string $role one of the non-public roles from
     *                     @see BaseTest::getBaseRoles().
     * @param string $tokenType either 'valid_token', 'invalid_token', or
     *                          'revoked_token'.
     * @return string the token.
     */
    private static function getToken($role, $tokenType)
    {
        // If the valid, invalid, and revoked tokens have not already been
        // generated for the roles, generate them.
        if (is_null(self::$tokens)) {
            self::$tokens = [];
            foreach (parent::getBaseRoles() as $baseRole) {
                if ('pub' === $baseRole) {
                    continue;
                }
                self::$tokens[$baseRole] = [];

                // Construct a test helper for making HTTP requests to create
                // and revoke tokens.
                $helper = new XdmodTestHelper();

                // Log the user in so tokens can be created for the role.
                $helper->authenticate($baseRole);

                // User tokens cannot be obtained after they have been created,
                // so if the user already has a token, we don't know what it is
                // and need to revoke it before creating a new one.
                $helper->delete(self::TOKEN_CRD_ENDPOINT);

                // Create a new token.
                $token = self::createToken($helper);

                // Store the role's user ID so it can be used to create an
                // invalid token and later used for expiring and unexpiring
                // tokens.
                self::$userIds[$baseRole] = substr(
                    $token,
                    0,
                    strpos($token, Tokens::DELIMITER)
                );

                // Create and store an invalid token.
                self::$tokens[$baseRole]['invalid_token'] = (
                    self::$userIds[$baseRole] . Tokens::DELIMITER . 'asdf'
                );

                // Revoke the created token and store it.
                $helper->delete(self::TOKEN_CRD_ENDPOINT);
                self::$tokens[$baseRole]['revoked_token'] = $token;

                // Create a new token and store it.
                self::$tokens[$baseRole]['valid_token'] = self::createToken(
                    $helper
                );
            }
        }
        return self::$tokens[$role][$tokenType];
    }

    /**
     * Create an API token.
     *
     * @param XdmodTestHelper $helper used to make HTTP requests.
     * @return string the token.
     * @throws Exception if there is an error making HTTP requests.
     */
    private static function createToken($helper)
    {
        $response = $helper->post(
            self::TOKEN_CRD_ENDPOINT,
            null,
            null
        );
        return $response[0]['data']['token'];
    }

    /**
     * Set the new expiration date of the given role's token.
     *
     * @param string $userId the ID of the role.
     * @param string $newDate the new date's SQL value.
     */
    private static function updateTokenExpirationDate($userId, $newDate)
    {
        // We need to directly access the database as we do not have an
        // endpoint for expiring/unexpiring a token.
        $db = DB::factory('database');
        $query = "UPDATE moddb.user_tokens SET expires_on = $newDate"
            . ' WHERE user_id = :user_id';
        $params = [':user_id' => $userId];
        $db->execute($query, $params);
    }
}

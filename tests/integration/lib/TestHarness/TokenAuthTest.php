<?php

namespace TestHarness;

use CCR\DB;
use Exception;
use IntegrationTests\BaseTest;
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
    private static $TOKEN_CRD_ENDPOINT = 'rest/users/current/api/token';

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
     * @param string $inputTestGroup the directory containing the JSON test
     *                               input artifact that describes the HTTP
     *                               request to be made.
     * @param string $inputFileName the name of the JSON test input artifact
     *                              file that describes the HTTP request
     *                              to be made.
     * @param string|null $testKey if the test artifact files contain multiple
     *                             keys, a single key must be provided to
     *                             indicate which test to run, and this key
     *                             must be present in both the input and output
     *                             test artifacts.
     * @param string|null $outputTestGroup if not null, the directory
     *                                     containing the JSON test output
     *                                     artifact that describes how to
     *                                     validate the HTTP response body when
     *                                     authentication is successful. If
     *                                     null, set to be equal to
     *                                     $inputTestGroup.
     * @param string|null $outputFileName if not null, the name of the JSON
     *                                    test output artifact file that
     *                                    describes how to validate the HTTP
     *                                    response body when authentication is
     *                                    successful.  If null, set to be equal
     *                                    to $inputFileName.
     * @return mixed the decoded JSON response body of the request.
     * @throws Exception if the role is unrecognized or there is an error
     *                   parsing the input or output files, making HTTP
     *                   requests, or validating the response.
     */
    public function runTokenAuthTest(
        $role,
        $tokenType,
        $inputTestGroup,
        $inputFileName,
        $testKey = null,
        $outputTestGroup = null,
        $outputFileName = null
    ) {
        if (is_null($outputTestGroup)) {
            $outputTestGroup = $inputTestGroup;
        }
        if (is_null($outputFileName)) {
            $outputFileName = $inputFileName;
        }
        $input = parent::loadJsonTestArtifact(
            $inputTestGroup,
            $inputFileName,
            'input'
        );

        // Make sure the input object has the additional required keys.
        parent::assertRequiredKeys(
            ['endpoint_type', 'authentication_type'],
            $input,
            '$input'
        );

        // Store the path to the input object for displaying in test assertion
        // failure messages.
        if (!is_null($testKey)) {
            $path = $input['$path'] . "#$testKey";
            $input = $input[$testKey];
            $input['$path'] = $path;
        }

        if ('valid_token' === $tokenType) {
            // If the token should be valid, load the test output artifact that
            // describes the successful authentication response.
            $output = parent::loadJsonTestArtifact(
                $outputTestGroup,
                $outputFileName,
                'output'
            );
            $path = $output['$path'];

            // Create the token.
            $token = self::createToken($role);
        } else {
            // Construct the desired type of token.
            if ('empty_token' === $tokenType) {
                $token = '';
            } else if ('malformed_token' === $tokenType) {
                $token = 'asdf';
            } else if ('invalid_token' === $tokenType) {
                // Create a valid token so we can break it apart to make an
                // invalid token.
                $token = self::createToken($role);
                $userId = self::getUserId($token);
                $token = $userId . Tokens::DELIMITER . 'asdf';
            } else if ('expired_token' === $tokenType) {
                // Create a valid token so we can expire it.
                $token = self::createToken($role);
                $userId = self::getUserId($token);
                // We need to directly access the database as we do not have an
                // endpoint for expiring a token.
                $db = DB::factory('database');
                $query = 'UPDATE moddb.user_tokens'
                    . ' SET expires_on = SUBDATE(NOW(), 1)'
                    . ' WHERE user_id = :user_id';
                $params = [':user_id' => $userId];
                $db->execute($query, $params);
            } else if ('revoked_token' === $tokenType) {
                // Create a token so we can revoke it.
                $token = self::createAndRevokeToken($role);
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
                    $tokenType = 'session_expired';
                } elseif ('rest' === $input['endpoint_type']) {
                    $tokenType = 'authentication_error';
                } else {
                    throw new Exception(
                        "Unknown value for endpoint_type:"
                        . " '$input[endpoint_type]'."
                    );
                }
            } elseif ('token_required' !== $input['authentication_type']) {
                throw new Exception(
                    'Unknown value for authentication_type:'
                    . " '$input[authentication_type]'."
                );
            }
            // Load the test output artifact that describes the successful
            // authentication response.
            $output = parent::loadJsonTestArtifact(
                'integration/token_auth',
                'errors',
                'output'
            );
            $path = $output['$path'];
            $output = $output[$tokenType];
        }
        // Store the path to the output object for displaying in test assertion
        // failure messages.
        if (!is_null($testKey)) {
            $output = $output[$testKey];
            $output[$testKey]['body']['$path'] = "$path#$testKey";
        } else {
            $output['body']['$path'] = $path;
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
        return $actualBodies['token_in_header'];
    }

    /**
     * Create an API token.
     *
     * @param string $role for whom to create the token.
     * @param XdmodTestHelper $helper used to authenticate the user and make
     *                                HTTP requests.
     * @return string the API token.
     * @throws Exception if there is an error making HTTP requests.
     */
    private static function createToken($role, $helper = null)
    {
        if (is_null($helper)) {
            $helper = new XdmodTestHelper();
        }
        // Log the user in so a token can be created.
        $helper->authenticate($role);

        // User tokens cannot be obtained after they have been created, so
        // if the user already has a token, we don't know what it is and
        // need to revoke it and create a new one.
        $helper->delete(self::$TOKEN_CRD_ENDPOINT);

        // Create a new token.
        $response = $helper->post(
            self::$TOKEN_CRD_ENDPOINT,
            null,
            null
        );
        return $response[0]['data']['token'];
    }

    /**
     * Extract a user's ID from the given token.
     *
     * @param string $token
     * @return string
     */
    private static function getUserId($token)
    {
        return substr($token, 0, strpos($token, Tokens::DELIMITER));
    }

    /**
     * Create and revoke a token.
     *
     * @param $role for whom to create and revoke the token.
     * @return string the token.
     * @throws Exception if there is an error making HTTP requests.
     */
    private static function createAndRevokeToken($role)
    {
        $helper = new XdmodTestHelper();
        $token = self::createToken($role, $helper);
        $helper->delete(self::$TOKEN_CRD_ENDPOINT);
        return $token;
    }
}

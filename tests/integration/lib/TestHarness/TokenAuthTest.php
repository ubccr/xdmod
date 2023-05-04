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
     * Make an HTTP request to an endpoint multiple times, testing a variety of
     * token authentication errors as well as a successful authentication.  If
     * the user is a public user, the error conditions tested are an empty
     * token and a malformed token. If the user is non-public, the error
     * conditions tested are an invalid token, an expired token, and a deleted
     * token, and a successful authentication is also testsed. If a token
     * already exists for the given user when this method is called, the token
     * is first revoked. A new token is created that is also revoked at the
     * end of the method call.  The user is also logged out at the end of the
     * method call.
     *
     * A JSON object is loaded from the provided test input artifact file to
     * specify how to make the HTTP request. This file needs to have the
     * required keys (@see TokenAuthTest::runErrorTest()).  For the error
     * tests, the expected response status codes and bodies are defined in the
     * test artifact file integration/token_auth/output/errors.json. For the
     * successful authentication test, the expected response status code and
     * body are defined in the provided test output artifact file.
     *
     * @param string $role the user role to use when authenticating.
     * @param string $inputTestGroup the directory containing the JSON test
     *                               artifact that describes the HTTP request
     *                               to be made.
     * @param string $inputFileName the name of the JSON test artifact
     *                              file that describes the HTTP request
     *                              to be made.
     * @param string|null $outputTestGroup if not null, the directory
     *                                     containing the JSON test artifact
     *                                     that describes how to validate the
     *                                     HTTP response body when
     *                                     authentication is successful. If
     *                                     null, set to be equal to
     *                                     $inputTestGroup.
     * @param string|null $outputFileName if not null, the name of the JSON
     *                                    test artifact file that describes how
     *                                    to validate the HTTP response body
     *                                    when authentication is successful.
     *                                    If null, set to be equal to
     *                                    $inputFileName.
     * @return mixed|null the decoded JSON response body of the request
     *                    involving successful authentication, or null if there
     *                    was no successful authentication.
     * @throws Exception if the role is unrecognized or there is an error
     *                   parsing the input or output files, making any of the
     *                   requests, or validating any of the responses.
     */
    protected function runTokenAuthTests(
        $role,
        $inputTestGroup,
        $inputFileName,
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
        $output = parent::loadJsonTestArtifact(
            $outputTestGroup,
            $outputFileName,
            'output'
        );
        $successBody = null;
        if ('pub' === $role) {
            // Make a request with an empty token.
            $this->runErrorTest(
                $input,
                '',
                'empty_token'
            );
            // Make a request with a malformed token.
            $this->runErrorTest(
                $input,
                'asdf',
                'malformed_token'
            );
        } else {
            // Construct a test helper for making HTTP requests to create and
            // revoke tokens.
            $testHelper = new XdmodTestHelper();

            // Log the user in so a token can be created for them.
            $testHelper->authenticate($role);

            // User tokens cannot be obtained after they have been created,
            // so if the user already has a token, we don't know what it is and
            // need to revoke it and create a new one.
            $testHelper->delete(self::$TOKEN_CRD_ENDPOINT);

            // Create a new token for the user.
            $response = $testHelper->post(
                self::$TOKEN_CRD_ENDPOINT,
                null,
                null
            );
            $token = $response[0]['data']['token'];

            // Get the user's ID so it can be used to construct an invalid
            // token.
            $userId = substr($token, 0, strpos($token, Tokens::DELIMITER));

            // Log the user out so the token is the only form of authentication
            // used.
            $testHelper->logout();

            // Make a request with an invalid token.
            $this->runErrorTest(
                $input,
                $userId . Tokens::DELIMITER . 'asdf',
                'invalid_token'
            );

            // Make a request with the valid token.
            $successBody = $this->runTokenAuthTest($input, $token, $output);

            // Expire the token and make a request with it.
            self::expireToken($userId);
            $this->runErrorTest(
                $input,
                $token,
                'expired_token'
            );

            // Log the user in and revoke their token.
            $testHelper->authenticate($role);
            $testHelper->delete(self::$TOKEN_CRD_ENDPOINT);

            // Log the user back out and make a request with the revoked token.
            $testHelper->logout();
            $this->runErrorTest(
                $input,
                $token,
                'revoked_token'
            );
        }
        return $successBody;
    }

    /**
     * Make an HTTP request involving an error in token authentication and
     * validate the response.
     *
     * @param array $input associative array describing the HTTP request to be
     *                     made. Needs to have the required keys
     *                     (@see BaseTest::REQUIRED_ENDPOINT_TEST_KEYS) as well
     *                     as an 'endpoint_type' key whose value is either
     *                     'controller' or 'rest' and an 'authentication_type'
     *                     key whose value is either 'token_optional' or
     *                     'token_required'.
     * @param string $token the API token to use for authentication.
     * @param string $type the type of expected error ('empty_token',
     *                     'malformed_token', 'invalid_token', 'expired_token',
     *                     or 'revoked_token').
     * @return mixed the decoded JSON response body.
     * @throws Exception if an unknown value of 'endpoint_type' or
     *                   'authentication_type' is provided or there is an error
     *                   while making the request or validating the response.
     */
    private function runErrorTest($input, $token, $type)
    {
        // Make sure the input object has the additional required keys.
        parent::assertRequiredKeys(
            ['endpoint_type', 'authentication_type'],
            $input,
            '$input'
        );
        // If the endpoint can authenticate via a method other than API token
        // (i.e., its 'authentication_type' is 'token_optional'), the response
        // on authentication failure will be different depending on whether the
        // endpoint is defined in 'html/controllers' (in which case it will be
        // 'Session Expired') or 'classes/Rest' (in which case it will be
        // 'authentication error').
        if ('token_optional' === $input['authentication_type']) {
            if ('controller' === $input['endpoint_type']) {
                $type = 'session_expired';
            } elseif ('rest' === $input['endpoint_type']) {
                $type = 'authentication_error';
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
        $output = parent::loadJsonTestArtifact(
            'integration/token_auth',
            'errors',
            'output'
        );
        return $this->runTokenAuthTest($input, $token, $output[$type]);
    }

    /**
     * Make an HTTP request using an API token for authentication and validate
     * the response.
     *
     * @param array $input associative array describing the HTTP request to be
     *                     made. Needs to have the required keys
     *                     (@see BaseTest::REQUIRED_ENDPOINT_TEST_KEYS).
     * @param string $token the API token to use for authentication.
     * @param array $output associative array describing the expected HTTP
     *                      response status code and JSON body. Needs to have
     *                      the required keys
     *                      (@see BaseTest::REQUIRED_ENDPOINT_TEST_KEYS).
     * @return mixed the decoded JSON response body.
     * @throws Exception if the input object does not contain all of the
     *                   required keys or if there is an error making the
     *                   request or validating the response.
     */
    private function runTokenAuthTest($input, $token, $output)
    {
        // Construct a test helper for making the request.
        $testHelper = new XdmodTestHelper();
        // Add the token to both the header and query parameters since the
        // Apache server eats the 'Authorization' header on EL7.
        $testHelper->addheader(
            'Authorization',
            Tokens::HEADER_KEY . ' ' . $token
        );
        parent::assertRequiredKeys(['params'], $input, '$input');
        if (is_null($input['params'])) {
            $input['params'] = [];
        }
        $input['params'][Tokens::HEADER_KEY] = $token;
        $actualBody = parent::requestAndValidateJson(
            $testHelper,
            $input,
            $output
        );
        return $actualBody;
    }

    /**
     * Expire a token.
     *
     * @param string $userId the ID of the user whose token should be expired.
     * @return bool true if the token was successfully expired.
     * @throws Exception if there is an error parsing the user's token or
     *                   connecting to or querying the database.
     */
    private static function expireToken($userId)
    {
        // We need to directly access the database as we do not have an
        // endpoint for expiring a token.
        $db = DB::factory('database');
        $query = 'UPDATE moddb.user_tokens SET expires_on = SUBDATE(NOW(), 1)'
            . ' WHERE user_id = :user_id';
        $params = array(':user_id' => $userId);
        return (1 === $db->execute($query, $params));
    }
}
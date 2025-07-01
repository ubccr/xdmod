<?php

namespace IntegrationTests;

use CCR\DB;
use Exception;
use Models\Services\Tokens;
use Models\Services\JsonWebToken;
use IntegrationTests\TestHarness\XdmodTestHelper;

/**
 * Provides methods for testing API token authentication for HTTP endpoints.
 */
abstract class TokenAuthTest extends BaseTest
{
    /**
     * HTTP path for endpoint that creates, reads, and deletes API tokens.
     */
    const API_TOKEN_CRD_ENDPOINT = 'rest/users/current/api/token';

    /**
     * Valid, expired, and revoked tokens for any of the non-public base roles
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
     * one non-public user.
     */
    public static function provideTokenAuthTestData()
    {
        return [
            ['pub', 'empty_token', 'api'],
            ['pub', 'malformed_token', 'api'],
            ['usr', 'invalid_token', 'api'],
            ['usr', 'expired_token', 'api'],
            ['usr', 'revoked_token', 'api'],
            ['usr', 'valid_token', 'api'],
            ['usr', 'invalid_token', 'jwt'],
            ['usr', 'expired_token', 'jwt'],
            ['usr', 'valid_token', 'jwt']
        ];
    }

    /**
     * Same as BaseTest::makeHttpRequest() but with an API token for the
     * given user role added to the request header.
     */
    public static function makeHttpRequestWithValidAPIToken(
        XdmodTestHelper $testHelper,
        array $input,
        $role
    ) {
        $token = self::getToken('valid_token', 'api', $role);
        $testHelper->addheader('Authorization', "Bearer $token");
        return BaseTest::makeHttpRequest($testHelper, $input);
    }

    /**
     * Retrieve the token for testing based on the format.
     *
     * @param string $role the user role to use when authenticating.
     * @param string $tokenType the type of token being provided
     *                          (@see provideTokenAuthTestData()).
     * @param string $format The authentication token type.
     *                            This can be either 'api' for API token
     *                            or 'jwt' for JSON Web Token.
     */
    private function getTestToken($type, $format, $role)
    {
        if ('api' === $type || 'jwt' === $type) {
            throw new Exception(
                'Unknown token format: "' . $format . '".'
            );
        }

        if ('expired_token' === $type && 'api' === $type) {
            // Expire the token (it will be unexpired at the end of this
            // test).
            self::expireAPIToken($role);
            $token = self::getToken('valid_token', $format, $role);
        } else {
            $token = self::getToken($type, $format, $role);
        }

        if ('revoked_token' === $type) {
            // The key in the test output artifact should now be switched
            // since revoked and invalid tokens are expected to produce the
            // same response.
            $type = 'invalid_token';
        }

        return [$token, $type];
    }

    /**
     * Make an HTTP request to an endpoint involving API token authentication
     * and validate the response.
     *
     * @param string $role the user role to use when authenticating.
     * @param string $tokenType the type of token being provided
     *                          (@see provideTokenAuthTestData()).
     * @param string $tokenFormat The authentication token type.
     *                            This can be either 'api' for API token
     *                            or 'jwt' for JSON Web Token.
     * @param array $input @see BaseTest::requestAndValidateJson(). Must have
     *                     additional required keys 'endpoint_type', whose value
     *                     is either 'controller' or 'rest', and
     *                     'authentication_type', whose value is either
     *                     'token_optional' or 'token_required'.
     * @param array $output @see BaseTest::requestAndValidateJson(). If
     *                      $tokenType is not 'valid_token', this will be
     *                      ignored and instead the relevant
     *                      authentication/authorization error testing will be
     *                      performed.
     * @return mixed the decoded JSON response body of the request.
     * @throws Exception if the role is unrecognized, if there is an error
     *                   making the HTTP request or validating the response, or
     *                   if there is an invalid value for $tokenType,
     *                   $input['endpoint_type'], or
     *                   $input['authentication_type'].
     */
    public function runTokenAuthTest(
        $role,
        $tokenType,
        $tokenFormat,
        array $input,
        array $output
    ) {
        // Make sure the input array has the additional required keys.
        parent::assertRequiredKeys(
            ['endpoint_type', 'authentication_type'],
            $input,
            '$input'
        );
        list($token, $tokenType) = self::getTestToken($tokenType, $tokenFormat, $role);
        if ('valid_token' !== $tokenType) {
            // If the endpoint can authenticate via a method other than API
            // token (i.e., its 'authentication_type' is 'token_optional'), the
            // response on authentication failure will be different depending
            // on whether the endpoint is defined in 'html/controllers' (in
            // which case it will be 'Session Expired') or 'classes/Rest' (in
            // which case it will be 'authentication error').
            if ('token_optional' === $input['authentication_type']) {
                if ('controller' === $input['endpoint_type']) {
                    $output = [
                        'status_code' => 401,
                        'body_validator' => $this->validateErrorResponseBody(
                            'Session Expired',
                            2
                        )
                    ];
                } elseif ('rest' === $input['endpoint_type']) {
                    $output = parent::validateAuthorizationErrorResponse(401);
                } else {
                    throw new Exception(
                        "Unknown value for endpoint_type:"
                        . " '$input[endpoint_type]'."
                    );
                }
            // If the endpoint requires a token for authentication, there is a
            // separate key in the output test artifact for each token type.
            } elseif ('token_required' === $input['authentication_type']) {
                $messages = [
                    'empty_token' => Tokens::MISSING_TOKEN_MESSAGE,
                    'malformed_token' => Tokens::INVALID_TOKEN_MESSAGE,
                    'invalid_token' => Tokens::INVALID_TOKEN_MESSAGE,
                    'expired_token' => Tokens::EXPIRED_TOKEN_MESSAGE,
                    'revoked_token' => Tokens::INVALID_TOKEN_MESSAGE
                ];
                $output = [
                    'status_code' => 401,
                    'body_validator' => $this->validateErrorResponseBody(
                        $messages[$tokenType],
                        0
                    )
                ];
            } else {
                throw new Exception(
                    'Unknown value for authentication_type:'
                    . " '$input[authentication_type]'."
                );
            }
        }
        // Set the expected header for authentication errors.
        if (
            'token_required' === $input['authentication_type']
            && 'valid_token' !== $tokenType
        ) {
            $output['headers'] = [
                'WWW-Authenticate' => 'Bearer'
            ];
        }

        // Construct a test helper for making the request.
        $helper = new XdmodTestHelper();

        // Add the token to the query parameters.
        parent::assertRequiredKeys(['params'], $input, '$input');
        if (is_null($input['params'])) {
            $input['params'] = [];
        }
        $input['params']['Bearer'] = $token;

        // Make the request and validate the response.
        $actualBody = parent::requestAndValidateJson(
            $helper,
            $input,
            $output
        );

        // If the token is expired, unexpire it.
        if ('expired_token' === $tokenType && 'api' === $tokenFormat) {
            self::unexpireAPIToken($role);
        }

        return $actualBody;
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
     * Return the generated token for the given role and token type.
     *
     * @param string $type either 'valid_token', 'invalid_token',
     *                          'revoked_token', 'malformed_token',
     *                          or 'expired_token'
     * @param string $role one of the non-public roles from
     *                     @see BaseTest::getBaseRoles().
     * @return string the token.
     */
    private static function getToken($type, $format, $role)
    {
        // If the valid, invalid, and revoked tokens have not already been
        // generated for the role, generate them.
        if (is_null(self::$tokens)) {
            self::$tokens = [];
        }
        if (!isset(self::$tokens[$role])) {
            self::$tokens[$role] = [];
        }
        if (!isset(self::$tokens[$role][$format])) {
            self::$tokens[$role][$format] = [];
            if ('api' === $format) {
                // Construct a test helper for making HTTP requests to create
                // and revoke tokens.
                $helper = new XdmodTestHelper();

                // Log the user in so tokens can be created for the role.
                $helper->authenticate($role);

                // User tokens cannot be obtained after they have been created,
                // so if the user already has a token, we don't know what it is
                // and need to revoke it before creating a new one.
                $helper->delete(self::API_TOKEN_CRD_ENDPOINT);

                // Create a new token.
                $token = self::createAPIToken($helper);

                // Store the role's user ID so it can be used to create an
                // invalid token and later used for expiring and unexpiring
                // tokens.
                self::$userIds[$role][$format] = substr(
                    $token,
                    0,
                    strpos($token, '.')
                );

                if ('invalid_token' === $type) {
                    // Create and store an invalid token.
                    $token = (
                        self::$userIds[$role][$format] . '.asdf'
                    );
                } elseif ('malformed_token' === $type) {
                    $token = (
                        self::$userIds[$role][$format] . 'asdf'
                    );
                } elseif ('empty_token' === $type) {
                    $token = (
                        self::$userIds[$role][$format] . ''
                    );
                }
                self::$tokens[$role][$format][$type] = $token;

                // Revoke the created token and store it.
                $helper->delete(self::API_TOKEN_CRD_ENDPOINT);
                self::$tokens[$role][$format]['revoked_token'] = $token;

                // Create a new token and store it.
                self::$tokens[$role][$format]['valid_token'] = self::createAPIToken(
                    $helper
                );
            } elseif ('jwt' === $format) {
                self::$tokens[$role][$format][$type] = self::createJSONWebToken();
            }
        }
        return self::$tokens[$role][$format][$type];
    }

    private static function createJSONWebToken()
    {
        $token = JsonWebToken::encode('testuser');
        return $token;
    }

    /**
     * Create an API token.
     *
     * @param XdmodTestHelper $helper used to make HTTP requests.
     * @return string the token.
     * @throws Exception if there is an error making HTTP requests.
     */
    private static function createAPIToken($helper)
    {
        $response = $helper->post(
            self::API_TOKEN_CRD_ENDPOINT,
            null,
            null
        );
        return $response[0]['data']['token'];
    }

    /**
     * Expire the given role's token.
     *
     * @param string $role
     * @throws Exception if there is an error establishing the database
     *                   connection or executing the SQL statement.
     */
    private static function expireAPIToken($role)
    {
        self::updateAPITokenExpirationDate(
            self::$userIds[$role],
            'SUBDATE(NOW(), 1)'
        );
    }

    /**
     * Unexpire the given role's token.
     *
     * @param string $role
     * @throws Exception if there is an error establishing the database
     *                   connection or executing the SQL statement.
     */
    private static function unexpireAPIToken($role)
    {
        self::updateAPITokenExpirationDate(
            self::$userIds[$role],
            'DATE_ADD(NOW(), INTERVAL 1 DAY)'
        );
    }

    /**
     * Set the new expiration date of the given role's token. Intended to be
     * called only by expireAPIToken() and unexpireAPIToken().
     *
     * @param string $userId the ID of the role.
     * @param string $newDate the new date's SQL value.
     * @throws Exception if there is an error establishing the database
     *                   connection or executing the SQL statement.
     */
    private static function updateAPITokenExpirationDate($userId, $newDate)
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

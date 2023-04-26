<?php

namespace TestHarness;

use CCR\DB;
use Models\Services\Tokens;

/**
 * A helper for making HTTP requests to endpoints that use API tokens for
 * authentication and validating the responses.
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
    private $expectedErrorOutputs = array(
        'empty_token' => array(),
        'malformed_token' => array(),
        'invalid_token' => array(),
        'expired_token' => array(),
        'deleted_token' => array()
    );

    /**
     * Construct a helper, given information about the REST endpoint to which it
     * will be making HTTP requests and information about how to validate the
     * responses.
     *
     * @param \PHPUnit_Framework_TestCase $testInstance the test instance used
     *                                                  to make assertions
     *                                                  about responses.
     * @param \TestHarness\XdmodTestHelper $testHelper the test helper
     *                                                 used to make the HTTP
     *                                                 requests.
     * @param string $role the role to use when authenticating.
     * @param string $path the path to the endpoint.
     * @param string $verb the method of the requests, i.e.,
     *                     'get', 'post', 'delete', or 'patch'.
     * @param array|object|null $params the query parameters to use when making
     *                                  HTTP requests.
     * @param array|object|null $data the body data to use when making HTTP
     *                                requests.
     * @param string $endpointType see $authenticationType below.
     * @param string $authenticationType if 'token_optional', HTTP responses
     *                                   involving failures in authentication
     *                                   will be expected to have status code
     *                                   401 and validate against the
     *                                   'session_expired' artifact file if
     *                                   $endpointType is 'controller' and
     *                                   against the 'authentication_error'
     *                                   artifact file if $endpointType is
     *                                   'rest'. If $authenticationType is
     *                                   instead 'token_required', the artifact
     *                                   file used for validation depends on
     *                                   the type of failure: 'empty_token',
     *                                   'malformed_token', 'expired_token', or
     *                                   'deleted_token', and the expected
     *                                   status code for each of these is 400,
     *                                   except 'invalid_token', which is 403.
     */
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
            foreach (array_keys($this->expectedErrorOutputs) as $type) {
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

    /**
     * Make HTTP requests to this helper's endpoint, testing a variety of
     * expected token authentication error conditions as well as a successful
     * authentication (for non-public users); during the successful
     * authentication, the provided callback is run. If this helper is set to
     * authenticate as a public user, the error conditions tested are an empty
     * token and a malformed token. If the helper is instead set to
     * authenticate as a non-public user, the error conditions tested are an
     * invalid token, an expired token, and a deleted token. If a token already
     * exists for the given user, it is first deleted, and a new one is
     * generated that is also deleted at the end of this method call. The user
     * is also logged out at the end of this method call.
     *
     * @param callable $callback a function that will be run while the user is
     *                           authenticated with a valid token.
     * @return null
     * @throws \Exception if this helper's user role is unrecognized, if there
     *                    is an error making any of the requests, or if there
     *                    is an error validating any of the responses.
     */
    public function runEndpointTests($callback)
    {
        if ('pub' === $this->role) {
            self::runExpectedErrorTest('', 'empty_token');
            self::runExpectedErrorTest('asdf', 'malformed_token');
        } else {
            $this->testHelper->authenticate($this->role);
            $this->testHelper->delete(self::$ENDPOINT);
            $response = $this->testHelper->post(self::$ENDPOINT, null, null);
            $token = $response[0]['data']['token'];
            $userId = substr($token, 0, strpos($token, Tokens::DELIMITER));
            $this->testHelper->logout();
            self::runExpectedErrorTest(
                $userId . Tokens::DELIMITER . 'asdf',
                'invalid_token'
            );
            $callback($token);
            self::expireToken($userId);
            self::runExpectedErrorTest($token, 'expired_token');
            $this->testHelper->authenticate($this->role);
            $this->testHelper->delete(self::$ENDPOINT);
            $this->testHelper->logout();
            self::runExpectedErrorTest($token, 'deleted_token');
        }
    }

    /**
     * Make an HTTP request to this helper's endpoint using the provided token
     * for authentication (which is added to both the 'Authorization' header
     * and the query parameters) and given the provided information about
     * validating the response. After the request is complete, the token is
     * removed from the header and query parameters.
     *
     * @param string $token the token to use when making the request.
     * @param string|null $outputFileName the name of the file used to validate
     *                                    the response. If null, this is loaded
     *                                    from the default value for the
     *                                    'empty_token' error case.
     * @param int|null $statusCode the expected status code of the response. If
     *                             null, this is loaded from the default value
     *                             for the 'empty_token' error case.
     * @param string $outputTestGroup the directory (relative to the test
     *                                artifacts directory) containing the
     *                                'output' directory that contains the file
     *                                used to validate the response.
     * @param string $validationType the method by which to validate the
     *                               response body against the file, i.e.,
     *                               'schema', which will validate it against a
     *                               JSON Schema, or 'exact', which will do an
     *                               exact comparison to the JSON object
     *                               in the file.
     * @return mixed the decoded JSON response body.
     * @throws \Exception if there is an error making the request, loading
     *                    the JSON output file, or running the validation of
     *                    it.
     */
    public function runEndpointTest(
        $token,
        $outputFileName = null,
        $statusCode = null,
        $outputTestGroup = 'integration/rest/user/api_token',
        $validationType = 'exact'
    ) {
        $defaultOutput = $this->expectedErrorOutputs['empty_token'];
        if (is_null($outputFileName)) {
            $outputFileName = $defaultOutput['file_name'];
        }
        if (is_null($statusCode)) {
            $statusCode = $defaultOutput['status_code'];
        }
        $authHeader = $this->testHelper->getheader('Authorization');
        $this->testHelper->addheader(
            'Authorization',
            Tokens::HEADER_KEY . ' ' . $token
        );
        if (is_null($this->params)) {
            $this->params = array();
        }
        $this->params[Tokens::HEADER_KEY] = $token;
        $responseBody = $this->testInstance->makeRequest(
            $this->testHelper,
            $this->path,
            $this->verb,
            $this->params,
            $this->data,
            $statusCode,
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

    /**
     * Set the expected HTTP status code and the name of the file that should
     * be used to validate the response to an HTTP request involving an
     * expected error type (e.g., an empty token).
     *
     * @param string $type a key in the array of expected error outputs, e.g.,
     *                     'empty_token'.
     * @param int $statusCode the HTTP status code that should be expected for
     *                        the given type of expected error.
     * @param string|null $fileName the name of the file that will be used for
     *                              validating the response to an HTTP request
     *                              involving the expected error. If null, the
     *                              $type will be used as the file name.
     * @return null
     */
    private function setExpectedErrorOutput(
        $type,
        $statusCode,
        $fileName = null
    ) {
        if (is_null($fileName)) {
            $fileName = $type;
        }
        $this->expectedErrorOutputs[$type] = array(
            'status_code' => $statusCode,
            'file_name' => $fileName
        );
    }

    /**
     * Make an HTTP request to this helper's endpoint using the provided token
     * for authentication, and test the response for an error of the provided
     * type.
     *
     * @param string $token the token to use when making requests.
     * @param string $type a key in the array of expected error outputs, e.g.,
     *                     'empty_token'.
     * @return null
     * @throws \Exception if this helper's user role is unrecognized, if there
     *                    is an error making any requests, or if there
     *                    is an error validating any responses.
     */
    private function runExpectedErrorTest($token, $type)
    {
        $this->runEndpointTest(
            $token,
            $this->expectedErrorOutputs[$type]['file_name'],
            $this->expectedErrorOutputs[$type]['status_code']
        );
    }
}

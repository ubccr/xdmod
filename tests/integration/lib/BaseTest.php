<?php

namespace IntegrationTests;

use CCR\Json;
use Exception;
use SebastianBergmann\Comparator\ComparisonFailure;
use Swaggest\JsonSchema\Schema;
use IntegrationTests\TestHarness\TestFiles;
use IntegrationTests\TestHarness\Utilities;
use IntegrationTests\TestHarness\XdmodTestHelper;
use InvalidArgumentException;

/**
 * This class serves as a base for test classes.
 *
 * It provides methods for making HTTP requests to REST endpoints and making
 * assertions about the JSON responses.
 *
 * Among other things, it has a method, provideRestEndpointTests(), that
 * provides test parameters for some common tests of REST endpoints like failed
 * authentication, failed authorization, and missing and invalid query
 * parameters. These test parameters can be fed into the methods
 * requestAndValidateJson() or authenticateRequestAndValidateJson() to make
 * HTTP requests and use PHPUnit to make assertions about the responses.
 *
 * For testing endpoints that have API token authentication,
 * @see \IntegrationTests\TokenAuthTest.
 *
 * Below is an example of a test that could be defined by a class that extends
 * this one, and a data provider for that test. This test would make GET
 * requests to the endpoint 'rest/example/get_data' and test to make sure
 * authentication failures give the correct response, failing to authorize as a
 * center director gives the correct response, requests in which the 'limit' or
 * 'start_date' parameters are missing give the correct response, and requests
 * give the correct response for which the value of 'limit' is not a valid
 * integer, the value of 'scale' is not a valid floating point number, the
 * value of 'debug' is not a valid Boolean value, the values of 'realm' or
 * 'dimension' are not valid strings, the value of 'ts' is not a valid Unix
 * timestamp, or the value of 'start_date' is not a valid ISO 8601 date. It
 * also tests that the response body of an error response with a given message
 * matches an expected JSON array.
 *
 *   public function testGetData($id, $role, $input, $output)
 *   {
 *      parent::authenticateRequestAndValidateJson(
 *          self::$testHelper,
 *          $role,
 *          $input,
 *          $output
 *      );
 *  }
 *
 *  public function provideGetData()
 *  {
 *      $validInput = [
 *          'path' => 'rest/example/get_data',
 *          'method' => 'get',
 *          'params' => [
 *              'limit' => 0,
 *              'start_date' => '2017-01-01'
 *          ],
 *          'data' => null
 *      ];
 *      // Run some standard endpoint tests.
 *      $tests = parent::provideRestEndpointTests(
 *          $validInput,
 *          [
 *              'authentication' => true,
 *              'authorization' => 'cd',
 *              'int_params' => ['limit'],
 *              'float_params' => ['scale'],
 *              'string_params' => ['realm', 'dimension'],
 *              'bool_params' => ['debug'],
 *              'unix_ts_params' => ['ts'],
 *              'date_params' => ['start_date'],
 *              'error_body_validator' => function ($message) {
 *                  return function ($body, $assertMessage) use ($message) {
 *                      parent::assertEquals(
 *                          [
 *                              'success' => false,
 *                              'message' => $message
 *                          ],
 *                          $body,
 *                          $assertMessage
 *                      );
 *                  }
 *              }
 *          ]
 *      );
 *  }
 */
abstract class BaseTest extends \PHPUnit\Framework\TestCase
{
    const DATE_REGEX = '/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/';

    protected static $XDMOD_REALMS;
    protected static $ROLES;
    protected static $testFiles;

    /**
     * for json test input artifacts that describe http requests to be made to
     * endpoints and json test output artifacts that describe the expected
     * responses, these are the required keys.
     */
    protected static $REQUIRED_ENDPOINT_TEST_KEYS = [
        'input' => ['path', 'method', 'params', 'data'],
        'output' => ['status_code', 'body_validator']
    ];

    public static function setupBeforeClass(): void
    {
        self::$XDMOD_REALMS = Utilities::getRealmsToTest();
    }

    protected static function getRealms()
    {
        return Utilities::getRealmsToTest();
    }

    public static function getTestFiles()
    {
        if (!isset(self::$testFiles)) {
            self::$testFiles = new TestFiles(__DIR__ . '/../../');
        }
        return self::$testFiles;
    }

    /**
     * Load the base roles to be used by @see provideBaseRoles().
     */
    protected static function getBaseRoles()
    {
        if (!isset(self::$ROLES)) {
            $testConfig = json_decode(
                file_get_contents(__DIR__ . '/../../ci/testing.json'),
                true
            );
            self::$ROLES = array_merge(
                ['pub'],
                array_keys($testConfig['role'])
            );
        }
        return self::$ROLES;
    }

    /**
     * A dataProvider for tests that use each of the base roles.
     */
    public function provideBaseRoles()
    {
        return array_map(
            function ($role) {
                return [$role];
            },
            self::getBaseRoles()
        );
    }

    /**
     * Make an HTTP request and return data about the response.
     *
     * @param XdmodTestHelper $testHelper used to make the request.
     * @param array $input describes the HTTP request. Required keys:
     *                     - 'path': the URL path to the HTTP endpoint (e.g.,
     *                               '/rest/...').
     *                     - 'method': the HTTP method, either 'get', 'post',
     *                                 'put', 'delete', or 'patch'.
     *                     - 'params': associative array of query parameters.
     *                     - 'data': associative array of request body data.
     * @return array element 0 is the response body, element 1 is the return of
     *               curl_getinfo(), and element 2 is the array of response
     *               headers.
     * @throws Exception if the input array does not contain all of the
     *                   required keys or if there is an error making the
     *                   request.
     */
    public static function makeHttpRequest(
        XdmodTestHelper $testHelper,
        array $input
    ) {
        $method = $input['method'];
        switch ($method) {
            case 'get':
                $response = $testHelper->$method(
                    $input['path'],
                    $input['params']
                );
                break;
            case 'post':
            case 'put':
            case 'delete':
            case 'patch':
                $response = $testHelper->$method(
                    $input['path'],
                    $input['params'],
                    $input['data']
                );
                break;
        }
        return $response;
    }

    /**
     * Make an HTTP request and make assertions about the JSON response's
     * status code, content type, body, and possibly headers.
     *
     * @param XdmodTestHelper $testHelper the test helper making the HTTP
     *                                    request.
     * @param array $input associative array describing the HTTP request. Must have the required
     *                     keys from @see self::$REQUIRED_ENDPOINT_TEST_KEYS['input'].
     * @param array $output associative array describing the expected HTTP response status code
     *                      and body. Must have the required keys from
     *                      @see self::$REQUIRED_ENDPOINT_TEST_KEYS['output']. Can also have an
     *                      optional 'headers' key whose value is an associative array containing a
     *                      set of header keys and values that are expected to be present in the
     *                      response (not an exclusive list; i.e., if there are headers that appear
     *                      in the response but not in the list, this will NOT cause the assertion
     *                      to fail).
     * @return mixed the actual decoded JSON response body.
     * @throws Exception if the input array does not contain all of the required keys or if there
     *                   is an error making the request, loading the JSON output file, or running
     *                   the validation of it.
     */
    protected function requestAndValidateJson(
        XdmodTestHelper $testHelper,
        array $input,
        array $output
    ) {
        // Make sure the input and output arrays have all the required keys.
        self::assertRequiredKeys(
            self::$REQUIRED_ENDPOINT_TEST_KEYS['input'],
            $input,
            '$input'
        );
        self::assertRequiredKeys(
            self::$REQUIRED_ENDPOINT_TEST_KEYS['output'],
            $output,
            '$output'
        );

        // Initialize response variables.
        $response = null;
        $actualStatusCode = null;
        $actualContentType = null;
        $actualBody = [];
        $actualHeaders = [];

        // Make HTTP request.
        $response = self::makeHttpRequest($testHelper, $input);

        // Set response variables.
        if (isset($response)) {
            $actualStatusCode = $response[1]['http_code'];
            $actualContentType = $response[1]['content_type'];
            $actualBody = $response[0];
            $actualHeaders = $response[2];
        }

        // Prepare test failure message.
        $message = (
            "\nPATH: $input[path]\nMETHOD: $input[method]\nHEADERS: "
            . json_encode($testHelper->getheaders(), JSON_PRETTY_PRINT)
            . "\nPARAMS: " . json_encode($input['params'], JSON_PRETTY_PRINT)
            . "\nDATA: " . json_encode($input['data'], JSON_PRETTY_PRINT)
            . "\nEXPECTED STATUS CODE: $output[status_code]"
            . "\nACTUAL STATUS CODE: $actualStatusCode"
            . "\nEXPECTED CONTENT TYPE: application/json"
            . "\nACTUAL CONTENT TYPE: $actualContentType"
            . "\nACTUAL BODY: "
            . self::getJsonStringForExceptionMessage($actualBody)
            . "\n"
        );

        // Make assertions
        parent::assertSame($output['status_code'], $actualStatusCode, $message);
        parent::assertSame('application/json', $actualContentType, $message);
        $output['body_validator']($actualBody, $message);
        if (isset($output['headers'])) {
            foreach ($output['headers'] as $key => $value) {
                parent::assertArrayHasKey($key, $actualHeaders, $message);
                parent::assertSame(
                    $value,
                    trim($actualHeaders[$key]),
                    $message
                );
            }
        }
        return $actualBody;
    }

    /**
     * Same as requestAndValidateJson() but authenticating as the given role
     * first (except for 'pub') and logging out afterwards.
     */
    protected function authenticateRequestAndValidateJson(
        XdmodTestHelper $testHelper,
        $role,
        array $input,
        array $output
    ) {
        if ('pub' !== $role) {
            $testHelper->authenticate($role);
        }
        $actualBody = $this->requestAndValidateJson(
            $testHelper,
            $input,
            $output
        );
        if ('pub' !== $role) {
            $testHelper->logout();
        }
        return $actualBody;
    }

    /**
     * Assert that a given JSON object validates against a provided file. If
     * the object in the file contains a '$schema' property, treat it as a JSON
     * schema; otherwise, treat it as a literal JSON object to be compared
     * against.
     *
     * @param array|object $json the JSON object to validate.
     * @param string $testGroup the directory (relative to the test artifacts directory) containing
     *                          the file against which to validate.
     * @param string $fileName the name of the file against which to validate.
     * @param string $fileType the type of file, i.e., the subdirectory in which the file is
     *                         located against which to validate, defaults to empty string.
     * @return object the provided JSON object after having been JSON encoded and decoded.
     * @throws Exception if there is an error loading the file or running the validation.
     */
    protected function validateJsonAgainstFile(
        $json,
        $testGroup,
        $fileName,
        $fileType = ''
    ) {
        $expectedFilePath = self::getTestFiles()->getFile(
            $testGroup,
            $fileName,
            $fileType
        );
        $expectedJson = JSON::loadFile($expectedFilePath);
        return $this->validateJson($expectedJson, $json);
    }

    /**
     * Throw an exception if any of the given keys are missing from the given
     * array.
     *
     * @param array $requiredKeys the list of required keys.
     * @param array $array the array.
     * @param array $arrayName the name of the array for printing in the
     *                         exception message.
     * @return null
     * @throws Exception if any of the required keys are missing from the
     *                   array.
     */
    protected static function assertRequiredKeys(
        $requiredKeys,
        $array,
        $arrayName
    ) {
        $missingKeys = [];
        foreach ($requiredKeys as $requiredKey) {
            if (!array_key_exists($requiredKey, $array)) {
                $missingKeys[] = $requiredKey;
            }
        }
        if (!empty($missingKeys)) {
            throw new Exception(
                "$arrayName is missing required keys: '"
                . implode("', '", $missingKeys)
                . "'.\n"
                . self::getJsonStringForExceptionMessage($array)
            );
        }
    }

    /**
     * Provide parameters for tests involving REST endpoints.
     *
     * @param array $validInput an input array for the requestAndValidateJson() method in which the
     *                          'params' or 'data' key is mapped to an associative array in which
     *                          the keys are all of the required endpoint parameters, and the values
     *                          are valid values for those parameters. If the 'method' value is
     *                          'post', 'put', or 'patch', the parameters will be pulled from the
     *                          'data' value; otherwise, they will be pulled from the 'params' value.
     * @param array $options an associative array that configures how this method should run.
     *                       The keys are all optional:
     *                       - 'authentication' — if the value is true, the return will include a
     *                         test for failed authentication in which the endpoint is requested by
     *                         the 'pub' role. Note that this option and 'token_auth' are
     *                         incompatible; 'token_auth' will already include authentication
     *                         tests.
     *                       - 'authorization' — if the value is a string role, the return will
     *                         include tests of failed authorization of the endpoint by all of the
     *                         non-pub base roles (from getBaseRoles()) except the one specified;
     *                         e.g., if the value is 'mgr', the return will include tests to make
     *                         sure the endpoint restricts access to just the admin user. Note that
     *                         this option and 'token_auth' are incompatible; 'token_auth' assumes
     *                         that the 'usr' role has authorization.
     *                       - 'run_as' — if the value is a string role, any tests in the return
     *                         involving an authenticated user will use that role, e.g., 'cd'. This
     *                         is overriden by setting the 'authorization' key, in which case those
     *                         tests will instead run as that authorized role. If both
     *                         'authorization' and 'run_as' are not provided, the tests will be run
     *                         as 'usr'.
     *                       - 'token_auth' — if the value is true, the tests in the return will
     *                         include 'valid_token' as their token type. If $this is a
     *                         TokenAuthTest, the return will also include tests that can be fed
     *                         into TokenAuthTest::runTokenAuthTest(); namely, each of the error
     *                         tests from TokenAuthTest::provideTokenAuthTestData().
     *                       - 'additional_params' — array of parameters that will be merged into
     *                         either 'params' or 'data' (based on the value of
     *                         $validInput['method']) of $validInput; e.g., if there are parameters
     *                         that are not required in all cases for the endpoint (i.e., which
     *                         shouldn't be tested here for missing mandatory parameters) but which
     *                         need to be present for other tests here to succeed.
     *                       - 'int_params' — array of parameters that will each be tested for
     *                         invalid integer values.
     *                       - 'float_params' — array of parameters that will each be tested for
     *                         invalid floating point values.
     *                       - 'string_params' — array of parameters that will each be tested for
     *                         invalid string values.
     *                       - 'bool_params' — array of parameters that will each be tested for
     *                         invalid Boolean values.
     *                       - 'unix_ts_params' — array of parameters that will each be tested for
     *                         invalid Unix timestamp values.
     *                       - 'date_params' — array of parameters that will each be tested for
     *                         invalid ISO 8601 date values.
     *                       - 'error_body_validator' — callable function that is used for
     *                         validating the response body of a request that has an error. Takes
     *                         a message as an argument and returns a function that has body and
     *                         assert message arguments and makes assertions about the body.
     *                         @see validateErrorResponseBody() for an example of the default
     *                         error body validator.
     * @return array of arrays of test data, each of which contains a string ID of the test, a
     *                         string role as whom the request will be made, the value
     *                         'valid_token' if $tokenAuth is true (otherwise this value is not
     *                         included in the array), and input and output arrays suitable for
     *                         requestAndValidateJson().
     * @throws InvalidArgumentException if a key in $options is unrecognized.
     */
    protected function provideRestEndpointTests(
        array $validInput,
        array $options
    ) {
        // Validate options
        $validOptions = [
             'authentication',
             'authorization',
             'run_as',
             'token_auth',
             'additional_params',
             'int_params',
             'float_params',
             'string_params',
             'bool_params',
             'unix_ts_params',
             'date_params',
             'error_body_validator'
        ];
        foreach (array_keys($options) as $key) {
            if (!in_array($key, $validOptions)) {
                throw new InvalidArgumentException(
                    "Unrecognized option '$key'."
                );
            }
        }
        // Determine the source of parameters.
        $paramSource = 'params';
        if (
            'post' === $validInput['method']
            || 'put' === $validInput['method']
            || 'patch' === $validInput['method']
        ) {
            $paramSource = 'data';
        }
        // Add any additional parameters.
        $validInputWithAdditionalParams = $validInput;
        if (array_key_exists('additional_params', $options)) {
            $validInputWithAdditionalParams = self::mergeParams(
                $validInputWithAdditionalParams,
                $paramSource,
                $options['additional_params']
            );
        }
        // Set up the custom error body validator.
        $errorBodyValidator = null;
        if (array_key_exists('error_body_validator', $options)) {
            $errorBodyValidator = $options['error_body_validator'];
        }
        $tests = [];
        // Provide authentication tests.
        if (
            array_key_exists('authentication', $options)
            && $options['authentication']
        ) {
            $tests[] = [
                'unauthenticated',
                'pub',
                $validInputWithAdditionalParams,
                $this->validateAuthorizationErrorResponse(
                    401,
                    $errorBodyValidator
                )
            ];
        }
        // Provide authorization tests.
        if (array_key_exists('authorization', $options)) {
            foreach (self::getBaseRoles() as $role) {
                if ('pub' !== $role && $options['authorization'] !== $role) {
                    $tests[] = [
                        'unauthorized',
                        $role,
                        $validInputWithAdditionalParams,
                        $this->validateAuthorizationErrorResponse(
                            403,
                            $errorBodyValidator
                        )
                    ];
                }
            }
            $runAs = $options['authorization'];
        }
        // Set the role for running the tests.
        if (!isset($runAs)) {
            if (array_key_exists('run_as', $options)) {
                $runAs = $options['run_as'];
            } else {
                $runAs = 'usr';
            }
        }
        // Determine whether API token authorization is used on this endpoint.
        $tokenAuth = (
            array_key_exists('token_auth', $options) && $options['token_auth']
        );
        // Provide token authentication tests.
        if ($tokenAuth && $this instanceof TokenAuthTest) {
            foreach ($this->provideTokenAuthTestData() as $testData) {
                list($role, $tokenType) = $testData;
                if ('valid_token' !== $tokenType) {
                    $tests[] = [
                        $tokenType,
                        $role,
                        $tokenType,
                        $validInputWithAdditionalParams,
                        []
                    ];
                }
            }
        }
        // Provide tests of missing required parameters.
        foreach (array_keys($validInput[$paramSource]) as $param) {
            $input = $validInput;
            unset($input[$paramSource][$param]);
            $testData = ["missing_$param", $runAs];
            if ($tokenAuth) {
                $testData[] = 'valid_token';
            }
            array_push(
                $testData,
                $input,
                $this->validateMissingRequiredParameterResponse(
                    $param,
                    $errorBodyValidator
                )
            );
            $tests[] = $testData;
        }
        // Provide tests of invalid parameters.
        $types = [
            'int_params' => 'integer',
            'float_params' => 'float',
            'string_params' => 'string',
            'bool_params' => 'boolean',
            'unix_ts_params' => 'Unix timestamp',
            'date_params' => 'ISO 8601 Date'
        ];
        $values = [
            'string' => 'foo',
            'array' => ['foo' => 'bar'],
            'invalid_date' => '2024-01-99'
        ];
        foreach ($types as $key => $type) {
            if (array_key_exists($key, $options)) {
                foreach ($options[$key] as $param) {
                    $input = $validInputWithAdditionalParams;
                    foreach ($values as $id => $value) {
                        // We can skip tests:
                        //   - Strings can be strings, so skip that test.
                        //   - Invalid dates should only be tested on date params.
                        if (('string' === $id && 'string_params' === $key) ||
                            ('invalid_date' === $id && 'date_params' !== $key) ) {
                            continue;
                        }

                        $input[$paramSource][$param] = $value;
                        $testData = ["{$param}_$id", $runAs];
                        if ($tokenAuth) {
                            $testData[] = 'valid_token';
                        }
                        array_push(
                            $testData,
                            $input,
                            $this->validateInvalidParameterResponse(
                                $param,
                                $type,
                                $errorBodyValidator
                            )
                        );
                        $tests[] = $testData;
                    }
                }
            }
        }
        return $tests;
    }

    /**
     * Return an output array for use in requestAndValidateJson() that
     * validates 400 Bad Request responses in which a required parameter with
     * the given name was not provided in the request.
     *
     * @param string $name
     * @param callable|null $bodyValidator if provided, overrides the default
     *                                     body validator.
     * @return array
     */
    protected function validateMissingRequiredParameterResponse(
        $name,
        $bodyValidator = null
    ) {
        return $this->validateBadRequestResponse(
            "$name is a required parameter.",
            $bodyValidator
        );
    }

    /**
     * Return an output array for use in requestAndValidateJson() that
     * validates 400 Bad Request responses in which a parameter with the given
     * name was not the given type.
     *
     * @param string $name
     * @param string $type
     * @param callable|null $bodyValidator if provided, overrides the default
     *                                     body validator.
     * @return array
     */
    protected function validateInvalidParameterResponse(
        $name,
        $type,
        $bodyValidator = null
    ) {
        return $this->validateBadRequestResponse(
            "Invalid value for $name. Must be a(n) $type.",
            $bodyValidator
        );
    }

    /**
     * Return an output array for use in requestAndValidateJson() that
     * validates 400 Bad Request responses expected to have the given message
     * in their JSON.
     *
     * @param string $message
     * @param callable|null $bodyValidator if provided, overrides the default
     *                                     body validator.
     * @return array
     */
    protected function validateBadRequestResponse(
        $message,
        $bodyValidator = null
    ) {
        return [
            'status_code' => 400,
            'body_validator' => $this->validateErrorResponseBody(
                $message,
                0,
                $bodyValidator
            )
        ];
    }

    /**
     * Return an output array for use in requestAndValidateJson() that
     * validates 404 Not Found responses expected to have the given message
     * in their JSON.
     *
     * @param string $message
     * @param callable|null $bodyValidator if provided, overrides the default
     *                                     body validator.
     * @return array
     */
    protected function validateNotFoundResponse(
        $message,
        $bodyValidator = null
    ) {
        return [
            'status_code' => 404,
            'body_validator' => $this->validateErrorResponseBody(
                $message,
                0,
                $bodyValidator
            )
        ];
    }

    /**
     * Return an output array for use in requestAndValidateJson() that
     * validates authorization error responses with the given HTTP status code.
     *
     * @param int $statusCode
     * @param callable|null $bodyValidator if provided, overrides the default
     *                                     body validator.
     * @return array
     */
    protected function validateAuthorizationErrorResponse(
        $statusCode,
        $bodyValidator = null
    ) {
        return [
            'status_code' => $statusCode,
            'body_validator' => $this->validateErrorResponseBody(
                (
                    'An error was encountered while attempting to process the'
                    . ' requested authorization procedure.'
                ),
                0,
                $bodyValidator
            )
        ];
    }

    /**
     * Return a validator for use in requestAndValidateJson() that
     * validates HTTP error responses expected to have the given message
     * and code in their JSON.
     *
     * @param string $message
     * @param int $code
     * @param callable|null $bodyValidator if provided, overrides the default
     *                                     body validator.
     * @return callable
     */
    protected function validateErrorResponseBody(
        $message,
        $code,
        $bodyValidator = null
    ) {
        if (!is_null($bodyValidator)) {
            return $bodyValidator($message, $code);
        }
        return function ($body, $assertMessage) use ($message, $code) {
            parent::assertEquals(
                [
                    'success' => false,
                    'count' => 0,
                    'total' => 0,
                    'totalCount' => 0,
                    'results' => [],
                    'data' => [],
                    'message' => $message,
                    'code' => $code
                ],
                $body,
                $assertMessage
            );
        };
    }

    /**
     * Return an output array for use in requestAndValidateJson() that
     * uses the given validator to validate 200 OK responses in which the
     * 'success' property is true.
     *
     * @param callable|array $validator if callable, a method used to validate the response body.
     *                                  If an array, the expected response body.
     * @return array
     */
    protected function validateSuccessResponse($validator)
    {
        return [
            'status_code' => 200,
            'body_validator' => function (
                $body,
                $assertMessage
            ) use ($validator) {
                if (is_callable($validator)) {
                    parent::assertSame(true, $body['success'], $assertMessage);
                    $validator($body, $assertMessage);
                } else {
                    parent::assertEquals($validator, $body, $assertMessage);
                }
            }
        ];
    }

    /**
     * Validate ISO 8601 dates.
     *
     * @param string $date
     * @return null
     */
    protected function validateDate($date)
    {
        parent::assertMatchesRegularExpression(self::DATE_REGEX, $date);
    }

    /**
     * Given an input associative array, a key of that array whose value is an
     * associative array, and a new associative array of parameters, make a
     * copy of the input array and merge the parameters into the associative
     * array at the given key.
     *
     * @param array $input the input array, e.g., one that could be passed into
     *                     requestAndValidateJson.
     * @param string $key, e.g., 'params' or 'data'.
     * @param array $params the new associative array of parameters, e.g., as could be used as a
     *                      'params' or 'data' property.
     * @return array the $input array with changes made to it.
     */
    protected function mergeParams(array $input, $key, array $params)
    {
        $newInput = $input;
        $newInput[$key] = array_merge($input[$key], $params);
        return $newInput;
    }

    /**
     * Given a JSON associative array, return a string representation of it for
     * printing in exception messages: pretty-print the JSON, and truncate at
     * 1000 characters.
     *
     * @param array $json the JSON associative array.
     * @return string the string representation.
     */
    protected static function getJsonStringForExceptionMessage(array $json)
    {
        return self::truncateStr(
            json_encode($json, JSON_PRETTY_PRINT),
            1000
        );
    }

    /**
     * Assert that an actual JSON object validates against an expected JSON
     * object. If the expected object contains a '$schema' property, treat it
     * as a JSON schema; otherwise, treat it as a literal JSON object to be
     * compared against.
     *
     * @param array|object $expectedJson the expected JSON object.
     * @param array|object $actualJson the actual JSON object.
     * @param string $message prepended to the error message shown when a test assertion fails.
     * @return object the actual JSON object after having been JSON encoded and decoded.
     * @throws Exception if there is an error running the validation.
     */
    private function validateJson($expectedJson, $actualJson, $message = '')
    {
        $expectedStr = json_encode($expectedJson, JSON_PRETTY_PRINT);
        $expectedJson = json_decode($expectedStr, false);
        $actualStr = json_encode($actualJson, JSON_PRETTY_PRINT);
        $actualJson = json_decode($actualStr, false);
        if (property_exists($expectedJson, '$schema')) {
            $schema = Schema::import($expectedJson);
            try {
                $schema->in($actualJson);
            } catch (Exception $e) {
                parent::fail($message . $e->getMessage());
            }
        } else {
            parent::assertSame($expectedStr, $actualStr, $message);
        }
        return $actualJson;
    }

    /**
     * If a string is longer than the provided number of characters, truncate
     * it and add an elipsis.
     *
     * @param string $str the string.
     * @param int $numChars the number of characters at which to truncate.
     * @return string the truncated string with the elipsis added.
     */
    private static function truncateStr($str, $numChars)
    {
        return (
            strlen($str) > $numChars
            ? substr($str, 0, $numChars) . '...'
            : $str
        );
    }
}

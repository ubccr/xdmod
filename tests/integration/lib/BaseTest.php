<?php

namespace IntegrationTests;

use CCR\Json;
use Exception;
use Swaggest\JsonSchema\Schema;
use TestHarness\TestFiles;
use TestHarness\Utilities;
use TestHarness\XdmodTestHelper;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
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

    public static function setUpBeforeClass()
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
     * Make an HTTP request and make assertions about the JSON response's
     * status code, content type, body, and possibly headers.
     *
     * @param XdmodTestHelper $testHelper the test helper making the HTTP
     *                                    request.
     * @param array $input associative array describing the HTTP request. Must
     *                     have the required keys from
     *                     @see self::$REQUIRED_ENDPOINT_TEST_KEYS['input'].
     * @param array $output associative array describing the expected HTTP
     *                      response status code and body. Must have the
     *                      required keys from
     *                      @see self::$REQUIRED_ENDPOINT_TEST_KEYS['output'].
     *                      Can also have an optional 'headers' key whose value
     *                      is an associative array containing a set of header
     *                      keys and values that are expected to be present in
     *                      the response (not an exclusive list; i.e., if there
     *                      are headers that appear in the response but not in
     *                      the list, this will NOT cause the assertion to
     *                      fail).
     * @return mixed the actual decoded JSON response body.
     * @throws Exception if the input array does not contain all of the
     *                   required keys or if there is an error making the
     *                   request, loading the JSON output file, or running the
     *                   validation of it.
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
        $method = $input['method'];
        switch ($method) {
            case 'get':
                $response = $testHelper->$method(
                    $input['path'],
                    $input['params']
                );
                break;
            case 'post':
            case 'delete':
            case 'patch':
                $response = $testHelper->$method(
                    $input['path'],
                    $input['params'],
                    $input['data']
                );
                break;
        }

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
        $this->assertSame($output['status_code'], $actualStatusCode, $message);
        $this->assertSame('application/json', $actualContentType, $message);
        $output['body_validator']($actualBody, $message);
        if (isset($output['headers'])) {
            foreach ($output['headers'] as $key => $value) {
                $this->assertArrayHasKey($key, $actualHeaders, $message);
                $this->assertSame(
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
     * @param string $testGroup the directory (relative to the test artifacts
     *                          directory) containing the file against which
     *                          to validate.
     * @param string $fileName the name of the file against which to validate.
     * @param string $fileType the type of file, i.e., the subdirectory in
     *                         which the file is located against which to
     *                         validate, defaults to empty string.
     * @return object the provided JSON object after having been JSON encoded
     *                and decoded.
     * @throws Exception if there is an error loading the file or running the
     *                   validation.
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
     * Data provider for tests involving REST endpoints.
     *
     * @param array $validInput an input array for the requestAndValidateJson()
     *                          method in which the 'params' or 'data' key is
     *                          mapped to an associative array in which the
     *                          keys are all of the required endpoint
     *                          parameters, and the values are valid values for
     *                          those parameters.
     * @param array $options an associative array that configures how this
     *                       method should run. There is one required key:
     *                       - 'param_source' — either 'params' or 'data',
     *                         specifying whether request parameters are to be
     *                         located in the query parameters ('params') or
     *                         the POST data ('data'). The return will include
     *                         tests of missing required parameters.
     *                       The other keys are optional:
     *                       - 'authentication' — if the value is true, the
     *                         return will include a test for failed
     *                         authentication in which the endpoint is
     *                         requested by the 'pub' role. Note that this
     *                         option and 'token_auth' are incompatible;
     *                         'token_auth' will already include authentication
     *                         tests.
     *                       - 'authorization' — if the value is a string role,
     *                         the return will include tests of failed
     *                         authorization of the endpoint by all of the
     *                         non-pub base roles (from getBaseRoles()) except
     *                         the one specified; e.g., if the value is 'mgr',
     *                         the return will include tests to make sure the
     *                         endpoint restricts access to just the admin
     *                         user. Note that this option and 'token_auth' are
     *                         incompatible; 'token_auth' will already include
     *                         authorization tests.
     *                       - 'run_as' — if the value is a string role, any
     *                         tests in the return involving an an
     *                         authenticated user will use that role, e.g.,
     *                         'usr'. This is overriden by setting the
     *                         'authorization' key, in which case those tests
     *                         will instead run as that authorized role.
     *                       - 'token_auth' — if the value is true, the tests
     *                         in the return will include 'valid_token' as
     *                         their token type. If $this is a TokenAuthTest,
     *                         the return will also include tests that can be
     *                         fed into TokenAuthTest::runTokenAuthTest();
     *                         namely, each of the error tests from
     *                         TokenAuthTest::provideTokenAuthTestData().
     *                       - 'int_params' — array of parameters that
     *                         will each be tested for invalid integer values.
     *                       - 'date_params' — array of parameters that will
     *                         each be tested for invalid ISO 8601 date values.
     * @return array of arrays of test data, each of which contains a string
     *               ID of the test, a string role as whom the request will be
     *               made, the value 'valid_token' if $tokenAuth is true
     *               (otherwise this value is not included in the array), and
     *               input and output arrays suitable for
     *               requestAndValidateJson().
     */
    protected function provideRestEndpointTests(
        array $validInput,
        array $options
    ) {
        $tests = [];
        // Test failed authentication.
        if (
            array_key_exists('authentication', $options)
            && $options['authentication']
        ) {
            $tests[] = [
                'unauthenticated',
                'pub',
                $validInput,
                $this->validateAuthorizationErrorResponse(401)
            ];
        }
        // Test failed authorization.
        if (array_key_exists('authorization', $options)) {
            $runAs = $options['authorization'];
            foreach (self::getBaseRoles() as $role) {
                if ('pub' !== $role && $options['authorization'] !== $role) {
                    $tests[] = [
                        'unauthorized',
                        $role,
                        $validInput,
                        $this->validateAuthorizationErrorResponse(403)
                    ];
                }
            }
        }
        // Set the role for running the tests.
        if (!isset($runAs)) {
            $runAs = $options['run_as'];
        }
        // Determine whether API token authorization is used on this endpoint.
        $tokenAuth = (
            array_key_exists('token_auth', $options) && $options['token_auth']
        );
        // Test failed token authentication.
        if ($tokenAuth && $this instanceof TokenAuthTest) {
            foreach ($this->provideTokenAuthTestData() as $testData) {
                list($role, $tokenType) = $testData;
                if ('valid_token' !== $tokenType) {
                    $tests[] = [
                        $tokenType,
                        $role,
                        $tokenType,
                        $validInput,
                        []
                    ];
                }
            }
        }
        // Test missing required parameters.
        foreach (array_keys($validInput[$options['param_source']]) as $param) {
            $input = $validInput;
            unset($input[$options['param_source']][$param]);
            $tests[] = self::getEndpointTestData(
                'missing_' . $param,
                $runAs,
                $tokenAuth,
                $input,
                $this->validateMissingRequiredParameterResponse($param)
            );
        }
        // Test invalid parameters.
        $validators = [
            'int_params' => function ($param) {
                return $this->validateInvalidIntParameterResponse($param);
            },
            'date_params' => function ($param) {
                return $this->validateInvalidDateParameterResponse($param);
            }
        ];
        foreach ($validators as $key => $validator) {
            if (array_key_exists($key, $options)) {
                foreach ($options[$key] as $param) {
                    $input = $validInput;
                    $input[$options['param_source']][$param] = 'foo';
                    $tests[] = self::getEndpointTestData(
                        $param . '_string',
                        $runAs,
                        $tokenAuth,
                        $input,
                        $validator($param)
                    );
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
     * @return array
     */
    protected function validateMissingRequiredParameterResponse($name)
    {
        return $this->validateBadRequestResponse(
            "$name is a required parameter.",
            0
        );
    }

    /**
     * Return an output array for use in requestAndValidateJson() that
     * validates 400 Bad Request responses in which a parameter with the given
     * name was not a valid integer.
     *
     * @param string $name
     * @return array
     */
    protected function validateInvalidIntParameterResponse($name)
    {
        return $this->validateInvalidParameterResponse($name, 'integer');
    }

    /**
     * Return an output array for use in requestAndValidateJson() that
     * validates 400 Bad Request responses in which a parameter with the given
     * name was not a valid ISO 8601 date.
     *
     * @param string $name
     * @return array
     */
    protected function validateInvalidDateParameterResponse($name)
    {
        return $this->validateInvalidParameterResponse($name, 'ISO 8601 Date');
    }

    /**
     * Return an output array for use in requestAndValidateJson() that
     * validates 400 Bad Request responses in which a parameter with the given
     * name was not the given type.
     *
     * @param string $name
     * @param string $type
     * @return array
     */
    protected function validateInvalidParameterResponse($name, $type)
    {
        return $this->validateBadRequestResponse(
            "Invalid value for $name. Must be a(n) $type.",
            0
        );
    }

    /**
     * Return an output array for use in requestAndValidateJson() that
     * validates 400 Bad Request responses expected to have the given message
     * and code in their JSON.
     *
     * @param string $message
     * @param int $code
     * @return array
     */
    protected function validateBadRequestResponse($message, $code)
    {
        return [
            'status_code' => 400,
            'body_validator' => $this->validateErrorResponseBody(
                $message,
                $code
            )
        ];
    }

    /**
     * Return an output array for use in requestAndValidateJson() that
     * validates authorization error responses with the given HTTP status code.
     *
     * @param int $statusCode
     * @return array
     */
    protected function validateAuthorizationErrorResponse($statusCode)
    {
        return [
            'status_code' => $statusCode,
            'body_validator' => $this->validateErrorResponseBody(
                (
                    'An error was encountered while attempting to process the'
                    . ' requested authorization procedure.'
                ),
                0
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
     * @return callable
     */
    protected function validateErrorResponseBody($message, $code)
    {
        return function ($body, $assertMessage) use ($message, $code) {
            $this->assertEquals(
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
     * @param callable|array $validator if callable, a method used to validate
     *                                  the response body. If an array, the
     *                                  expected response body.
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
                    $this->assertSame(true, $body['success'], $assertMessage);
                    $validator($body, $assertMessage);
                } else {
                    $this->assertEquals($validator, $body, $assertMessage);
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
        $this->assertRegExp(
            '/[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}/',
            $date
        );
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
     * @param array $params the new associative array of parameters, e.g., as
     *                      could be used as a 'params' or 'data' property.
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
     * @param string $message prepended to the error message shown when a
     *                        test assertion fails.
     * @return object the actual JSON object after having been JSON encoded
     *                and decoded.
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
                $this->fail($message . $e->getMessage());
            }
        } else {
            $this->assertSame($expectedStr, $actualStr, $message);
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

    /**
     * @see provideRestEndpointTests().
     */
    private static function getEndpointTestData(
        $id,
        $role,
        $tokenAuth,
        array $input,
        array $output
    ) {
        $testData = [$id, $role];
        if ($tokenAuth) {
            $testData[] = 'valid_token';
        }
        array_push($testData, $input, $output);
        return $testData;
    }
}

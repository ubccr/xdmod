<?php

namespace IntegrationTests;

use CCR\Json;
use Exception;
use Swaggest\JsonSchema\Schema;
use TestHarness\IntegrationTestConfiguration;
use TestHarness\TestFiles;
use TestHarness\Utilities;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected static $XDMOD_REALMS;
    protected static $testFiles;

    /**
     * for json test input artifacts that describe http requests to be made to
     * endpoints and json test output artifacts that describe the expected
     * responses, these are the required keys.
     */
    protected static $REQUIRED_ENDPOINT_TEST_KEYS = [
        'input' => [
            'path', // string, e.g., 'rest/warehouse/export/realms'.
            'method', // string, e.g., 'get', 'post', 'delete', 'patch'.
            'params', // object containing query parameters of the request.
            'data' // object containing request body data.
        ],
        'output' => [
            'status_code', // int, e.g., 200, 404.
            'body' // json object.
        ]
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
     * A dataProvider for tests that use each of the base roles.
     */
    public function provideBaseRoles()
    {
        return array(
            array('pub'),
            array('cd'),
            array('cs'),
            array('pi'),
            array('usr'),
            array('mgr')
        );
    }

    /**
     * Load a JSON test artifact file into an associative array, replacing the
     * string '${INTEGRATION_ROOT}' with the path to the integration test
     * artifacts directory. If the artifacts has a 'body' object, that object
     * will have a '$path' key added to it whose value is the path to the file;
     * this key can be later stripped out by
     * @see BaseTest::requestAndValidateJson() and used to prepare the error
     * message that is displayed when validation fails.
     *
     * @param string $testGroup the directory (relative to the test artifacts
     *                          directory) containing the file.
     * @param string $fileName the name of the file.
     * @param string $fileType the type of file, i.e., the subdirectory in
     *                         which the file is located.
     * @return array the test artifact as an associative array.
     * @throws Exception if there is an error loading the file.
     */
    protected function loadJsonTestArtifact($testGroup, $fileName, $fileType)
    {
        $filePath = self::getTestFiles()->getFile(
            $testGroup,
            $fileName,
            $fileType
        );
        $artifact = IntegrationTestConfiguration::defaultAssocArrayFactory(
            $filePath,
            self::getTestFiles()
        );
        if (isset($artifact['body'])) {
            $artifact['body']['$path'] = $filePath;
        }
        return $artifact;
    }

    /**
     * Make an HTTP request and make assertions about the JSON response's
     * status code, content type, body, and possibly headers.
     *
     * @param \TestHarness\XdmodTestHelper $testHelper the test helper making
     *                                                 the HTTP request.
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
     * @throws Exception if the input object does not contain all of the
     *                   required keys or if there is an error making the
     *                   request, loading the JSON output file, or running the
     *                   validation of it.
     */
    protected function requestAndValidateJson(
        $testHelper,
        $input,
        $output
    ) {
        // Make sure the input and output objects have all the required keys.
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
            . "\nEXPECTED BODY: "
            . self::getJsonStringForExceptionMessage($output['body'])
            . "\nACTUAL BODY: "
            . self::getJsonStringForExceptionMessage($actualBody)
            . "\n"
        );

        // If the expected body has a path defined, strip it out before making
        // any assertions.
        unset($output['body']['$path']);

        // Make assertions
        $this->assertSame($output['status_code'], $actualStatusCode, $message);
        $this->assertSame('application/json', $actualContentType, $message);
        $actualBody = json_decode(json_encode($actualBody));
        $this->validateJson($output['body'], $actualBody, $message);
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
        $expectedJson = IntegrationTestConfiguration::defaultAssocArrayFactory(
            $expectedFilePath,
            self::getTestFiles()
        );
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
     * Given a JSON associative array, return a string representation of it for
     * printing in exception messages. If the array has the key '$path',
     * return only that it was defined in the file at that path. Otherwise,
     * return the pretty-printed JSON itself, truncated at 1000 characters.
     *
     * @param array $json the JSON associative array.
     * @return string the string representation.
     */
    private function getJsonStringForExceptionMessage($json)
    {
        return (
            isset($json['$path'])
            ? 'defined in ' . $json['$path']
            : self::truncateStr(
                json_encode($json, JSON_PRETTY_PRINT),
                1000
            )
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
}

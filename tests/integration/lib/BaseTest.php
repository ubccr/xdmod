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
     * artifacts directory, and adding a '$path' key whose value is the path to
     * the file; this key can be later stripped out by
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
        $artifact['$path'] = $filePath;
        return $artifact;
    }

    /**
     * Make an HTTP request and make assertions about the JSON response's
     * status code, content type, body, and possibly headers.
     *
     * @param \TestHarness\XdmodTestHelper $testHelper the test helper making
     *                                                 the HTTP request.
     * @param array $input associative array describing the HTTP request. Must
     *                     contain the following keys:
     *                     - 'path' the path of the request, e.g.,
     *                              '/rest/warehouse/export/realms'.
     *                     - 'method' the method of the request, i.e.,
     *                                'get', 'post', 'delete', or 'patch'.
     *                     - 'params' the query parameters of the request.
     *                     - 'data' the request body data.
     * @param int $expectedStatusCode the expected status code.
     * @param array $expectedBody associative array representing the expected
     *                            JSON response body.
     * @param array|null $expectedHeaders if not null, associative array
     *                                    containing header keys and values
     *                                    that are expected to be present in
     *                                    the response (not necessarily the
     *                                    full set of these).
     * @return mixed the actual decoded JSON response body.
     * @throws Exception if the input object does not contain all of the
     *                   required keys or if there is an error making the
     *                   request, loading the JSON output file, or running the
     *                   validation of it.
     */
    protected function requestAndValidateJson(
        $testHelper,
        $input,
        $expectedStatusCode,
        $expectedBody,
        $expectedHeaders = null
    ) {
        // Make sure the input object has all the required keys.
        foreach (['path', 'method', 'params', 'data'] as $requiredKey) {
            if (!array_key_exists($requiredKey, $input)) {
                throw new Exception(
                    "input object is missing key '$requiredKey':\n"
                    . var_export($input, true)
                );
            }
        }

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

        // If the expected body has a path defined, extract it in preparation
        // for providing it in any test failure messages.
        if (array_key_exists('$path', $expectedBody)) {
            $expectedBodyPath = $expectedBody['$path'];
            unset($expectedBody['$path']);
        }

        // Prepare test failure message.
        $message = "\nPATH: $input[path]\nMETHOD: $input[method]\nHEADERS: "
            . json_encode($testHelper->getheaders(), JSON_PRETTY_PRINT)
            . "\nPARAMS: " . json_encode($input['params'], JSON_PRETTY_PRINT)
            . "\nDATA: " . json_encode($input['data'], JSON_PRETTY_PRINT)
            . "\nEXPECTED STATUS CODE: $expectedStatusCode"
            . "\nACTUAL STATUS CODE: $actualStatusCode"
            . "\nEXPECTED CONTENT TYPE: application/json"
            . "\nACTUAL CONTENT TYPE: $actualContentType"
            . "\nEXPECTED BODY: "
            . (
                isset($expectedBodyPath)
                ? "defined in $expectedBodyPath"
                : self::truncateStr(
                    json_encode($expectedBody, JSON_PRETTY_PRINT),
                    1000
                )
            )
            . "\nACTUAL BODY: " . self::truncateStr(
                json_encode($actualBody, JSON_PRETTY_PRINT),
                1000
            )
            . "\n";

        // Make assertions
        $this->assertSame($expectedStatusCode, $actualStatusCode, $message);
        $this->assertSame('application/json', $actualContentType, $message);
        $actualBody = json_decode(json_encode($actualBody));
        $this->validateJson($expectedBody, $actualBody, $message);
        if (!is_null($expectedHeaders)) {
            foreach ($expectedHeaders as $key => $value) {
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

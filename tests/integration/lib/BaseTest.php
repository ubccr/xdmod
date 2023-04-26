<?php

namespace IntegrationTests;

use CCR\Json;
use Exception;
use Swaggest\JsonSchema\Schema;
use TestHarness\Utilities;
use TestHarness\TestFiles;

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
     * Perform an HTTP request and optionally make assertions about the
     * response's status code, content type, and/or body.
     *
     * @param \TestHarness\XdmodTestHelper $testHelper performs the HTTP
     *                                                 request.
     * @param string $path the path of the request, e.g.,
     *                     '/rest/warehouse/export/realms'.
     * @param string $verb the method of the request, i.e.,
     *                     'get', 'post', 'delete', or 'patch'.
     * @param array|object|null $params the query parameters of the request.
     * @param array|object|null $data the body data of the request.
     * @param int|null $expectedHttpCode if provided, the test will assert
     *                                   the response status code is the
     *                                   same as this.
     * @param string|null $expectedContentType if provided, the test will
     *                                         assert the content type of the
     *                                         response is the same as this.
     * @param string|null $expectedFileGroup if provided along with
     *                                       $expectedFileName and
     *                                       $validationType, the test will
     *                                       read a JSON output file in this
     *                                       directory (relative to the test
     *                                       artifacts directory) against
     *                                       which to validate the response
     *                                       body.
     * @param string|null $expectedFileName if provided along with
     *                                      $expectedFileGroup and
     *                                      $validationType, the test will
     *                                      read a JSON file with this name in
     *                                      the 'output' directory of the
     *                                      $expectedFileGroup directory and
     *                                      validate the response body against
     *                                      it.
     * @param string|null $validationType the method by which to validate the
     *                                    response body against the provided
     *                                    JSON output file, i.e., 'schema',
     *                                    which will validate it against a JSON
     *                                    Schema, or 'exact', which will do an
     *                                    exact comparison to the JSON object
     *                                    in the file.
     * @return mixed the decoded JSON response body.
     * @throws \Exception if there is an error making the request, loading
     *                    the JSON output file, or running the validation of
     *                    it.
     */
    public function makeRequest(
        $testHelper,
        $path,
        $verb,
        $params = null,
        $data = null,
        $expectedHttpCode = null,
        $expectedContentType = null,
        $expectedFileGroup = null,
        $expectedFileName = null,
        $validationType = null
    ) {
        $response = null;
        switch ($verb) {
            case 'get':
                $response = $testHelper->$verb($path, $params);
                break;
            case 'post':
            case 'delete':
            case 'patch':
                $response = $testHelper->$verb($path, $params, $data);
                break;
        }
        if (isset($response)) {
            $actualHttpCode = $response[1]['http_code'];
            $actualContentType = $response[1]['content_type'];
            $actualResponseBody = $response[0];
        } else {
            $actualHttpCode = null;
            $actualContentType = null;
            $actualResponseBody = [];
        }
        $message = "PATH: $path\nVERB: $verb\nHEADERS: "
            . json_encode($testHelper->getheaders(), JSON_PRETTY_PRINT)
            . "\nPARAMS: " . json_encode($params, JSON_PRETTY_PRINT)
            . "\nDATA: " . json_encode($data, JSON_PRETTY_PRINT)
            . "\nEXPECTED HTTP CODE: $expectedHttpCode"
            . "\nACTUAL HTTP CODE: $actualHttpCode"
            . "\nEXPECTED CONTENT TYPE: $expectedContentType"
            . "\nACTUAL CONTENT TYPE: $actualContentType";
        if (isset($expectedHttpCode)) {
            $this->assertSame(
                $expectedHttpCode,
                $actualHttpCode,
                $message
            );
        }
        if (isset($expectedContentType)) {
            $this->assertSame(
                $expectedContentType,
                $actualContentType,
                $message
            );
        }
        $actual = json_decode(json_encode($actualResponseBody));
        if (
            isset($expectedFileGroup)
            && isset($expectedFileName)
            && isset($validationType)
        ) {
            $this->validateJson(
                $actual,
                $expectedFileGroup,
                $expectedFileName,
                'output',
                $validationType,
                $message
            );
        }
        return $actual;
    }

    /**
     * Assert that a given JSON object validates against a provided file.
     * 
     * @param object $json the JSON object to validate.
     * @param string $testGroup the directory (relative to the test artifacts
     *                          directory) containing the file against which
     *                          to validate.
     * @param string $fileName the name of the file against which to validate.
     * @param string $fileType the type of file, i.e., the subdirectory in
     *                         which the file is located against which to
     *                         validate, defaults to 'output'.
     * @param string $validationType the method by which to validate the
     *                               JSON object against the provided file,
     *                               i.e., 'schema' (the default), which will
     *                               validate it against a JSON Schema, or
     *                               'exact', which will do an exact comparison
     *                               to the JSON object in the file.
     * @param string $message the prefix of an error message to be shown when a
     *                        test assertion fails.
     * @return object the $json object provided after having been JSON encoded
     *                and decoded.
     * @throws \Exception if there is an error loading the file or running the
     *                    validation.
     */
    public function validateJson(
        $json,
        $testGroup,
        $fileName,
        $fileType = 'output',
        $validationType = 'schema',
        $message = ''
    ) {
        $expectedFilePath = self::getTestFiles()->getFile(
            $testGroup,
            $fileName,
            $fileType,
            '.json'
        );
        $actualObject = json_decode(json_encode($json), false);
        if ('exact' === $validationType) {
            $expectedObject = self::loadRawJsonFile(
                $expectedFilePath,
                'allow_inheritance'
            );
            $this->assertSame(
                json_encode($expectedObject),
                json_encode($actualObject),
                $message . "\nEXPECTED OUTPUT FILE: $expectedFilePath"
            );
        } elseif ('schema' === $validationType) {
            $expectedObject = Json::loadFile($expectedFilePath, false);
            $expectedObject = self::resolveRemoteSchemaRefs(
                $expectedObject,
                dirname($expectedFilePath)
            );
            $schema = Schema::import($expectedObject);
            try {
                $schema->in($actualObject);
            } catch (Exception $e) {
                $a = json_encode($actualObject);
                $this->fail(
                    $e->getMessage() . "\nEXPECTED SCHEMA: $expectedFilePath"
                    . "\nACTUAL OBJECT: "
                    . (strlen($a) > 1000 ? substr($a, 0, 1000) . '...' : $a)
                );
            }
        }
        return $actualObject;
    }

    /**
     * Load a JSON object from a file.
     *
     * @param string $path the path to the file.
     * @param string $allowInheritance if this value is 'allow_inheritance',
     *                                 the object in the file can inherit
     *                                 properties from an object in another
     *                                 file by specifying an '$extends'
     *                                 property whose value is the path to
     *                                 the file containing that other object.
     *                                 This is recursive to allow for arbitrary
     *                                 depths of inheritance.
     * @return object the JSON object.
     * @throws \Exception if there is an error loading the file.
     */
    private function loadRawJsonFile($path, $allowInheritance) {
        $object = Json::loadFile($path, true);
        if ('allow_inheritance' === $allowInheritance) {
            if (isset($object['$extends'])) {
                $parentObject = self::loadRawJsonFile(
                    self::resolveExternalFilePath(
                        $object['$extends'],
                        dirname($path)
                    ),
                    $allowInheritance 
                );
                $object = array_replace_recursive($parentObject, $object);
                unset($object['$extends']);
            }
        }
        return $object;
    }

    /**
     * Given a JSON schema object loaded from a file, recursively replace the
     * values of any '$ref' properties with the path to the other schema file
     * being referenced (unless the value starts with a '#', which indicates a
     * reference that is local to the object and not a reference to an external
     * file).
     *
     * @param object $obj the JSON object.
     * @param string $schemaDir the path to the directory containing the file
     *                          from which the object was loaded.
     * @return object the JSON object with the values of the '$ref' properties
     *                replaced.
     * @throws \Exception if there are any errors resolving paths to files.
     */
    private static function resolveRemoteSchemaRefs($obj, $schemaDir)
    {
        foreach ($obj as $key => $value) {
            if ('$ref' === $key && '#' !== $value[0]) {
                $obj->$key = self::resolveExternalFilePath($value, $schemaDir);
            } elseif ('object' === gettype($value)
                    || 'array' === gettype($value)) {
                $value = self::resolveRemoteSchemaRefs($value, $schemaDir);
            }
        }
        return $obj;
    }

    /**
     * Given the path to a file and its parent directory's path, return the
     * full path to the file. If the path contains the string
     * '${INTEGRATION_ROOT}', the string is replaced with the path to the
     * integration test artifacts directory.
     *
     * @param string $path the path.
     * @param string $parentPath the parent directory's path.
     * @return string the full path to the file.
     * @throws \Exception if there are any errors resolving the path to the
     *                    integration test artifacts directory.
     */
    private static function resolveExternalFilePath($path, $parentPath)
    {
        if (false !== strpos($path, '${INTEGRATION_ROOT}')) {
            return self::getTestFiles()->getFile(
                'integration',
                str_replace(
                    '${INTEGRATION_ROOT}/',
                    '',
                    $path
                ),
                '',
                ''
            );
        } else {
            return $parentPath . '/' . $path;
        }
    }
}

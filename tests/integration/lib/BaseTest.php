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
        $validationType = null,
        $expectedHeaders = null
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
        $actualHttpCode = isset($response) ? $response[1]['http_code'] : null;
        $actualContentType = isset($response) ? $response[1]['content_type'] : null;
        $actualResponseBody = isset($response) ? $response[0] : array();
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

        if (isset($expectedFileName)) {
            $this->validateJson(
                $actual,
                $expectedFileGroup,
                $expectedFileName,
                'output',
                $validationType,
                $message
            );
        }

        if (isset($expectedHeaders)) {
            foreach ($expectedHeaders as $key => $value) {
                $this->assertArrayHasKey($key, $response[2], $message);
                $this->assertSame(
                    $value,
                    trim($response[2][$key]),
                    $message
                );
            }
        }

        return $actual;
    }

    public function validateJson(
        $json,
        $testGroup,
        $fileName,
        $fileType = 'output',
        $validationType = 'schema',
        $message = ''
    ) {
        $expectedFile = self::getTestFiles()->getFile(
            $testGroup,
            $fileName,
            $fileType,
            '.json'
        );
        $actualObject = json_decode(json_encode($json), false);
        if ('exact' === $validationType) {
            $expectedObject = self::loadRawJsonFile(
                $expectedFile,
                $validationType
            );
            $this->assertSame(
                json_encode($expectedObject),
                json_encode($actualObject),
                $message . "\nEXPECTED OUTPUT FILE: $expectedFile"
            );
        } elseif ('schema' === $validationType) {
            $expectedObject = Json::loadFile($expectedFile, false);
            $expectedObject = self::resolveRemoteSchemaRefs(
                $expectedObject,
                dirname($expectedFile)
            );
            $schema = Schema::import($expectedObject);
            try {
                $schema->in($actualObject);
            } catch (Exception $e) {
                $a = json_encode($actualObject);
                $this->fail(
                    $e->getMessage() . "\nEXPECTED SCHEMA: $expectedFile"
                    . "\nACTUAL OBJECT: "
                    . (strlen($a) > 1000 ? substr($a, 0, 1000) . '...' : $a)
                );
            }
        }
        return $actualObject;
    }

    private function loadRawJsonFile($file, $validationType) {
        $object = Json::loadFile($file, true);
        if ('exact' === $validationType) {
            if (isset($object['$extends'])) {
                $parentObject = self::loadRawJsonFile(
                    self::resolveExternalFilePath(
                        dirname($file),
                        $object['$extends']
                    ),
                    $validationType
                );
                $object = array_replace_recursive($parentObject, $object);
                unset($object['$extends']);
            }
        }
        return $object;
    }

    private static function resolveRemoteSchemaRefs($obj, $schemaDir)
    {
        foreach ($obj as $key => $value) {
            if ('$ref' === $key && '#' !== $value[0]) {
                $obj->$key = self::resolveExternalFilePath($schemaDir, $value);
            } elseif ('object' === gettype($value)
                    || 'array' === gettype($value)) {
                $value = self::resolveRemoteSchemaRefs($value, $schemaDir);
            }
        }
        return $obj;
    }

    private static function resolveExternalFilePath($parentPath, $path)
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

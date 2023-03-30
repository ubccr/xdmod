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
        $actualHttpCode = isset($response) ? $response[1]['http_code'] : null;
        $actualContentType = isset($response) ? $response[1]['content_type'] : null;
        $actualResponseBody = isset($response) ? $response[0] : array();
        $message = "PATH: $path\nVERB: $verb\nPARAMS: "
            . json_encode($params, JSON_PRETTY_PRINT) . "\nDATA: "
            . json_encode($data, JSON_PRETTY_PRINT) . "\n";
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
                '.json',
                $validationType
            );
        }

        return $actual;
    }

    public function validateJson(
        $json,
        $testGroup,
        $fileName,
        $fileType = 'output',
        $validationType = 'schema'
    ) {
        $expectedFile = self::getTestFiles()->getFile(
            $testGroup,
            $fileName,
            $fileType,
            '.json'
        );
        $expectedObject = self::loadJsonFile(
            $expectedFile,
            $testGroup,
            $fileType,
            $validationType
        );
        $actualObject = json_decode(json_encode($json), true);
        if ('exact' === $validationType) {
            $this->assertSame(
                json_encode($expectedObject),
                json_encode($actualObject)
            );
        } elseif ('schema' === $validationType) {
            $expectedObject = $this->resolveRemoteSchemaRefs(
                $expectedObject,
                dirname($expectedFile)
            );
            $schema = Schema::import($expectedObject);
            try {
                $schema->in($actualObject);
            } catch (Exception $e) {
                $this->fail(
                    $e->getMessage() . "\nEXPECTED SCHEMA: $expectedFile"
                    . "\nACTUAL OBJECT:" . json_encode($actualObject)
                );
            }
        }
        return $actualObject;
    }

    private function loadJsonFile(
        $file,
        $testGroup,
        $fileType,
        $validationType
    ) {
        $object = Json::loadFile($file, true);
        if ('exact' === $validationType) {
            if (isset($object['$extends'])) {
                $parentFile = self::getTestFiles()->getFile(
                    $testGroup,
                    $object['$extends'],
                    $fileType,
                    '.json'
                );
                $parentObject = self::loadJsonFile(
                    $parentFile,
                    $testGroup,
                    $object['$extends'],
                    $fileType,
                    $validationType
                );
                $object = array_replace_recursive($parentObject, $object);
                unset($object['$extends']);
            }
        }
        return $object;
    }

    private function resolveRemoteSchemaRefs($obj, $schemaDir)
    {
        foreach ($obj as $key => $value) {
            if ('$ref' === $key && '#' !== $value[0]) {
                $obj[$key] = $schemaDir . '/' . $value;
            } elseif ('array' === gettype($value)) {
                $value = $this->resolveRemoteSchemaRefs($value, $schemaDir);
            }
        }
        return $obj;
    }
}

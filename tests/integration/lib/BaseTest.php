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

    /**
     * @param XdmodTestHelper $testHelper
     * @param string $path
     * @param string $verb
     * @param array|null $params
     * @param array|null $data
     * @param int|null $expectedHttpCode
     * @param string|null $expectedContentType
     * @param string|null $expectedFileGroup
     * @param string|null $expectedFileName
     * @param string|null $validationType
     * @return mixed
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
        $actualHttpCode = isset($response) ? $response[1]['http_code'] : null;
        $actualContentType = isset($response) ? $response[1]['content_type'] : null;
        $actualResponseBody = isset($response) ? $response[0] : array();
        $message = "PATH: $path\nVERB: $verb\nPARAMS: "
            . json_encode($params, JSON_PRETTY_PRINT) . "\nDATA: "
            . json_encode($data, JSON_PRETTY_PRINT) . "\n";
        if (isset($expectedHttpCode)) {
            // Note $expectedHttpCode was changed to support being an array due to el7 returning 400 where el8 returns
            // 401.
            if (is_numeric($expectedHttpCode)) {
                $this->assertSame(
                    $expectedHttpCode,
                    $actualHttpCode,
                    $message
                );
            } elseif (is_array($expectedHttpCode)) {
                $this->assertContains(
                    $actualHttpCode,
                    $expectedHttpCode,
                    $message
                );
            }
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

    /**
     * Validate the provided $json w/ provided Json Schema file (specified by
     * its group, name, type, and extension) and asserting that there were no
     * errors. If the validation is successful then the decoded value is
     * returned.
     *
     * @param mixed $json the JSON structure to be validated.
     * @param string $testGroup
     * @param string $fileName
     * @param string $type
     * @param string $extension
     * @return mixed the decoded, valid json structure.
     */
    public function validateJson(
        $json,
        $testGroup,
        $fileName,
        $fileType = 'output',
        $extension = '.json',
        $validationType = 'schema'
    ) {
        $expectedFile = self::getTestFiles()->getFile(
            $testGroup,
            $fileName,
            $fileType,
            $extension
        );
        $expectedObject = Json::loadFile($expectedFile, false);
        $actualObject = json_decode(json_encode($json), false);
        if ($validationType === 'exact') {
            $this->assertSame(
                json_encode($expectedObject),
                json_encode($actualObject)
            );
        } elseif ($validationType === 'schema') {
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

    private function resolveRemoteSchemaRefs($obj, $schemaDir)
    {
        foreach ($obj as $key => $value) {
            if ('$ref' === $key && '#' !== $value[0]) {
                $obj->$key = $schemaDir . '/' . $value;
            } elseif ('object' === gettype($value)
                    || 'array' === gettype($value)) {
                $value = $this->resolveRemoteSchemaRefs($value, $schemaDir);
            }
        }
        return $obj;
    }
}

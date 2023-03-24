<?php

namespace IntegrationTests;

use CCR\Json;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
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
            array('usr')
        );
    }

    /**
     * @param XdmodTestHelper $testHelper
     * @param string $url
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
        $url,
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
                $response = $testHelper->$verb($url, $params);
                break;
            case 'post':
            case 'delete':
            case 'patch':
                $response = $testHelper->$verb($url, $params, $data);
                break;
        }
        $actualHttpCode = isset($response) ? $response[1]['http_code'] : null;
        $actualContentType = isset($response) ? $response[1]['content_type'] : null;
        $actualResponseBody = isset($response) ? $response[0] : array();

        if (isset($expectedHttpCode)) {
            // Note $expectedHttpCode was changed to support being an array due to el7 returning 400 where el8 returns
            // 401.
            if (is_numeric($expectedHttpCode)) {
                $this->assertSame($actualHttpCode, $expectedHttpCode);
            } elseif (is_array($expectedHttpCode)) {
                $this->assertContains($actualHttpCode, $expectedHttpCode);
            }
        }
        if (isset($expectedContentType)) {
            $this->assertSame($actualContentType, $expectedContentType);
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
                json_encode($actualObject),
                json_encode($expectedObject)
            );
        } elseif ($validationType === 'schema') {
            $validator = new Validator();
            $schemaStorage = new SchemaStorage();
            $schemaStorage->addSchema($expectedFile, $expectedObject);
            $validator = new Validator(new Factory($schemaStorage));
            $validator->validate($actualObject, $expectedObject);
            $errors = array();
            foreach ($validator->getErrors() as $err) {
                $errors[] = sprintf(
                    "[%s] %s\n",
                    $err['property'],
                    $err['message']
                );
            }
            $this->assertEmpty(
                $errors,
                implode("\n", $errors) . "\n"
                . json_encode($json, JSON_PRETTY_PRINT)
            );
        }
        return $actualObject;
    }
}

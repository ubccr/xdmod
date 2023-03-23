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
    protected function validateJson(
        $json,
        $testGroup,
        $fileName,
        $type = 'output',
        $extension = '.json'
    ) {
        $schemaFile = self::getTestFiles()->getFile(
            $testGroup,
            $fileName,
            $type,
            $extension
        );
        $validator = new Validator();
        $schemaObject = Json::loadFile($schemaFile, false);
        $schemaStorage = new SchemaStorage();
        $schemaStorage->addSchema($schemaFile, $schemaObject);
        $validator = new Validator(new Factory($schemaStorage));
        $actualDecoded = json_decode(json_encode($json));
        $validator->validate($actualDecoded, $schemaObject);
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
        return $actualDecoded;
    }
}

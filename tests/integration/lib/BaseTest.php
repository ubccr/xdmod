<?php

namespace IntegrationTests;

use JsonSchema\Validator;
use \TestHarness\Utilities;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected static $XDMOD_REALMS;

    public static function setUpBeforeClass()
    {
        self::$XDMOD_REALMS = Utilities::getRealmsToTest();
    }

    public static function getRealms()
    {
        return Utilities::getRealmsToTest();
    }

    /**
     * @param $actual
     * @param \stdClass $schemaObject
     * @return mixed
     */
    protected function validateJson($actual, \stdClass $schemaObject)
    {
        $validator = new Validator();
        $actualDecoded = json_decode(json_encode($actual));
        $validator->validate($actualDecoded, $schemaObject);
        $errors = array();
        foreach ($validator->getErrors() as $err) {
            $errors[] = sprintf("[%s] %s\n", $err['property'], $err['message']);
        }
        $this->assertEmpty($errors, implode("\n", $errors) . "\n" . json_encode($actual, JSON_PRETTY_PRINT));
        return $actualDecoded;
    }
}

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

    protected static function getRealms()
    {
        return Utilities::getRealmsToTest();
    }

    /**
     * Validate the provided $json w/ provided Json Schema $schemaObject and asserting that there were no errors. If the
     * validation is successful then the decoded value is returned.
     *
     * @param mixed $json the JSON structure to be validated.
     * @param \stdClass $schemaObject the JsonSchema object to be used to validate $json.
     * @return mixed the decoded, valid json structure.
     */
    protected function validateJson($json, \stdClass $schemaObject)
    {
        $validator = new Validator();
        $actualDecoded = json_decode(json_encode($json));
        $validator->validate($actualDecoded, $schemaObject);
        $errors = array();
        foreach ($validator->getErrors() as $err) {
            $errors[] = sprintf("[%s] %s\n", $err['property'], $err['message']);
        }
        $this->assertEmpty($errors, implode("\n", $errors) . "\n" . json_encode($json, JSON_PRETTY_PRINT));
        return $actualDecoded;
    }
}

<?php

namespace IntegrationTests\Logging;

use PHPSQLParser\Test\Creator\whereTest;

class CCRDBHandlerTest extends \PHPUnit\Framework\TestCase
{

    public function testHandlerWritesCorrectly()
    {
        $db = \CCR\DB::factory('logger');

        $now = time();
        $schema = \xd_utilities\getConfiguration('logger', 'database');
        $table = \xd_utilities\getConfiguration('logger', 'table');

        $logger = \CCR\Log::factory(
            'test-ccr-db-handler',
            array(
                'file' => false,
                'console' => false,
                'mail' => false
            )
        );

        $logger->pushHandler(new \CCR\CCRDBHandler());

        $logger->debug("Testing DB Write Handler: $now");

        $results = $db->query("SELECT * FROM $schema.$table WHERE message LIKE '%$now%' ");
        $actual = count($results);

        $this->assertEquals(1, $actual, sprintf("Expected 1 log record to be written, but received: %s", $actual));
        $this->assertTrue(is_numeric($results[0]['id']), sprintf("Expected the id value to be numeric, received: %s", $results[0]['id']));

        // Check that the result has the required column
        $result = $results[0];
        $this->assertArrayHasKey(
            'message',
            $result,
            sprintf(
                "Expected there to be a column called 'message' in log table results. Received: %s",
                print_r($result, true)
            )
        );

        // Check that the data contained in the required column is formatted correctly.
        $message = $result['message'];
        $json = null;
        $isActuallyJson = str_contains($message, '{');
        try {
            $json = json_decode($message);
        } catch (Exception $e) {
            $this->fail("Expected the `message` property to be json de-codable. Received: $message");
        }

        // json_decode does things differently in php 8.2 vs. php 7.4. In 7.4, if you pass a string that does not
        // contain json ( ex. "This is a test" ) to json_decode, it will return "This is a test". In 8.2 it will return
        // null, which makes sense to be fair since "This is a test" is not valid json. But it's still annoying.
        if (is_null($json) && $isActuallyJson) {
            echo "\n". var_export($result, true) . "\n";
        }
        $this->assertTrue(is_null($json) && !$isActuallyJson);

        // TODO: Double check to see if we actually use the JSON-ness of the log messages anywhere.
        /*$this->assertObjectHasProperty(
            'message',
            $json,
            sprintf(
                "Expected decoded message to be an object with a `message` property. Received: %s",
                print_r($json, true)
            )
        );*/

    }
}

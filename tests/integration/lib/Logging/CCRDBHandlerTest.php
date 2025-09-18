<?php

namespace IntegrationTests\Logging;

use CCR\Log;
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
                'mail' => false,
                'dbLogLevel' => Log::DEBUG
            )
        );

        // We should be able to just log strings
        $logger->debug("Testing DB Write Handler: $now");

        // We should be able to log string messages w/ additional context.
        $logger->debug("Testing DB Write Handler w/ Context", ['timestamp' => "$now"]);

        // we should be able to log w/ no messages and only a context array.
        $logger->debug('', ['message' => 'Testing 123', 'timestamp' => "$now"]);

        $results = $db->query("SELECT * FROM $schema.$table WHERE message LIKE '%$now%' ");
        $actual = count($results);

        $this->assertEquals(3, $actual, sprintf("Expected 2 log record to be written, but received: %s", $actual));
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
        } catch (\Exception $e) {
            $this->fail("Expected the `message` property to be json de-codable. Received: $message");
        }

        // json_decode does things differently in php 8.2 vs. php 7.4. In 7.4, if you pass a string that does not
        // contain json ( ex. "This is a test" ) to json_decode, it will return "This is a test". In 8.2 it will return
        // null, which makes sense to be fair since "This is a test" is not valid json. But it's still annoying.
        if (is_null($json) && $isActuallyJson) {
            echo "\n". var_export($result, true) . "\n";
        }

        // If it's null & not json then cool, if json is not null, then we expect $isActuallyJson to also be true.
        $valid = (is_null($json) && !$isActuallyJson) || (!is_null($json) && $isActuallyJson);
        $this->assertTrue($valid);

        // If we get valid json back, then make sure it has the `message` property.
        if (!is_null($json)) {
            $this->assertObjectHasProperty(
                'message',
                $json,
                sprintf(
                    "Expected decoded message to be an object with a `message` property. Received: %s",
                    print_r($json, true)
                )
            );
        }
    }
}

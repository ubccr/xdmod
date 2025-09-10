<?php

namespace IntegrationTests\Logging;

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
        try {
            $json = json_decode($message);
        } catch (\Exception $e) {
            $this->fail("Expected the `message` property to be json de-codable. Received: $message");
        }

        $this->assertNotNull($json);
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

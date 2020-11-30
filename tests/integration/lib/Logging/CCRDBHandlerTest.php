<?php

class CCRDBHandlerTest extends PHPUnit_Framework_TestCase
{

    public function testHandlerWritesCorrectly()
    {
        $db = \CCR\DB::factory('logger');

        $now = time();
        $schema = \xd_utilities\getConfiguration('logger', 'database');
        $table = \xd_utilities\getConfiguration('logger', 'table');

        $logger = new \Monolog\Logger('test-ccr-db-handler');
        $logger->pushHandler(new \CCR\CCRDBHandler());

        $logger->debug("Testing DB Write Handler: $now");

        $results = $db->query("SELECT * FROM $schema.$table WHERE message LIKE '%$now%' ");
        $actual = count($results);

        $this->assertEquals(1, $actual, sprintf("Expected 1 log record to be written, but received: %s", $actual));
        $this->assertTrue(is_numeric($results[0]['id']), sprintf("Expected the id value to be numeric, received: %s", $results[0]['id']));

    }


}

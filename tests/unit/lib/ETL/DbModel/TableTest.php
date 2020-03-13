<?php
/**
 * Test ETL table database models.
 */

namespace UnitTesting\ETL\Configuration;

use ETL\DbModel\Table;
use Log;
use PHPUnit_Framework_TestCase;

class TableTest extends PHPUnit_Framework_TestCase
{
    private static $logger;

    public static function setUpBeforeClass()
    {
        self::$logger = Log::singleton('null');
    }

    /**
     * Test that the table schema cannot be changed.
     *
     * @expectedException Exception
     */
    public function testTableSchemaError()
    {
        $config = (object) [
            'schema' => 'my_schema',
            'columns' => [
                (object) [
                    'name' => 'id',
                    'type' => 'int(11)'
                ]
            ]
        ];
        $table = new Table($config, '`', self::$logger);
        $table->schema = 'test';
    }
}

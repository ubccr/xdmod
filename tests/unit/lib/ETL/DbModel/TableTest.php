<?php
/**
 * Test ETL table database models.
 */

namespace UnitTests\ETL\DbModel;

use CCR\Log;
use ETL\DbModel\Table;
use Exception;
use \PHPUnit\Framework\TestCase;

class TableTest extends \PHPUnit\Framework\TestCase
{
    private static $logger;

    public static function setupBeforeClass(): void
    {
        self::$logger = Log::singleton('null');
    }

    /**
     * Test that the table schema cannot be changed.
     *
     *
     */
    public function testTableSchemaError()
    {
        $this->expectException(Exception::class);
        $config = (object) [
            'schema' => 'my_schema',
            'name' => 'my_table',
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

    /**
     * Test that the table schema may be set after the table is instantiated.
     */
    public function testTableSchemaAssignmentAfterInstantiation()
    {
        $schemaName = 'my_schema';
        $config = (object) [
            'name' => 'my_table',
            'columns' => [
                (object) [
                    'name' => 'id',
                    'type' => 'int(11)'
                ]
            ]
        ];
        $table = new Table($config, '`', self::$logger);
        $table->schema = $schemaName;
        $this->assertTrue($table->verify(), 'Table is verified');
        $this->assertEquals($schemaName, $table->schema, 'Schema name');
    }

    /**
     * Test that the table schema may be set by both the constructor and
     * afterward.
     */
    public function testTableSchemaDuplicateAssignment()
    {
        $schemaName = 'my_schema';
        $config = (object) [
            'schema' => $schemaName,
            'name' => 'my_table',
            'columns' => [
                (object) [
                    'name' => 'id',
                    'type' => 'int(11)'
                ]
            ]
        ];
        $table = new Table($config, '`', self::$logger);
        $table->schema = $schemaName;
        $this->assertTrue($table->verify(), 'Table is verified');
        $this->assertEquals($schemaName, $table->schema, 'Schema name');
    }

    /**
     * Test that the table schema may be set repeatedly to the same name.
     */
    public function testTableSchemaMultipleAssignment()
    {
        $schemaName = 'my_schema';

        // Schema in configuration object.
        $config = (object) [
            'schema' => $schemaName,
            'name' => 'my_table',
            'columns' => [
                (object) [
                    'name' => 'id',
                    'type' => 'int(11)'
                ]
            ]
        ];
        $table = new Table($config, '`', self::$logger);
        $table->schema = $schemaName;
        $table->schema = $schemaName;
        $this->assertTrue($table->verify(), 'Table is verified');
        $this->assertEquals($schemaName, $table->schema, 'Schema name');

        // No schema in configuration object.
        $config = (object) [
            'name' => 'my_table',
            'columns' => [
                (object) [
                    'name' => 'id',
                    'type' => 'int(11)'
                ]
            ]
        ];
        $table = new Table($config, '`', self::$logger);
        $table->schema = $schemaName;
        $table->schema = $schemaName;
        $this->assertTrue($table->verify(), 'Table is verified');
        $this->assertEquals($schemaName, $table->schema, 'Schema name');
    }

    /**
     * Test that the table schema must be a string.
     *
     * @dataProvider tableSchemaTypeErrorProvider
     */
    public function testTableSchemaTypeError($schemaName)
    {
        $this->expectException(Exception::class);
        $config = (object) [
            'schema' => $schemaName,
            'name' => 'my_table',
            'columns' => [
                (object) [
                    'name' => 'id',
                    'type' => 'int(11)'
                ]
            ]
        ];
        $table = new Table($config, '`', self::$logger);
        $table->verify();
    }

    public function tableSchemaTypeErrorProvider()
    {
        return [
            'boolean' => [
                true
            ],
            'number' => [
                1.1
            ],
            'array' => [
                ['schema' => 'schema_in_array']
            ],
            'object' => [
                (object) ['schema' => 'schema_in_object']
            ],
            'function' => [
                function () {
                    return 'schema_returned_from_function';
                }
            ]
        ];
    }
}

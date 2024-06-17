<?php
/**
 * Test ETL foreign key constraint database models.
 */

namespace UnitTests\ETL\DbModel;

use CCR\Log;
use ETL\DbModel\ForeignKeyConstraint;
use ETL\DbModel\Table;
use Exception;
use \PHPUnit\Framework\TestCase;
use IntegrationTests\TestHarness\TestFiles;
use stdClass;

class ForeignKeyConstraintTest extends TestCase
{
    const TEST_GROUP = 'unit/etl/db-model/foreign-key-constraint';

    private static $logger;

    private $testFiles;

    public static function setupBeforeClass(): void
    {
        self::$logger = Log::singleton('null');
    }

    private function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__ . '/../../../..');
        }
        return $this->testFiles;
    }

    /**
     * Test foreign key constraint initialization error.
     *
     *
     *
     */
    public function testForeignKeyConstraintInitializationError()
    {
        $this->expectExceptionMessage("\"columns\" must be an array");
        $this->expectException(Exception::class);
        $config = (object) [
            'name' => 'initialize_error',
            'columns' => [
                (object) [
                    'name' => 'column1',
                    'type' => 'int(11)',
                    'nullable' => false
                ]
            ],
            'indexes' => [
                (object) [
                    'columns' => [
                        'column1'
                    ]
                ]
            ],
            'foreign_key_constraints' => [
                (object) [
                    'referenced_table' => 'other_table',
                    'referenced_columns' => [
                        'id'
                    ]
                ]
            ]
        ];

        $table = new Table($config);
        $table->verify();
    }

    /**
     * Test that the given configuration results in a valid table.
     *
     * @dataProvider verificationProvider
     */
    public function testVerification(stdClass $config)
    {
        $table = new Table($config, '`', self::$logger);
        $this->assertTrue($table->verify());
    }

    /**
     * Test that the given configuration does not result in a valid table.
     *
     * @dataProvider verificationFailureProvider
     *
     */
    public function testVerificationFailure(stdClass $config)
    {
        $this->expectException(Exception::class);
        $table = new Table($config, '`', self::$logger);
        $table->verify();
    }

    /**
     * Test generating create table SQL.
     *
     * @dataProvider createTableProvider
     */
    public function testCreateTable(
        stdClass $tableConfig,
        array $expectedSql
    ) {
        $table = new Table($tableConfig, '`', self::$logger);
        $sql = $table->getSql();

        $this->assertCount(
            count($expectedSql),
            $sql,
            'Expected SQL statement count'
        );

        foreach ($expectedSql as $i => $expected) {
            $this->assertEquals(
                $expected,
                trim($sql[$i]),
                sprintf('SQL statement %d', $i + 1)
            );
        }
    }

    /**
     * Test generating alter table SQL.
     *
     * @dataProvider alterTableProvider
     */
    public function testAlterTable(
        stdClass $tableConfig,
        stdClass $fk1Config,
        stdClass $fk2Config,
        array $expectedSql
    ) {
        $origTable = new Table($tableConfig, '`', self::$logger);
        $origTable->addForeignKeyConstraint($fk1Config);

        $targetTable = new Table($tableConfig, '`', self::$logger);
        $targetTable->addForeignKeyConstraint($fk2Config);

        $sql = $origTable->getAlterSql($targetTable);

        $this->assertCount(
            count($expectedSql),
            $sql,
            'Expected SQL statement count'
        );

        foreach ($expectedSql as $i => $expected) {
            $this->assertEquals(
                $expected,
                trim($sql[$i]),
                sprintf('SQL statement %d', $i + 1)
            );
        }
    }

    /**
     * Test comparison of foreign key constraints.
     */
    public function testCompare()
    {
        $fk1 = new ForeignKeyConstraint((object) [
            'schema' => 'my_schema',
            'columns' => ['other_id'],
            'referenced_schema' => 'my_schema',
            'referenced_table' => 'other_table',
            'referenced_columns' => ['id']
        ]);

        $fk2 = new ForeignKeyConstraint((object) [
            'schema' => 'my_schema',
            'columns' => ['other_id'],
            'referenced_table' => 'other_table',
            'referenced_columns' => ['id']
        ]);

        $this->assertEquals(
            0,
            $fk1->compare($fk2),
            'fk1 == fk2 (referenced schema defaults to fk schema)'
        );
        $this->assertEquals(
            0,
            $fk2->compare($fk1),
            'fk2 == fk1 (referenced schema defaults to fk schema)'
        );

        $fk3 = new ForeignKeyConstraint((object) [
            'schema' => 'other_schema',
            'columns' => ['other_id'],
            'referenced_table' => 'other_table',
            'referenced_columns' => ['id']
        ]);

        $this->assertNotEquals(
            0,
            $fk1->compare($fk3),
            'fk1 != fk3 (fk in different schema is different)'
        );
        $this->assertNotEquals(
            0,
            $fk3->compare($fk1),
            'fk3 != fk1 (fk in different schema is different)'
        );
    }

    /**
     * Test inheriting of schema from table.
     */
    public function testSchemaInheritance()
    {
        $schemaName = 'my_schema';
        $config = (object) [
            'schema' => $schemaName,
            'name' => 'test',
            'engine' => 'InnoDB',
            'columns' => [
                (object) [
                    'name' => 'id',
                    'type' => 'int(11)'
                ]
            ],
            'indexes' => [
                (object) [
                    'columns' => ['id']
                ]
            ],
            'foreign_key_constraints' => [
                (object) [
                    'columns' => ['id'],
                    'referenced_table' => 'other',
                    'referenced_columns' => ['id']
                ]
            ]
        ];
        $table = new Table($config, '`', self::$logger);
        $table->verify();
        foreach ($table->foreign_key_constraints as $constraint) {
            $this->assertEquals($schemaName, $constraint->schema);
        }
    }

    /**
     * Convert associative arrays to stdClass recursively.
     *
     * @param array $obj
     * @param stdClass
     */
    private function arrayToStdClass(array $obj)
    {
        return json_decode(json_encode($obj));
    }

    /**
     * Load test data from file and convert to appropriate format.
     *
     * PHPUnit expects an associative array for named tests, but the ETL
     * classes expect stdClass input.
     *
     * Data is structured as an associative array with elements that are
     * numeric arrays.  Each element in the numeric array is converted to a
     * stdClass object.
     *
     * e.g.
     *
     * {
     *   "test 1": [
     *     {
     *       "a": "b"
     *     },
     *     {
     *       "c": "d"
     *     }
     *   ],
     *   "test 2" [
     *
     *   ]
     * }
     *
     * @param string $name The name of the JSON file (without ".json").
     * @return array
     */
    private function loadTestData($name)
    {
        return array_map(
            function ($inputData) {
                return array_map(
                    fn($obj) => json_decode(json_encode($obj)),
                    $inputData
                );
            },
            $this->getTestFiles()->loadJsonFile(self::TEST_GROUP, $name)
        );
    }

    public function verificationProvider()
    {
        return $this->loadTestData('verification');
    }

    public function verificationFailureProvider()
    {
        return $this->loadTestData('verification-failure');
    }

    public function createTableProvider()
    {
        return $this->loadTestData('create-table');
    }

    public function alterTableProvider()
    {
        return $this->loadTestData('alter-table');
    }
}

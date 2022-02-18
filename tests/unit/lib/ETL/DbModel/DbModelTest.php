<?php
/* ------------------------------------------------------------------------------------------
 * Component tests for ETL JSON configuration files
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-21
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTesting\ETL\Configuration;

use CCR\Log;
use ETL\Utilities;
use ETL\VariableStore;
use ETL\DbModel\Table;
use ETL\DbModel\AggregationTable;
use ETL\DbModel\Query;
use ETL\DbModel\Column;
use ETL\DbModel\Index;
use ETL\DbModel\ForeignKeyConstraint;
use ETL\DbModel\Trigger;
use ETL\Configuration\EtlConfiguration;
use Psr\Log\LoggerInterface;

class DbModelTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./../artifacts/xdmod/etlv2/dbmodel/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./../artifacts/xdmod/etlv2/dbmodel/output";

    /**
     * @var LoggerInterface|null
     */
    private static $logger = null;

    public static function setUpBeforeClass()
    {
        // Set up a logger so we can get warnings and error messages from the ETL
        // infrastructure
        $conf = array(
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Log::WARNING
        );
        self::$logger = Log::factory('PHPUnit', $conf);
    }

    /**
     * Test creating a table from a JSON file and feeding the generated JSON back to generate
     * the same table.
     */

    public function testParseJsonFile()
    {
        // Instantiate the reference table
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def-charset.json';
        $table = new Table($config, '`', self::$logger);
        $table->verify();

        // Verify SQL generated from JSON
        $generated = $table->getSql();
        $generated = array_shift($generated);
        $expected = trim(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/table_def-charset.sql'));
        $this->assertEquals($expected, $generated);

        // Run the generated JSON through and verify the generated SQL again.
        $newTable = new Table(json_decode($table->toJson()), '`', self::$logger);
        $generated = $newTable->getSql();
        $generated = array_shift($generated);
        $this->assertEquals($expected, $generated);
    }

    /**
     * Test generating SQL with and without a table schema.
     */

    public function testTableSchema()
    {
        $config = (object) array(
            'name' => "table_no_schema",
            'columns' => array(
                (object) array(
                    'name' => 'column1',
                    'type' => 'int(11)',
                    'nullable' => true,
                    'default' => 0,
                    'comment' => 'This is my comment'
                ),
                (object) array(
                    'name' => 'column2',
                    'type' => 'varchar(16)',
                    'nullable' => false,
                    'default' => 'Test Column',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_general_ci',
                    'comment' => 'No comment',
                ),
            ),
        );

        $table = new Table($config, '`', self::$logger);
        $table->schema = "my_schema";
        $table->verify();

        // SQL with no schema
        $generated = $table->getSql(false);
        $generated = array_shift($generated);
        $expected = "CREATE TABLE IF NOT EXISTS `table_no_schema` (
  `column1` int(11) NULL DEFAULT 0 COMMENT 'This is my comment',
  `column2` varchar(16) CHARSET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Test Column' COMMENT 'No comment'
);";
        $this->assertEquals($expected, $generated);

        // SQL with schema
        $generated = $table->getSql();
        $generated = array_shift($generated);
        $expected = "CREATE TABLE IF NOT EXISTS `my_schema`.`table_no_schema` (
  `column1` int(11) NULL DEFAULT 0 COMMENT 'This is my comment',
  `column2` varchar(16) CHARSET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Test Column' COMMENT 'No comment'
);";
        $this->assertEquals($expected, $generated);
    }

    /**
     * Test index initialization error.
     *
     * @expectedException Exception
     * @expectedExceptionMessage "columns" must be an array
     */
    public function testIndexInitializationError()
    {
        $config = (object) [
            'name' => 'initialize_error',
            'columns' => [
                (object) [
                    'name' => 'column1',
                    'type' => 'int(11)',
                    'nullable' => false,
                    'default' => 0,
                    'comment' => 'This is my comment'
                ]
            ],
            'indexes' => [
                (object) [
                    'type' => 'PRIMARY'
                ]
            ]
        ];

        $table = new Table($config);
        $table->verify();
    }

    /**
     * Test table verification error
     *
     * @expectedException Exception
     */

    public function testTableVerificationError()
    {
        $config = (object) array(
            'name' => "verification_error",
            'columns' => array( (object) array(
                'name' => 'column1',
                'type' => 'int(11)',
                'nullable' => true,
                'default' => 0,
                'comment' => 'This is my comment'
            )),
            'indexes' => array( (object) array(
                'columns' => array('column1', 'missing_column')
            ))
        );

        $table = new Table($config);  // No logger here
        $table->verify();
    }

    /**
     * Verify creating table elements manually.
     */

    public function testCreateSql()
    {
        $config = (object) array(
            'name' => 'column1',
            'type' => 'int(11)',
            'nullable' => true,
            'default' => 0,
            'comment' => 'This is my comment'
        );

        $obj = new Column($config, '`', self::$logger);
        $generated = $obj->getSql();
        $expected = "`column1` int(11) NULL DEFAULT 0 COMMENT 'This is my comment'";
        $this->assertEquals($expected, $generated);

        $config = (object) array(
            'name' => 'column2',
            'type' => 'varchar(16)',
            'nullable' => false,
            'default' => 'Test Column',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'comment' => 'No comment',
        );

        $obj = new Column($config, '`', self::$logger);
        $generated = $obj->getSql();
        $expected = "`column2` varchar(16) CHARSET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Test Column' COMMENT 'No comment'";
        $this->assertEquals($expected, $generated);

        $config = (object) array(
            'name' => 'created',
            'type' => 'datetime',
            'nullable' => false,
            'default' => 'NOW()',
            'comment' => 'The date and time at which a thing is created.'
        );
        $obj = new Column($config, '`', self::$logger);
        $generated = $obj->getSql();
        $expected = "`created` datetime NOT NULL DEFAULT NOW() COMMENT 'The date and time at which a thing is created.'";
        $this->assertEquals($expected, $generated);

        $config = (object) array(
            'columns' => array('col1', 'col2')
        );

        // Test with a system quote character
        $obj = new Index($config, '`', self::$logger);
        $generated = $obj->getSql();
        $expected = "INDEX `index_col1_col2` (`col1`, `col2`)";
        $this->assertEquals($expected, $generated);

        // Test with no system quote character
        $obj = new Index($config, null, self::$logger);
        $generated = $obj->getSql();
        $expected = "INDEX index_col1_col2 (col1, col2)";
        $this->assertEquals($expected, $generated);

        $config = (object) array(
            'columns' => array('col1', 'col2'),
            'referenced_table' => 'other_table',
            'referenced_columns' => array('col3', 'col4'),
        );

        // Test with a system quote character
        $obj = new ForeignKeyConstraint($config, '`', self::$logger);
        $generated = $obj->getSql();
        $expected = "CONSTRAINT `fk_col1_col2` FOREIGN KEY (`col1`, `col2`) REFERENCES `other_table` (`col3`, `col4`)";
        $this->assertEquals($expected, $generated);

        // Test with no system quote character
        $obj = new ForeignKeyConstraint($config, null, self::$logger);
        $generated = $obj->getSql();
        $expected = "CONSTRAINT fk_col1_col2 FOREIGN KEY (col1, col2) REFERENCES other_table (col3, col4)";
        $this->assertEquals($expected, $generated);

        $config = (object) array(
            'name' => 'before_ins',
            'time' => 'before',
            'event' => 'insert',
            'table' => 'jobfact',
            'body' => 'BEGIN DELETE FROM jobfactstatus WHERE job_id = NEW.job_id; END'
        );

        $obj = new Trigger($config, '`', self::$logger);
        $generated = $obj->getSql();
        $expected =
            "CREATE TRIGGER `before_ins` BEFORE INSERT ON `jobfact` FOR EACH ROW"
            . PHP_EOL
            . " BEGIN DELETE FROM jobfactstatus WHERE job_id = NEW.job_id; END";
        $this->assertEquals($expected, $generated);

    }

    /**
     * Test comparing 2 tables and the ALTER TABLE statement needed to go from one to the other.
     * Also manually add elements and verify the ALTER TABLE statement generated.
     */

    public function testAlterTable()
    {
        // Instantiate the reference table
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def-charset.json';
        $currentTable = new Table($config, '`', self::$logger);
        $currentTable->verify();
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def_2-charset.json';
        $destTable = new Table($config, '`', self::$logger);
        $destTable->verify();

        $generated = implode(PHP_EOL, $currentTable->getAlterSql($destTable));
        $file = self::TEST_ARTIFACT_OUTPUT_PATH . '/alter_table-charset.sql';
        $expected = trim(file_get_contents($file));
        $this->assertEquals($expected, $generated, sprintf("%s(): %s", __FUNCTION__, $file));

        // The table should generate no SQL if there are no changes.
        $generated = $currentTable->getAlterSql($currentTable);
        $this->assertFalse($generated, sprintf("%s(): Expected false (no SQL)", __FUNCTION__));

        // Alter the table by manually adding columns, index, and trigger.

        $config = (object) array(
            'name' => 'new_column',
            'type' => 'boolean',
            'nullable' => false,
            'default' => 0
        );
        $destTable->addColumn($config);

        $config = (object) array(
            'name' => 'new_column2',
            'type' => 'char(64)',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'nullable' => true,
        );
        $destTable->addColumn($config);

        $config = (object) array(
            'columns' => array('new_column')
        );
        $destTable->addIndex($config);

        $config = (object) array(
            'columns' => array('new_column'),
            'referenced_table' => 'other_table',
            'referenced_columns' => array('other_column'),
        );
        $destTable->addForeignKeyConstraint($config);

        $config = (object) array(
            'name' => 'before_ins',
            'time' => 'before',
            'event' => 'insert',
            'table' => 'jobfact',
            'body' => 'BEGIN DELETE FROM jobfactstatus WHERE job_id = NEW.job_id; END'
        );
        $destTable->addTrigger($config);

        // The getSql() and alterSql() methods return an array containing distinct SQL statements.
        $generated = implode(PHP_EOL, $currentTable->getAlterSql($destTable));
        $file = self::TEST_ARTIFACT_OUTPUT_PATH . '/alter_table_manually-charset.sql';
        $expected = trim(file_get_contents($file));
        $this->assertEquals($expected, $generated, sprintf("%s(): %s", __FUNCTION__, $file));
    }

    /**
     * Test removing all elements from the table
     */

    public function testDeleteTableElements()
    {
        // Instantiate the reference table
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def-charset.json';
        $table = new Table($config, '`', self::$logger);
        $table->verify();

        $table->resetPropertyValues();
        $this->assertFalse($table->getSql());
    }

    /**
     * Test the query object including variable substitution
     */

    public function testQuery()
    {
        $config = json_decode(file_get_contents(self::TEST_ARTIFACT_INPUT_PATH . '/resource_allocations.json'));
        $query = new Query($config->source_query, '"', self::$logger);
        $generated = $query->getSql();

        // Process variables present in the SQL
        $variableStore = new VariableStore(
            array(
                'TIMEZONE' => 'America/New_York',
                'SOURCE_SCHEMA' => 'xras'
            ),
            self::$logger
        );
        $generated = $variableStore->substitute(
            $generated,
            "Undefined macros found in source query"
        );

        $file = self::TEST_ARTIFACT_OUTPUT_PATH . '/resource_allocations_8.0.0.sql';
        $expected = trim(file_get_contents($file));
        $this->assertEquals($expected, $generated, "expected output in $file");
    }

    /**
     * Test generating an AggregationTable
     */

    public function testAggregationTable()
    {
        $aggregationUnit = 'quarter';
        $file = self::TEST_ARTIFACT_INPUT_PATH . '/resourceallocationfact_by.aggregation.json';

        $config = json_decode(file_get_contents($file));
        $table = new AggregationTable($config, '`', self::$logger);
        $table->aggregation_unit = $aggregationUnit;
        $generated = $table->getSql();
        $generated = array_shift($generated);

        // Process variables present in the SQL
        $variableStore = new VariableStore(
            array(
                'AGGREGATION_UNIT' => $aggregationUnit,
                'SOURCE_SCHEMA' => 'xras'
            ),
            self::$logger
        );
        $generated = $variableStore->substitute(
            $generated,
            "Undefined macros found in source query"
        );

        $file = self::TEST_ARTIFACT_OUTPUT_PATH . '/resourceallocationfact_by.aggregation.sql';
        $expected = trim(file_get_contents($file));
        $this->assertEquals($expected, $generated, "expected output in $file");
    }

    /**
     * Test generating SQL from an aggregation source query
     */

    public function testAggregationTableQuery()
    {
        $aggregationUnit = 'quarter';
        $file = self::TEST_ARTIFACT_INPUT_PATH . '/resourceallocationfact_by.aggregation.json';

        $config = json_decode(file_get_contents($file));
        $table = new AggregationTable($config, '`', self::$logger);
        $table->aggregation_unit = $aggregationUnit;
        $generated = $table->query->getSql();

        // Process variables present in the SQL
        $variableStore = new VariableStore(
            array(
                'AGGREGATION_UNIT' => $aggregationUnit,
                'SOURCE_SCHEMA' => 'modw_ra',
                'UTILITY_SCHEMA' => 'modw',
                ':PERIOD_ID' => ':period_id',
                ':YEAR_VALUE' => ':year_value',
                ':PERIOD_VALUE' => ':period_value',
                ':PERIOD_START_TS' => ':period_start_ts',
                ':PERIOD_END_TS' => ':period_end_ts'
            ),
            self::$logger
        );
        $generated = $variableStore->substitute(
            $generated,
            "Undefined macros found in source query"
        );

        $file = self::TEST_ARTIFACT_OUTPUT_PATH . '/resourceallocationfact_by.query_8.0.0.sql';
        $expected = trim(file_get_contents($file));
        $this->assertEquals($expected, $generated, "expected output in $file");
    }

    /**
     * Test generating a stdClass from an object
     */

    public function testGenerateQueryStdClass()
    {
        // Generate a query
        $config = json_decode(file_get_contents(self::TEST_ARTIFACT_INPUT_PATH . '/resource_allocations.json'));
        $query = new Query($config->source_query, '"', self::$logger);

        // Generate the stdclass and pass it back to generate the same query
        $obj = $query->toStdClass();
        $newQuery = new Query($obj, '"', self::$logger);
        $generated = $newQuery->getSql();

        $variableStore = new VariableStore(
            array(
                'TIMEZONE' => 'America/New_York',
                'SOURCE_SCHEMA' => 'xras'
            ),
            self::$logger
        );
        $generated = $variableStore->substitute(
            $generated,
            "Undefined macros found in source query"
        );

        $file = self::TEST_ARTIFACT_OUTPUT_PATH . '/resource_allocations_8.0.0.sql';
        $expected = trim(file_get_contents($file));
        $this->assertEquals($expected, $generated, "expected output in $file");
    }

    /**
     * Test generating a stdClass from an table
     */

    public function testGenerateTableStdClass()
    {
        // Instantiate the reference table
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def-charset.json';
        $table = new Table($config, '`', self::$logger);
        $table->verify();

        // Generate the stdclass and pass it back to generate the same table
        $obj = $table->toStdClass();
        $newTable = new Table($obj, '`', self::$logger);
        $generated = $newTable->getSql();
        $generated = array_shift($generated);

        $expected = trim(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/table_def-charset.sql'));
        $this->assertEquals($expected, $generated);
    }
} // class ConfigurationTest

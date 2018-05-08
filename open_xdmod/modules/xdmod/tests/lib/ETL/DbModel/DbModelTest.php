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
use ETL\DbModel\Table;
use ETL\DbModel\AggregationTable;
use ETL\DbModel\Query;
use ETL\DbModel\Column;
use ETL\DbModel\Index;
use ETL\DbModel\ForeignKeyConstraint;
use ETL\DbModel\Trigger;
use ETL\Configuration\EtlConfiguration;

class DbModelTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/dbmodel/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/dbmodel/output";
    private $logger = null;

    public function __construct()
    {
        // Set up a logger so we can get warnings and error messages from the ETL
        // infrastructure
        $conf = array(
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Log::WARNING
        );
        $this->logger = Log::factory('PHPUnit', $conf);
    }

    /**
     * Test creating a table from a JSON file and feeding the generated JSON back to generate
     * the same table.
     */

    public function testParseJsonFile()
    {
        // Instantiate the reference table
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def_8.0.0.json';
        $table = new Table($config, '`', $this->logger);
        $table->verify();

        // Verify SQL generated from JSON
        $generated = $table->getSql();
        $generated = array_shift($generated);
        $expected = trim(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/table_def_8.0.0.sql'));
        $this->assertEquals($expected, $generated);

        // Run the generated JSON through and verify the generated SQL again.
        $newTable = new Table(json_decode($table->toJson()), '`', $this->logger);
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
            'columns' => array( (object) array(
                'name' => 'column1',
                'type' => 'int(11)',
                'nullable' => true,
                'default' => 0,
                'comment' => 'This is my comment'
            ))
        );

        $table = new Table($config, '`', $this->logger);
        $table->schema = "my_schema";
        $table->verify();

        // SQL with no schema
        $generated = $table->getSql(false);
        $generated = array_shift($generated);
        $expected = "CREATE TABLE IF NOT EXISTS `table_no_schema` (
  `column1` int(11) NULL DEFAULT 0 COMMENT 'This is my comment'
);";
        $this->assertEquals($expected, $generated);

        // SQL with schema
        $generated = $table->getSql();
        $generated = array_shift($generated);
        $expected = "CREATE TABLE IF NOT EXISTS `my_schema`.`table_no_schema` (
  `column1` int(11) NULL DEFAULT 0 COMMENT 'This is my comment'
);";
        $this->assertEquals($expected, $generated);
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

        $obj = new Column($config, '`', $this->logger);
        $generated = $obj->getSql();
        $expected = "`column1` int(11) NULL DEFAULT 0 COMMENT 'This is my comment'";
        $this->assertEquals($expected, $generated);

        $config = (object) array(
            'columns' => array('col1', 'col2')
        );

        // Test with a system quote character
        $obj = new Index($config, '`', $this->logger);
        $generated = $obj->getSql();
        $expected = "INDEX `index_col1_col2` (`col1`, `col2`)";
        $this->assertEquals($expected, $generated);

        // Test with no system quote character
        $obj = new Index($config, null, $this->logger);
        $generated = $obj->getSql();
        $expected = "INDEX index_col1_col2 (col1, col2)";
        $this->assertEquals($expected, $generated);

        $config = (object) array(
            'columns' => array('col1', 'col2'),
            'referenced_table' => 'other_table',
            'referenced_columns' => array('col3', 'col4'),
        );

        // Test with a system quote character
        $obj = new ForeignKeyConstraint($config, '`', $this->logger);
        $generated = $obj->getSql();
        $expected = "CONSTRAINT `constraint_col1_col2` FOREIGN KEY (`col1`, `col2`) REFERENCES `other_table` (`col3`, `col4`)";
        $this->assertEquals($expected, $generated);

        // Test with no system quote character
        $obj = new ForeignKeyConstraint($config, null, $this->logger);
        $generated = $obj->getSql();
        $expected = "CONSTRAINT constraint_col1_col2 FOREIGN KEY (col1, col2) REFERENCES other_table (col3, col4)";
        $this->assertEquals($expected, $generated);

        $config = (object) array(
            'name' => 'before_ins',
            'time' => 'before',
            'event' => 'insert',
            'table' => 'jobfact',
            'body' => 'BEGIN DELETE FROM jobfactstatus WHERE job_id = NEW.job_id; END'
        );

        $obj = new Trigger($config, '`', $this->logger);
        $generated = $obj->getSql();
        $expected =
            "CREATE TRIGGER `before_ins` before insert ON `jobfact` FOR EACH ROW"
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
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def_8.0.0.json';
        $currentTable = new Table($config, '`', $this->logger);
        $currentTable->verify();
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def_2_8.0.0.json';
        $destTable = new Table($config, '`', $this->logger);
        $destTable->verify();

        $generated = $currentTable->getAlterSql($destTable);
        $generated = array_shift($generated);
        $expected = trim(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/alter_table_8.0.0.sql'));
        // Assert that there is no alter sql statement.
        $this->assertEquals($expected, $generated);

        // Alter the table by manually adding a column, index, constraint and trigger.

        $config = (object) array(
            'name' => 'new_column',
            'type' => 'boolean',
            'nullable' => false,
            'default' => 0
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

        // The getSql() and getSql() methods return an array containing distinct SQL
        // statements.
        $generated = $currentTable->getAlterSql($destTable);
        $alterTable = array_shift($generated);
        $trigger = array_shift($generated);
        $generated = $alterTable . PHP_EOL . $trigger;
        $expected = trim(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/alter_table_manually_8.0.0.sql'));
        $this->assertEquals($expected, $generated);
    }

    /**
     * Test removing all elements from the table
     */

    public function testDeleteTableElements()
    {
        // Instantiate the reference table
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def_8.0.0.json';
        $table = new Table($config, '`', $this->logger);
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
        $query = new Query($config->source_query, '"', $this->logger);
        $generated = $query->getSql();

        // Process variables present in the SQL
        $variableMap = array(
            'TIMEZONE' => 'America/New_York',
            'SOURCE_SCHEMA' => 'xras'
        );
        $generated = Utilities::substituteVariables(
            $generated,
            $variableMap,
            $query,
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
        $table = new AggregationTable($config, '`', $this->logger);
        $table->aggregation_unit = $aggregationUnit;
        $generated = $table->getSql();
        $generated = array_shift($generated);

        // Process variables present in the SQL
        $variableMap = array(
            'AGGREGATION_UNIT' => $aggregationUnit,
            'SOURCE_SCHEMA' => 'xras'
        );
        $generated = Utilities::substituteVariables(
            $generated,
            $variableMap,
            $table,
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
        $table = new AggregationTable($config, '`', $this->logger);
        $table->aggregation_unit = $aggregationUnit;
        $generated = $table->query->getSql();

        // Process variables present in the SQL
        $variableMap = array(
            'AGGREGATION_UNIT' => $aggregationUnit,
            'SOURCE_SCHEMA' => 'modw_ra',
            'UTILITY_SCHEMA' => 'modw',
            ':PERIOD_ID' => ':period_id',
            ':YEAR_VALUE' => ':year_value',
            ':PERIOD_VALUE' => ':period_value',
            ':PERIOD_START_TS' => ':period_start_ts',
            ':PERIOD_END_TS' => ':period_end_ts'
        );
        $generated = Utilities::substituteVariables(
            $generated,
            $variableMap,
            $table,
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
        $query = new Query($config->source_query, '"', $this->logger);

        // Generate the stdclass and pass it back to generate the same query
        $obj = $query->toStdClass();
        $newQuery = new Query($obj, '"', $this->logger);
        $generated = $newQuery->getSql();

        $variableMap = array(
            'TIMEZONE' => 'America/New_York',
            'SOURCE_SCHEMA' => 'xras'
        );
        $generated = Utilities::substituteVariables(
            $generated,
            $variableMap,
            $newQuery,
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
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def_8.0.0.json';
        $table = new Table($config, '`', $this->logger);
        $table->verify();

        // Generate the stdclass and pass it back to generate the same table
        $obj = $table->toStdClass();
        $newTable = new Table($obj, '`', $this->logger);
        $generated = $newTable->getSql();
        $generated = array_shift($generated);

        $expected = trim(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/table_def_8.0.0.sql'));
        $this->assertEquals($expected, $generated);
    }
} // class ConfigurationTest

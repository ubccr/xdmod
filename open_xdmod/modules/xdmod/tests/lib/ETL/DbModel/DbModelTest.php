<?php
/* ------------------------------------------------------------------------------------------
* Component tests for ETL JSON configuration files
*
* @author Steve Gallo <smgallo@buffalo.edu>
* @date 2017-04-21
* ------------------------------------------------------------------------------------------
*/

namespace UnitTesting\ETL\Configuration;

use ETL\DbModel\Table;
use ETL\DbModel\Column;
use ETL\DbModel\Index;
use ETL\DbModel\Trigger;
use ETL\Configuration\EtlConfiguration;

class DbModelTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "../../../../vendor/ubccr/xdmod-test-artifacts/xdmod/etlv2/dbmodel/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "../../../../vendor/ubccr/xdmod-test-artifacts/xdmod/etlv2/dbmodel/output";

    /**
    * Test creating a table from a JSON file and feeding the generated JSON back to generate
    * the same table.
    */

    public function testParseJsonFile()
    {
        // Instantiate the reference table
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def.json';
        $table = new Table($config, '`');
        $table->verify();

        // Verify SQL generated from JSON
        $generated = $table->getCreateSql();
        $generated = array_shift($generated);
        $expected = trim(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/table_def.sql'));
        $this->assertEquals($generated, $expected);

        // Run the generated JSON through and verify the generated SQL again.
        $newTable = new Table(json_decode($table->toJson()), '`');
        $generated = $newTable->getCreateSql();
        $generated = array_shift($generated);
        $this->assertEquals($generated, $expected);
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

        $obj = new Column($config);
        $generated = $obj->getCreateSql();
        $expected = "column1 int(11) NULL DEFAULT 0 COMMENT 'This is my comment'";
        $this->assertEquals($generated, $expected);

        $config = (object) array(
            'columns' => array('col1', 'col2')
        );

        $obj = new Index($config);
        $generated = $obj->getCreateSql();
        $expected = "INDEX index_col1_col2 (col1, col2)";
        $this->assertEquals($generated, $expected);

        $config = (object) array(
            'name' => 'before_ins',
            'time' => 'before',
            'event' => 'insert',
            'table' => 'jobfact',
            'body' => 'BEGIN DELETE FROM jobfactstatus WHERE job_id = NEW.job_id; END'
        );

        $obj = new Trigger($config);
        $generated = $obj->getCreateSql();
        $expected =
        "CREATE TRIGGER before_ins before insert ON jobfact FOR EACH ROW"
        . PHP_EOL
        . " BEGIN DELETE FROM jobfactstatus WHERE job_id = NEW.job_id; END";
        $this->assertEquals($generated, $expected);

    }

    /**
    * Test comparing 2 tables and the ALTER TABLE statement needed to go from one to the other.
    * Also manually add elements and verify the ALTER TABLE statement generated.
    */

    public function testAlterTable()
    {
        // Instantiate the reference table
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def.json';
        $currentTable = new Table($config, '`');
        $currentTable->verify();

        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def_2.json';
        $destTable = new Table($config, '`');
        $destTable->verify();

        $generated = $currentTable->getAlterSql($destTable);
        $generated = array_shift($generated);
        $expected = trim(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/alter_table.sql'));
        // Assert that there is no alter sql statement.
        $this->assertEquals($generated, $expected);

        // Alter the table by manually adding a column, index, and trigger.

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
            'name' => 'before_ins',
            'time' => 'before',
            'event' => 'insert',
            'table' => 'jobfact',
            'body' => 'BEGIN DELETE FROM jobfactstatus WHERE job_id = NEW.job_id; END'
        );
        $destTable->addTrigger($config);

        // The getCreateSql() and getAlterSql() methods return an array containing distinct SQL
        // statements.
        $generated = $currentTable->getAlterSql($destTable);
        $alterTable = array_shift($generated);
        $trigger = array_shift($generated);
        $generated = $alterTable . PHP_EOL . $trigger;
        $expected = trim(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/alter_table_manually.sql'));
        $this->assertEquals($generated, $expected);
    }

    /**
    * Test removing all elements from the table
    */

    public function testDeleteTableElements()
    {
        // Instantiate the reference table
        $config = self::TEST_ARTIFACT_INPUT_PATH . '/table_def.json';
        $table = new Table($config, '`');
        $table->verify();

        $table->deleteColumns();
        $table->deleteIndexes();
        $table->deleteTriggers();
        $this->assertFalse($table->getCreateSql());
    }

    /**
    * Test removal of comments from a JSON file
    */

    public function testSchema()
    {
        // Get name with and without schema
    }
} // class ConfigurationTest

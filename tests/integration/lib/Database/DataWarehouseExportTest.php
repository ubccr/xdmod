<?php

namespace IntegrationTests\Database;

use CCR\DB;
use CCR\DB\MySQLHelper;
use PHPUnit_Framework_TestCase;

/**
 * Test the data warehouse export database table.
 */
class DataWarehouseExportTest extends PHPUnit_Framework_TestCase
{
    /**
     * Name of the data warehouse export batch requests table.
     */
    const EXPORT_REQUEST_TABLE_NAME = 'batch_export_requests';

    /** @var \CCR\DB */
    private $db;

    /** @var \CCR\DB\MySQLHelper */
    private $dbHelper;

    public function setUp()
    {
        $this->db = DB::factory('database');
        $this->dbHelper = MySQLHelper::factory($this->db);
    }

    /**
     * Test that the table used by the data warehouse export exists.
     */
    public function testTableExists()
    {
        $this->assertTrue(
            $this->dbHelper->tableExists(self::EXPORT_REQUEST_TABLE_NAME),
            sprintf('Table `%s` exists', self::EXPORT_REQUEST_TABLE_NAME)
        );
    }

    /**
     * Test that the table used by the data warehouse export is empty.
     *
     * @depends testTableExists
     */
    public function testTableEmpty()
    {
        list($row) = $this->db->query(
            sprintf(
                'SELECT COUNT(*) AS count FROM `%s`',
                self::EXPORT_REQUEST_TABLE_NAME
            )
        );
        $this->assertEquals(
            0,
            $row['count'],
            sprintf('Table `%s` is empty', self::EXPORT_REQUEST_TABLE_NAME)
        );
    }
}

<?php

namespace IntegrationTests\Database;

use CCR\DB;
use CCR\Json;
use PHPUnit_Framework_TestCase;
use TestHarness\TestFiles;

/**
 * Test the resource names and codes in the database.
 */
class ResourceNamesTest extends PHPUnit_Framework_TestCase
{
    private $db;

    private $testFiles;

    public function setUp()
    {
        $this->db = DB::factory('datawarehouse');
        $this->testFiles = new TestFiles(__DIR__ . '/../../../');
    }

    public function testResourcesNamesValues()
    {
        $actual = $this->db->query('SELECT code, name FROM modw.resourcefact ORDER BY code');
        $expected = Json::loadFile($this->testFiles->getFile('integration/database', 'resource_names'));
        $this->assertEquals($expected, $actual);
    }
}

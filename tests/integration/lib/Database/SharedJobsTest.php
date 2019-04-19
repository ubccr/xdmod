<?php

namespace IntegrationTests\Database;

use CCR\DB;
use CCR\Json;
use PHPUnit_Framework_TestCase;
use TestHarness\TestFiles;

/**
 * Test the "shared_jobs" values in the database.
 */
class SharedJobsTest extends PHPUnit_Framework_TestCase
{
    private $db;

    private $testFiles;

    public function setUp()
    {
        $this->db = DB::factory('datawarehouse');
        $this->testFiles = new TestFiles(__DIR__ . '/../../');
    }

    public function testResourcesSharedJobsValues()
    {
        $actual = $this->db->query('SELECT code, shared_jobs FROM modw.resourcefact ORDER BY code');
        $expected = Json::loadFile($this->testFiles->getFile('integration/database', 'shared_jobs'));
        $this->assertEquals($expected, $actual);
    }
}

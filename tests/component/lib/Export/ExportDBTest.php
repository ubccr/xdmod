<?php

namespace ComponentTests;

use CCR\DB;
use \Exception;
use \DataWarehouse\Export\QueryHandler;


class ExportDBTest extends BaseTest
{
    static $dbh = null;
    static $maxId = null;
    private static $userId = 34; // Tom Furlani

    public function testCountSubmitted()
    {
        $query = new QueryHandler();
        $submittedCount = $query->countSubmittedRecords();
        $this->assertNotNull($submittedCount);
        $this->assertTrue($submittedCount>=0);
    }

    private function findSubmittedRecord()
    {
        $query = new QueryHandler();

        // Find or create a record in submitted status to transition
        $maxSubmitted = static::$dbh->query('SELECT MAX(id) AS id FROM batch_export_requests where export_succeeded IS NULL')[0]['id'];
        if ($maxSubmitted == NULL) {
            $maxSubmitted = $query->createRequestRecord(self::$userId, 'Jobs', '2017-01-01','2017-08-01');
        }
        return($maxSubmitted);
    }

    public function testNewRecordCreation()
    {
        $query = new QueryHandler();

        // find the count
        $initialCount = $query->countSubmittedRecords();

        // add new record and verify
        $requestId = $query->createRequestRecord(self::$userId, 'Jobs', '2019-01-01','2019-03-01');
        $this->assertNotNull($requestId);

        // determine final count
        $finalCount = $query->countSubmittedRecords();

        // verify final count
        $this->assertTrue($finalCount-$initialCount==1);
    }

    public function testSubmittedToFailed()
    {
        $query = new QueryHandler();

        // Find or create a record in submitted status to transition
        $maxSubmitted = $this->findSubmittedRecord();

        // Transition record
        $result = $query->submittedToFailed($maxSubmitted);
        $test = static::$dbh->query("SELECT export_succeeded FROM batch_export_requests where id=:id",
                                    array('id'=>$maxSubmitted))[0]['export_succeeded'];

        // Assert that:
        // Exactly one record was transitioned
        $this->assertTrue($result==1);

        // That record is marked export_succeeded=FALSE
        $this->assertEquals($test, 0);
    }

    // TODO
    // FAILS: needs export_expires_datetime column
    public function testSubmittedToAvailable()
    {
        $query = new QueryHandler();

        // Find or create a record in submitted status to transition
        $maxSubmitted = $this->findSubmittedRecord();

        $result = $query->submittedToAvailable($maxSubmitted);
        $test = static::$dbh->query("SELECT export_succeeded FROM batch_export_requests where id=:id",
                                    array('id'=>$maxSubmitted))[0]['export_succeeded'];

        // Assert that:
        // Exactly one record was transitioned
        $this->assertTrue($result==1);

        // That record is marked export_succeeded=TRUE
        $this->assertEquals($test, 1);
    }

    // TODO
    // FAILS: needs export_expires_datetime
    public function testAvailableToExpired()
    {
        $query = new QueryHandler();

        // Find or create a record in available status to transition
        $maxAvailable = static::$dbh->query('SELECT MAX(id) AS id FROM batch_export_requests where export_succeeded IS TRUE')[0]['id'];
        if ($maxAvailable == NULL) {
            $maxSubmitted = $this->findSubmittedRecord();
            $maxAvailable = $query->submittedToAvailable($maxSubmitted);
        }

        $result = $query->availableToExpired($maxAvailable);
        $test = static::$dbh->query("SELECT export_expired FROM batch_export_requests where id=:id",
                                    array('id'=>$maxAvailable))[0]['export_expired'];

        // Assert that:
        // Exactly one record was transitioned
        $this->assertTrue($result==1);

        // That record is marked export_expired=TRUE
        $this->assertEquals($test, 1);
    }

    public function testSubmittedRecordFieldList()
    {
        $query = new QueryHandler();

        // Expect these keys from the associative array
        $expectedKeys = array(
            'id',
            'realm',
            'start_date',
            'end_date'
        );

        $actual = $query->listSubmittedRecords();

        if (count($actual) > 0) {

            // check that you get the same fields back from the query...
            $this->assertEquals($expectedKeys, array_keys($actual[0]));
        }
    }

    // TODO
    // FAILS: needs export_expires_datetime
    public function testUserRecordFieldList()
    {
        $query = new QueryHandler();

        // Expect these keys from the associative array
        $expectedKeys = array(
            'id',
            'realm',
            'start_date',
            'end_date',
            'export_succeeded',
            'export_expires_datetime',
            'export_created_datetime'
        );

        // Requests via this user have been created as part of these tests
        $actual = $query->listRequestsForUser(self::$userId);

        if (count($actual) > 0) {

            // check that you get the same fields back from the query...
            $this->assertEquals($expectedKeys, array_keys($actual[0]));
        }
    }

    public static function setUpBeforeClass()
    {
        static::$dbh = DB::factory('database');
        static::$maxId = static::$dbh->query('SELECT COALESCE(MAX(id), 0) AS id FROM batch_export_requests')[0]['id'];
    }

    public static function tearDownAfterClass()
    {
        static::$dbh->execute('DELETE FROM batch_export_requests WHERE id > :id', array('id' => static::$maxId));
    }
}

<?php

namespace ComponentTests;

use CCR\DB;
use \Exception;
use \DataWarehouse\Export\QueryHandler;
use \XDUser;


class ExportDBTest extends BaseTest
    // Test access to batch_export_requests table, via QueryHandler class.
    // Clean up table to initial state following testing.
    // $debug variable, if TRUE, enables print of output from public functions,
{
    static $dbh = null;
    static $maxId = null;
    //private static $userName = self::NORMAL_USER_USER_NAME;
    private static $debug = TRUE;

    public function testCountSubmitted()
    {
        $query = new QueryHandler();
        $submittedCount = $query->countSubmittedRecords();
        $this->assertNotNull($submittedCount);
        $this->assertTrue($submittedCount>=0);

        // debug
        if (self::$debug) print("\n".__FUNCTION__.": submittedRecords=$submittedCount\n");
    }

    // Specify a user that actually exists in moddb.Users table.
    private function acquireUserId()
    {
        $userId = static::$dbh->query('SELECT MIN(id) AS id FROM Users')[0]['id'];
        if ($userId == NULL) {
            // TODO throw an exception, we have no users in the db
        }
        return $userId;
    }

    /*
    // Specify a different user that actually exists in moddb.Users table.
    private function acquireMaxUserId()
    {
        return static::$dbh->query('SELECT MAX(id) AS id FROM Users')[0]['id'];
    }
     */


    private function findSubmittedRecord()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId(); //XDUser::getUserByUserName(self::$userName);

        // Find or create a record in submitted status to transition
        $maxSubmitted = static::$dbh->query('SELECT MAX(id) AS id FROM batch_export_requests where export_succeeded IS NULL')[0]['id'];
        if ($maxSubmitted == NULL) {
            $maxSubmitted = $query->createRequestRecord($userId, 'Jobs', '2017-01-01','2017-08-01','CSV');
        }

        if (self::$debug) print("\n".__FUNCTION__.": maxSubmitted ID=$maxSubmitted\n");

        return($maxSubmitted);
    }

    private function findAvailableRecord()
    {
        $query = new QueryHandler();

        // Find or create a record in available status to transition
        $maxAvailable = static::$dbh->query('SELECT MAX(id) AS id FROM batch_export_requests where
                                            export_succeeded IS TRUE
                                            AND export_expired IS FALSE')[0]['id'];
        if ($maxAvailable == NULL) {
            $maxSubmitted = $this->findSubmittedRecord();
            $maxAvailable = $query->submittedToAvailable($maxSubmitted);
        }
        return($maxAvailable);
    }

    // Create two new records to enable testing of transition to Available, and transition to Expired:
    public function testNewRecordCreation()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId(); //XDUser::getUserByUserName(self::$userName);
        //if (self::$debug) print("\n".__FUNCTION__.": userName=".self::$userName." userId=$userId\n");

        // find the count
        $initialCount = $query->countSubmittedRecords();

        // add new record and verify
        $requestId = $query->createRequestRecord($userId, 'Jobs', '2019-01-01','2019-03-01','CSV');
        $this->assertNotNull($requestId);

        // add another new record and verify
        $requestId2 = $query->createRequestRecord($userId, 'Accounts', '2016-12-01','2017-01-01','JSON');

        $this->assertNotNull($requestId2 );
        $this->assertTrue($requestId2-$requestId==1);

        // determine final count
        $finalCount = $query->countSubmittedRecords();

        // verify final count
        // should have added 2 records.
        $this->assertTrue($finalCount-$initialCount==2);

        // debug
        if (self::$debug) print("\n".__FUNCTION__.": initialCount=$initialCount finalCount=$finalCount requestId=$requestId requestId2=$requestId2\n");
    }

    // Test should fail. Do not allow this transition.
    public function testAvailableToFailed()
    {
        $query = new QueryHandler();

        // Find or create a record in available status
        $maxAvailable = $this->findAvailableRecord();
        if (self::$debug) print("\n".__FUNCTION__.": max=$maxAvailable\n");

        // Attempt to transition the record
        $result = $query->submittedToFailed($maxAvailable);
        $test = static::$dbh->query("SELECT export_succeeded FROM batch_export_requests where id=:id",
                                    array('id'=>$maxAvailable))[0]['export_succeeded'];

        // Assert that:
        //
        // Exactly zero records were transitioned
        $this->assertTrue($result==0);

        // That record is marked export_succeeded=TRUE
        $this->assertEquals($test, 1);

        // That record has a non-null export created datetime
        $export_created = static::$dbh->query("SELECT export_created_datetime FROM batch_export_requests where id=:id",
                                    array('id'=>$maxAvailable))[0]['export_created_datetime'];

        // That record is marked export_created_datetime=NOT NULL
        // todo: has a datetime
        //$this->assertEquals($test, 1);

        // debug
        if (self::$debug) print("\n".__FUNCTION__.": NON transitioned Id=$maxAvailable\n");
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

        // debug
        if (self::$debug) print("\n".__FUNCTION__.": transitioned Id=$maxSubmitted\n");
    }

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

        // debug
        if (self::$debug) print("\n".__FUNCTION__.": transitioned Id=$maxSubmitted\n");
    }

    public function testAvailableToExpired()
    {
        $query = new QueryHandler();

        // Find or create a record in available status to transition
        $maxAvailable = $this->findAvailableRecord();

        $result = $query->availableToExpired($maxAvailable);
        $test = static::$dbh->query("SELECT export_expired FROM batch_export_requests where id=:id",
                                    array('id'=>$maxAvailable))[0]['export_expired'];

        // Assert that:
        // Exactly one record was transitioned
        $this->assertTrue($result==1);

        // That record is marked export_expired=TRUE
        $this->assertEquals($test, 1);

        // debug
        if (self::$debug) print("\n".__FUNCTION__.": transitioned Id=$maxAvailable\n");
    }

    public function testSubmittedRecordFieldList()
    {
        $query = new QueryHandler();

        // Expect these keys from the associative array
        $expectedKeys = array(
            'id',
            'realm',
            'start_date',
            'end_date',
            'export_file_format',
            'requested_datetime'
        );

        $actual = $query->listSubmittedRecords();

        if (count($actual) > 0) {

            // assert that the expected fields are returned from the query
            $this->assertEquals($expectedKeys, array_keys($actual[0]));
        }
    }

    public function testUserRecordFieldList()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId(); //XDUser::getUserByUserName(self::$userName);

        // Expect these keys from the associative array
        $expectedKeys = array(
            'id',
            'realm',
            'start_date',
            'end_date',
            'export_succeeded',
            'export_expired',
            'export_expires_datetime',
            'export_created_datetime',
            'export_file_format',
            'requested_datetime'
        );

        // Requests via this user have been created as part of these tests
        $actual = $query->listRequestsForUser($userId);

        if (count($actual) > 0) {

            // assert that the expected fields are returned from the query
            $this->assertEquals($expectedKeys, array_keys($actual[0]));
        }
    }

    public function testUserRecordReportStates()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId(); //XDUser::getUserByUserName(self::$userName);

        // Expect these keys from the associative array
        $expectedKeys = array(
            'id',
            'realm',
            'start_date',
            'end_date',
            'export_succeeded',
            'export_expired',
            'export_expires_datetime',
            'export_created_datetime',
            'export_file_format',
            'requested_datetime',
            'state'
        );

        // Requests via this user have been created as part of these tests
        $actual = $query->listUserRequestsByState($userId);

        if (count($actual) > 0) {

            // assert that the expected fields are returned from the query
            $this->assertEquals($expectedKeys, array_keys($actual[0]));
        }


    }

    // verify that user that created request can delete it
    public function testRecordDeleteCorrectUser()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId(); //XDUser::getUserByUserName(self::$userName);

        // Requests via this user have been created as part of these tests
        $actual = $query->listRequestsForUser($userId);

        if (count($actual) > 0) {

            // pick the first such request and try to delete it:
            $testVal = $query->deleteRequest($actual[0]['id'], $userId);

            // assert that the delete affected 1 row
            $this->assertEquals($testVal, 1);
            if (self::$debug) print("\n".__FUNCTION__.": deleted record? $testVal\n");
        }
    }

    // verify that user that did not create request cannot delete it
    public function testRecordDeleteIncorrectUser()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId();
        $wrongUserId = \XDUser::PUBLIC_USER;

        // Requests via user with $userId have been created as part of these tests
        $actual = $query->listRequestsForUser($userId);

        // Provided that different users created and try to delete:
        if (count($actual) > 0 && $userId != $wrongUserId) {

            // pick the first such request and try to delete it:
            $testVal = $query->deleteRequest($actual[0]['id'], $wrongUserId);

            // assert that the delete attempt affected 0 rows
            $this->assertEquals($testVal, 0);
            if (self::$debug) print("\n".__FUNCTION__.": deleted record? $testVal\n");
        }
    }

    // determine initial max id to enable cleanup after testing
    public static function setUpBeforeClass()
    {
        // so long as you use PUBLIC_USER_NAME or the like
        parent::setUpBeforeClass();
        static::$dbh = DB::factory('database');
        static::$maxId = static::$dbh->query('SELECT COALESCE(MAX(id), 0) AS id FROM batch_export_requests')[0]['id'];
    }

    // TODO: put it back...
    // Reset the table to where it started
    public static function tearDownAfterClass()
    {
        static::$dbh->execute('DELETE FROM batch_export_requests WHERE id > :id', array('id' => static::$maxId));
    }

}

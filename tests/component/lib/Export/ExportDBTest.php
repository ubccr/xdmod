<?php

namespace ComponentTests;

use CCR\DB;
use \Exception;
use \DataWarehouse\Export\QueryHandler;
use \XDUser;

// Test access to batch_export_requests table, via QueryHandler class.
class ExportDBTest extends BaseTest
{
    private static $dbh = null;

    // Used for: Clean up table to initial state following testing.
    private static $maxId = null;

    // $debug variable, if true, enables print of output from public functions,
    private static $debug = false;

    /* *********** PRIVATE HELPER METHODS *********** */

    // Acquire an existing userId for test record creation, deletion, transitions
    private function acquireUserId()
    {
        return XDUser::getUserByUserName(self::NORMAL_USER_USER_NAME)->getUserID();
    }

    private function findSubmittedRecord()
    {
        // Find or create a record in Submitted status
        $maxSubmitted = static::$dbh->query('SELECT MAX(id) AS id FROM batch_export_requests WHERE export_succeeded IS NULL')[0]['id'];

        if ($maxSubmitted == null) {
            $query = new QueryHandler();
            $userId = $this->acquireUserId();
            $maxSubmitted = $query->createRequestRecord($userId, 'Jobs', '2017-01-01', '2017-08-01','CSV');
        }
        return($maxSubmitted);
    }

    private function findAvailableRecord()
    {
        // Find or create a record in Available status
        $maxAvailable = static::$dbh->query('SELECT MAX(id) AS id FROM batch_export_requests WHERE
                                            export_succeeded = 1
                                            AND export_expired = 0')[0]['id'];
        if ($maxAvailable == null) {
            $query = new QueryHandler();
            $maxSubmitted = $this->findSubmittedRecord();
            $maxAvailable = $query->submittedToAvailable($maxSubmitted);
        }
        return($maxAvailable);
    }

    private function findExpiredRecord()
    {
        // Find or create a record in Expired status
        $maxExpired = static::$dbh->query('SELECT MAX(id) AS id FROM batch_export_requests WHERE
                                            export_expired = 1')[0]['id'];
        if ($maxExpired == null) {
            $query = new QueryHandler();
            $maxAvailable = $this->findAvailableRecord();
            $maxExpired = $query->availableToExpired($maxAvailable);
        }
        return($maxExpired);
    }

    private function findFailedRecord()
    {
        // Find or create a record in Failed status
        $maxFailed = static::$dbh->query('SELECT MAX(id) AS id FROM batch_export_requests WHERE
                                            export_succeeded = 0')[0]['id'];
        if ($maxFailed == null) {
            $query = new QueryHandler();
            $maxSubmitted = $this->findSubmittedRecord();
            $maxFailed = $query->submittedToFailed($maxSubmitted);
        }
        return($maxFailed);
    }

    private function flattenRecords($inArr)
    {
        $ids = array();
        foreach($inArr as $arr)
        {
            $ids[] = $arr['id'];
        }
        return $ids;
    }

    private function listSubmittedRecords()
    {
        // List ids of records in Submitted state
        $submittedArr = static::$dbh->query('SELECT id FROM batch_export_requests WHERE export_succeeded IS NULL');
        $retval = $this->flattenRecords($submittedArr);
        return($retval);
    }

    private function listAvailableRecords()
    {
        // List ids of records in Available state
        $availableArr = static::$dbh->query('SELECT id FROM batch_export_requests WHERE export_succeeded = 1 and export_expired = 0');
        $retval = $this->flattenRecords($availableArr);
        return($retval);
    }

    private function listExpiredRecords()
    {
        // List ids of records in Expired state
        $availableArr = static::$dbh->query('SELECT id FROM batch_export_requests WHERE export_succeeded = 1 and export_expired = 1');
        $retval = $this->flattenRecords($availableArr);
        return($retval);
    }

    private function listFailedRecords()
    {
        // List ids of records in Failed state
        $availableArr = static::$dbh->query('SELECT id FROM batch_export_requests WHERE export_succeeded = 0 and export_expired = 0');
        $retval = $this->flattenRecords($availableArr);
        return($retval);
    }

    /* *********** PUBLIC TESTS *********** */

    // Create three new records in Submitted state.
    public function testNewRecordCreation()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId();

        // Find the count
        $initialCount = $query->countSubmittedRecords();

        // Add new record and verify
        $requestId = $query->createRequestRecord($userId, 'Jobs', '2019-01-01', '2019-03-01','CSV');
        $this->assertNotNull($requestId);

        // Add another new record and verify
        $requestId2 = $query->createRequestRecord($userId, 'Accounts', '2016-12-01', '2017-01-01','JSON');
        $this->assertNotNull($requestId2 );

        // Add another new record and verify
        $requestId3 = $query->createRequestRecord($userId, 'Jobs', '2014-01-05', '2014-01-26','CSV');
        $this->assertNotNull($requestId3 );

        // Determine final count
        $finalCount = $query->countSubmittedRecords();

        // Verify final count. Should have added 3 records.
        $this->assertTrue($finalCount-$initialCount==3);

        // Verify newly created records are found in list of Submitted status records
        $allSubmitted = $this->listSubmittedRecords();
        $this->assertContains($requestId3, $allSubmitted);
        $this->assertContains($requestId2, $allSubmitted);
        $this->assertContains($requestId, $allSubmitted);

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": initialCount=$initialCount finalCount=$finalCount requestId=$requestId
                    requestId2=$requestId2 requestId3=$requestId3\n");
        }
    }

    public function testCountSubmitted()
    {
        $query = new QueryHandler();
        $submittedCount = $query->countSubmittedRecords();

        $submittedList = $this->listSubmittedRecords();

        $this->assertEquals($submittedCount, count($submittedList));
        $this->assertNotNull($submittedCount);
        $this->assertTrue($submittedCount>=0);

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": submittedRecords=$submittedCount\n");
        }
    }

    // Verify field list returned from listSubmittedRecords()
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

        // List all records in Submitted state:
        $actual = $query->listSubmittedRecords();

        if (count($actual) > 0) {

            // assert that the expected fields are returned from the query
            $this->assertEquals($expectedKeys, array_keys($actual[0]));
        }
    }
    public function testSubmittedToFailed()
    {
        $query = new QueryHandler();

        // Find or create a record in submitted status to transition
        $maxSubmitted = $this->findSubmittedRecord();

        // Transition the record to Failed
        $result = $query->submittedToFailed($maxSubmitted);

        // Assert that:
        // Exactly one record was transitioned
        $this->assertTrue($result==1);

        // This record is now marked Failed
        $allFailed = $this->listFailedRecords();
        $this->assertContains($maxSubmitted, $allFailed);

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": transitioned Id=$maxSubmitted\n");
        }
    }

    public function testSubmittedToExpired()
    {
        $query = new QueryHandler();

        // Find or create a record in Submitted status to transition
        $maxSubmitted = $this->findSubmittedRecord();

        $result = $query->availableToExpired($maxSubmitted);

        // Assert that:
        // Exactly zero records transitioned
        $this->assertTrue($result==0);

        // That record is still marked Submitted
        $allSubmitted = $this->listSubmittedRecords();
        $this->assertContains($maxSubmitted, $allSubmitted);

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": NON transitioned Id=$maxSubmitted\n");
        }
    }

    public function testSubmittedToAvailable()
    {
        $query = new QueryHandler();

        // Find or create a record in submitted status to transition
        $maxSubmitted = $this->findSubmittedRecord();

        $result = $query->submittedToAvailable($maxSubmitted);

        // Assert that:
        // Exactly one record was transitioned
        $this->assertTrue($result==1);

        // That record is marked Available
        $allAvailable = $this->listAvailableRecords();
        $this->assertContains($maxSubmitted, $allAvailable);

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": transitioned Id=$maxSubmitted\n");
        }
    }

    public function testAvailableToFailed()
    {
        $query = new QueryHandler();

        // Find or create a record in Available status
        $maxAvailable = $this->findAvailableRecord();

        // Attempt to transition the record to Failed
        $result = $query->submittedToFailed($maxAvailable);

        // Assert that:
        // Exactly zero records were transitioned
        $this->assertTrue($result==0);

        // This record is still marked Available
        $allAvailable = $this->listAvailableRecords();
        $this->assertContains($maxAvailable, $allAvailable);

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": NON transitioned Id=$maxAvailable\n");
        }
    }

    public function testAvailableToExpired()
    {
        $query = new QueryHandler();

        // Find or create a record in Available status to transition
        $maxAvailable = $this->findAvailableRecord();

        $result = $query->availableToExpired($maxAvailable);

        // Assert that:
        // Exactly one record was transitioned
        $this->assertTrue($result==1);

        // That record is marked export_expired=TRUE
        $allExpired = $this->listExpiredRecords();
        $this->assertContains($maxAvailable, $allExpired);

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": transitioned Id=$maxAvailable\n");
        }
    }


    public function testExpiredToFailed()
    {
        $query = new QueryHandler();

        // Find or create a record in Expired status
        $maxExpired = $this->findExpiredRecord();

        // Attempt to transition the record to Failed
        $result = $query->submittedToFailed($maxExpired);

        // Assert that:
        // Exactly zero records were transitioned
        $this->assertTrue($result==0);

        // This record is still marked Expired
        $allExpired = $this->listExpiredRecords();
        $this->assertContains($maxExpired, $allExpired);

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": NON transitioned Id=$maxExpired\n");
        }
    }

    public function testFailedToExpired()
    {
        $query = new QueryHandler();

        // Find or create a record in Failed status to transition
        $maxFailed = $this->findFailedRecord();

        $result = $query->availableToExpired($maxFailed);

        // Assert that:
        // Exactly zero records transitioned
        $this->assertTrue($result==0);

        // That record is marked Failed
        $allFailed = $this->listFailedRecords();
        $this->assertContains($maxFailed, $allFailed);

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": NON transitioned Id=$maxFailed\n");
        }
    }

    public function testExpiredToAvailable()
    {
        $query = new QueryHandler();

        // Find or create a record in Expired status to transition
        $maxExpired = $this->findExpiredRecord();

        $result = $query->submittedToAvailable($maxExpired);

        // Assert that:
        // Exactly zero records transitioned
        $this->assertTrue($result==0);

        // That record is marked Expired
        $allExpired = $this->listExpiredRecords();
        $this->assertContains($maxExpired, $allExpired);

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": NON transitioned Id=$maxExpired\n");
        }
    }

    public function testFailedToAvailable()
    {
        $query = new QueryHandler();

        // Find or create a record in Failed status to transition
        $maxFailed = $this->findFailedRecord();

        $result = $query->submittedToAvailable($maxFailed);

        // Assert that:
        // Exactly zero records transitioned
        $this->assertTrue($result==0);

        // That record is marked Failed
        $allFailed = $this->listFailedRecords();
        $this->assertContains($maxFailed, $allFailed);

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": NON transitioned Id=$maxFailed\n");
        }
    }


    // Verify field list returned from listRequestsForUser()
    public function testUserRecordFieldList()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId();

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

    // Verify field list returned from listUserRequestsByState()
    public function testUserRecordReportStates()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId();

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

    // Verify that user that did not create request cannot delete it
    public function testRecordDeleteIncorrectUser()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId();
        $wrongUserId = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME)->getUserID();

        // Requests via user with $userId have been created as part of these tests
        $actual = $query->listRequestsForUser($userId);

        // Provided that we have specified two different users here:
        if (count($actual) > 0 && $userId != $wrongUserId) {

            // pick the first such request and try to delete it:
            $testVal = $query->deleteRequest($actual[0]['id'], $wrongUserId);

            // assert that the delete attempt affected 0 rows
            $this->assertEquals($testVal, 0);

            if (self::$debug)
            {
                print("\n".__FUNCTION__.": deleted record id=".$actual[0]['id']." ? $testVal\n");
            }
        }
    }

    // Verify that user that created request can delete it
    public function testRecordDeleteCorrectUser()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId();

        // Requests via this user have been created as part of these tests
        $actual = $query->listRequestsForUser($userId);

        if (count($actual) > 0)
        {
            // pick the first such request and try to delete it:
            $testVal = $query->deleteRequest($actual[0]['id'], $userId);

            // assert that the delete affected 1 row
            $this->assertEquals($testVal, 1);

            if (self::$debug)
            {
                print("\n".__FUNCTION__.": deleted record id=".$actual[0]['id']." ? $testVal\n");
            }
        }
    }


    public static function setUpBeforeClass()
    {
        // setup needed to use NORMAL_USER_USER_NAME or the like
        parent::setUpBeforeClass();

        // determine initial max id to enable cleanup after testing
        static::$dbh = DB::factory('database');
        static::$maxId = static::$dbh->query('SELECT COALESCE(MAX(id), 0) AS id FROM batch_export_requests')[0]['id'];
    }

    public static function tearDownAfterClass()
    {
        // Reset the batch_export_requests database table to its initial contents
    //    static::$dbh->execute('DELETE FROM batch_export_requests WHERE id > :id', array('id' => static::$maxId));
    }
}

<?php

namespace ComponentTests\Export;

use CCR\DB;
use ComponentTests\BaseTest;
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
        // Find a record in Submitted status
        return static::$dbh->query('SELECT MAX(id) AS id FROM batch_export_requests WHERE export_succeeded IS NULL')[0]['id'];
    }

    private function findAvailableRecord()
    {
        // Find a record in Available status
        return static::$dbh->query('SELECT MAX(id) AS id FROM batch_export_requests WHERE
                                            export_succeeded = 1
                                            AND export_expired = 0')[0]['id'];
    }

    private function findExpiredRecord()
    {
        // Find a record in Expired status
        return static::$dbh->query('SELECT MAX(id) AS id FROM batch_export_requests WHERE
                                            export_expired = 1')[0]['id'];
    }

    private function findFailedRecord()
    {
        // Find a record in Failed status
        return static::$dbh->query('SELECT MAX(id) AS id FROM batch_export_requests WHERE
                                            export_succeeded = 0')[0]['id'];
    }

    private function countSubmittedRecords()
    {
        // Count records in Submitted state
        return static::$dbh->query('SELECT COUNT(id) AS count FROM batch_export_requests WHERE export_succeeded IS NULL')[0]['count'];
    }

    private function countAvailableRecords()
    {
        // Count records in Available state
        return static::$dbh->query('SELECT COUNT(id) AS count FROM batch_export_requests WHERE export_succeeded = 1 and export_expired = 0')[0]['count'];
    }

    private function countExpiredRecords()
    {
        // Count records in Expired state
        return static::$dbh->query('SELECT COUNT(id) AS count FROM batch_export_requests WHERE export_succeeded = 1 and export_expired = 1')[0]['count'];
    }

    private function countFailedRecords()
    {
        // List ids of records in Failed state
        return static::$dbh->query('SELECT COUNT(id) AS count FROM batch_export_requests WHERE export_succeeded = 0 and export_expired = 0')[0]['count'];
    }

    private function countUserRequests()
    {
        // Determine number of requests placed by this user
        $params= array('user_id' => $this->acquireUserId());
        $sql = 'SELECT COUNT(id) AS count FROM batch_export_requests WHERE user_id=:user_id';
        $retval = static::$dbh->query($sql, $params);
        return $retval[0]['count'];
    }

    /* *********** PUBLIC TESTS *********** */

    // Create three new records in Submitted state.
    public function testNewRecordCreation()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId();

        // Find the Submitted record count
        $initialCount = $query->countSubmittedRecords();

        // Add new record and verify
        $requestId = $query->createRequestRecord($userId, 'Jobs', '2019-01-01', '2019-03-01', 'CSV');
        $this->assertNotNull($requestId);

        // Add another new record and verify
        $requestId2 = $query->createRequestRecord($userId, 'Accounts', '2016-12-01', '2017-01-01', 'JSON');
        $this->assertNotNull($requestId2);

        // Add another new record and verify
        $requestId3 = $query->createRequestRecord($userId, 'Jobs', '2014-01-05', '2014-01-26', 'CSV');
        $this->assertNotNull($requestId3);

        // Determine final count
        $finalCount = $query->countSubmittedRecords();
        $finalCountTest = $this->countSubmittedRecords();

        $this->assertEquals(3, $finalCount - $initialCount, 'Verify final Submitted count. Should have added 3 records');
        $this->assertEquals($finalCount, $finalCountTest, 'Verify test and class methods return same Submitted counts');

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": initialCount=$initialCount finalCount=$finalCount requestId=$requestId
                    requestId2=$requestId2 requestId3=$requestId3\n");
        }
    }

    // Verify counts of Submitted records
    public function testCountSubmitted()
    {
        $query = new QueryHandler();
        $submittedCount = $query->countSubmittedRecords();
        $submittedCountTest = $this->countSubmittedRecords();

        $this->assertEquals($submittedCount, $submittedCountTest);
        $this->assertNotNull($submittedCount);
        $this->assertGreaterThanOrEqual(0, $submittedCount);

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
            'user_id',
            'realm',
            'start_date',
            'end_date',
            'export_file_format',
            'requested_datetime'
        );

        // List all records in Submitted state:
        $actual = $query->listSubmittedRecords();

        $this->assertEquals($expectedKeys, array_keys($actual[0]), 'the expected fields are returned from the query');
        $this->assertEquals($this->countSubmittedRecords(), count($actual), 'the expected number of records is returned from the query');
    }

    public function testSubmittedToFailed()
    {
        $query = new QueryHandler();

        // initial counts
        $submittedCountInitial = $this->countSubmittedRecords();
        $failedCountInitial = $this->countFailedRecords();

        // Find a record in submitted status to transition
        $maxSubmitted = $this->findSubmittedRecord();
        $result = $query->submittedToFailed($maxSubmitted);

        // final counts
        $submittedCountFinal = $this->countSubmittedRecords();
        $failedCountFinal = $this->countFailedRecords();

        $this->assertEquals(1, $result, 'Exactly one record was transitioned');
        $this->assertEquals($submittedCountInitial - 1, $submittedCountFinal, 'There is one fewer Submitted record');
        $this->assertEquals($failedCountInitial + 1, $failedCountFinal, 'There is one more Failed record');

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": transitioned Id=$maxSubmitted\n");
        }
    }

    public function testSubmittedToExpired()
    {
        $query = new QueryHandler();

        // initial counts
        $submittedCountInitial = $this->countSubmittedRecords();
        $expiredCountInitial = $this->countExpiredRecords();

        // Find a record in Submitted status to transition
        $maxSubmitted = $this->findSubmittedRecord();
        $result = $query->availableToExpired($maxSubmitted);

        // final counts
        $submittedCountFinal = $this->countSubmittedRecords();
        $expiredCountFinal = $this->countExpiredRecords();

        $this->assertEquals(0, $result, 'Exactly zero records transitioned');
        $this->assertEquals($submittedCountInitial, $submittedCountFinal, 'No change in Submitted counts occurred');
        $this->assertEquals($expiredCountInitial, $expiredCountFinal, 'No change in Expired state counts occurred');

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": NON transitioned Id=$maxSubmitted\n");
        }
    }

    public function testSubmittedToAvailable()
    {
        $query = new QueryHandler();

        // initial counts
        $submittedCountInitial = $this->countSubmittedRecords();
        $availCountInitial = $this->countAvailableRecords();

        // Find a record in Submitted status to transition
        $maxSubmitted = $this->findSubmittedRecord();
        $result = $query->submittedToAvailable($maxSubmitted);

        // final counts
        $submittedCountFinal = $this->countSubmittedRecords();
        $availCountFinal = $this->countAvailableRecords();

        $this->assertEquals(1, $result, 'Exactly one record was transitioned');
        $this->assertEquals($submittedCountInitial - 1, $submittedCountFinal, 'There is one fewer Submitted record');
        $this->assertEquals($availCountInitial + 1, $availCountFinal, 'There is one more Available record');

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": transitioned Id=$maxSubmitted\n");
        }
    }

    public function testAvailableToFailed()
    {
        $query = new QueryHandler();

        // initial counts
        $availCountInitial = $this->countAvailableRecords();
        $failCountInitial = $this->countFailedRecords();

        // Find a record in Available status to transition
        $maxAvailable = $this->findAvailableRecord();
        $result = $query->submittedToFailed($maxAvailable);

        // final counts
        $availCountFinal = $this->countAvailableRecords();
        $failCountFinal = $this->countFailedRecords();

        $this->assertEquals(0, $result, 'Exactly zero records were transitioned');
        $this->assertEquals($availCountInitial, $availCountFinal, 'No change in Available state counts occurred');
        $this->assertEquals($failCountInitial, $failCountFinal, 'No change in Failed state counts occurred');

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": NON transitioned Id=$maxAvailable\n");
        }
    }

    public function testAvailableToExpired()
    {
        $query = new QueryHandler();

        // initial counts
        $availCountInitial = $this->countAvailableRecords();
        $expiredCountInitial = $this->countExpiredRecords();

        // Find a record in Available status to transition
        $maxAvailable = $this->findAvailableRecord();
        $result = $query->availableToExpired($maxAvailable);

        // final counts
        $availCountFinal = $this->countAvailableRecords();
        $expiredCountFinal = $this->countExpiredRecords();

        $this->assertEquals(1, $result, 'Exactly one record was transitioned');
        $this->assertEquals($availCountInitial - 1, $availCountFinal, 'There is one fewer Available record');
        $this->assertEquals($expiredCountInitial + 1, $expiredCountFinal, 'There is one more Expired record');

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": transitioned Id=$maxAvailable\n");
        }
    }

    public function testExpiredToFailed()
    {
        $query = new QueryHandler();

        // initial counts
        $expiredCountInitial = $this->countExpiredRecords();
        $failCountInitial = $this->countFailedRecords();

        // Find a record in Expired status to transition
        $maxExpired = $this->findExpiredRecord();
        $result = $query->submittedToFailed($maxExpired);

        // final counts
        $expiredCountFinal = $this->countExpiredRecords();
        $failCountFinal = $this->countFailedRecords();

        $this->assertEquals(0, $result, 'Exactly zero records were transitioned');
        $this->assertEquals($expiredCountInitial, $expiredCountFinal, 'No change in Expired state counts occurred');
        $this->assertEquals($failCountInitial, $failCountFinal, 'No change in Failed state counts occurred');

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": NON transitioned Id=$maxExpired\n");
        }
    }

    public function testFailedToExpired()
    {
        $query = new QueryHandler();

        // initial counts
        $expiredCountInitial = $this->countExpiredRecords();
        $failCountInitial = $this->countFailedRecords();

        // Find or create a record in Failed status to transition
        $maxFailed = $this->findFailedRecord();
        $result = $query->availableToExpired($maxFailed);

        // final counts
        $expiredCountFinal = $this->countExpiredRecords();
        $failCountFinal = $this->countFailedRecords();

        $this->assertEquals(0, $result, 'Exactly zero records transitioned');
        $this->assertEquals($expiredCountInitial, $expiredCountFinal, 'No change in Expired state counts occurred');
        $this->assertEquals($failCountInitial, $failCountFinal, 'No change in Failed state counts occurred');

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": NON transitioned Id=$maxFailed\n");
        }
    }

    public function testExpiredToAvailable()
    {
        $query = new QueryHandler();

        // initial counts
        $expiredCountInitial = $this->countExpiredRecords();
        $availCountInitial = $this->countAvailableRecords();

        // Find a record in Expired status to transition
        $maxExpired = $this->findExpiredRecord();
        $result = $query->submittedToAvailable($maxExpired);

        // final counts
        $expiredCountFinal = $this->countExpiredRecords();
        $availCountFinal = $this->countAvailableRecords();

        $this->assertEquals(0, $result, 'Exactly zero records transitioned');
        $this->assertEquals($expiredCountInitial, $expiredCountFinal, 'No change in Expired state counts occurred');
        $this->assertEquals($availCountInitial, $availCountFinal, 'No change in Available state counts occurred');

        // debug
        if (self::$debug)
        {
            print("\n".__FUNCTION__.": NON transitioned Id=$maxExpired\n");
        }
    }

    public function testFailedToAvailable()
    {
        $query = new QueryHandler();

        // initial counts
        $availCountInitial = $this->countAvailableRecords();
        $failCountInitial = $this->countFailedRecords();

        // Find a record in Failed status to transition
        $maxFailed = $this->findFailedRecord();
        $result = $query->submittedToAvailable($maxFailed);

        // final counts
        $availCountFinal = $this->countAvailableRecords();
        $failCountFinal = $this->countFailedRecords();

        $this->assertEquals(0, $result, 'Exactly zero records transitioned');
        $this->assertEquals($availCountInitial, $availCountFinal, 'No change in Available state counts occurred');
        $this->assertEquals($failCountInitial, $failCountFinal, 'No change in Failed state counts occurred');

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

        $this->assertEquals($expectedKeys, array_keys($actual[0]), 'the expected fields are returned from the query');
        $this->assertEquals($this->countUserRequests(), count($actual), 'the expected number of records is returned from the query');
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
            'downloaded_datetime',
            'state'
        );

        // Requests via this user have been created as part of these tests
        $actual = $query->listUserRequestsByState($userId);

        $this->assertEquals($expectedKeys, array_keys($actual[0]), 'the expected fields are returned from the query');
        $this->assertEquals($this->countUserRequests(), count($actual), 'the expected number of records is returned from the query');
    }

    // Verify that user that did not create request cannot delete it
    public function testRecordDeleteIncorrectUser()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId();
        $wrongUserId = XDUser::getUserByUserName(self::CENTER_STAFF_USER_NAME)->getUserID();

        // Requests via $userId have been created as part of these tests
        $maxSubmitted = $this->findSubmittedRecord();

        // Provided that we have specified two different users here:
        if ($userId != $wrongUserId) {

            // try to delete the request:
            $actual = $query->deleteRequest($maxSubmitted, $wrongUserId);

            $this->assertEquals(0, $actual, 'the delete attempt affected 0 rows');

            if (self::$debug)
            {
                print("\n".__FUNCTION__.": deleted record id=".$maxSubmitted." ? $actual\n");
            }
        }
    }

    // Verify that user that created request can delete it
    public function testRecordDeleteCorrectUser()
    {
        $query = new QueryHandler();
        $userId = $this->acquireUserId();

        // Requests via $userId have been created as part of these tests
        $maxSubmitted = $this->findSubmittedRecord();

        // try to delete the request:
        $actual = $query->deleteRequest($maxSubmitted, $userId);

        $this->assertEquals(1, $actual, 'the delete affected 1 row');

        if (self::$debug)
        {
            print("\n".__FUNCTION__.": deleted record id=".$maxSubmitted." ? $actual\n");
        }
    }

    public static function setupBeforeClass(): void
    {
        // setup needed to use NORMAL_USER_USER_NAME or the like
        parent::setupBeforeClass();

        // determine initial max id to enable cleanup after testing
        static::$dbh = DB::factory('database');
        static::$maxId = static::$dbh->query('SELECT COALESCE(MAX(id), 0) AS id FROM batch_export_requests')[0]['id'];
    }

    public static function tearDownAfterClass(): void
    {
        // Reset the batch_export_requests database table to its initial contents
        static::$dbh->execute('DELETE FROM batch_export_requests WHERE id > :id', array('id' => static::$maxId));
    }
}

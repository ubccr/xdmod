<?php
/* ==========================================================================================
 *
 * Class governing database access by Data Warehouse Export batch script
 *
 * TODO: Notes and choices: evaluate these:
 *  Perhaps no: make this class a singleton--does it make sense?
 *      or do I need to have multiple instances--and who instantiates them?
 *      say I'm using it for a rest endpoint--what I do need to know?
 *  For database access and to issue queries:
 *      use CCR\DB namespace, which contains PDODB class that we want.
 *  deciding against e.g. making the state transitions reflected by classes. Too much.
 *  think about and safe access...
 *      do I need to do more to secure vars coming in
 *  determine current testing practices and write tests to them
 *      these need to be component tests
 *
 *  TODO, possibly: return list of ids for records that need to be marked 'export_expired'
 *
 *  timestamp and current datetime: always use the database's value
 *  ==========================================================================================
 */

namespace DataWarehouse\Export;

use Exception;
use CCR\DB;

class QueryHandler
{
    private $pdo; // populated in the constructor


    function __construct()
    {
        // Fetch the database handle
        $this->pdo = DB::factory('database');
    }

    /* transition between request record states */

    // create request record for specified export request
    public function createRequestRecord($userId, $realm, $startDate, $endDate)
    {
        $sql = "INSERT INTO batch_export_requests
                (requested_datetime, user_id, realm, start_date, end_date)
                VALUES
                (NOW(), :user_id, :realm, :start_date, :end_date)";

        $params = array('user_id' => $userId,
                        'realm' => $this->pdo->quote($realm),
                        'start_date' => $startDate,
                        'end_date' => $endDate);

        // return the id for the inserted record
        $id = $this->pdo->insert($sql, $params);

        return($id);
    }

    // transition specified export request to Failed state from Submitted.
    public function submittedToFailed($id)
    {
        $sql = "UPDATE batch_export_requests
                SET export_succeeded=0
                WHERE id=:id";

        $params = array('id' => $id);

        // Return count of affected rows--should be 1.
        $result = $this->pdo->execute($sql, $params);

        return(count($result)==1);
    }

    // transition specified export request to Available state from Submitted.
    // All time is current time as relative to database.
    public function submittedToAvailable($id)
    {
        // read export retention duration from config file. Value is stored in days.
        $expires_in_days = \xd_utilities\getConfiguration('data_warehouse_export','retention_duration');

        $sql = "UPDATE batch_export_requests
                SET
                    export_created_datetime=CAST(NOW() as DATETIME),
                    export_expires_datetime=DATE_ADD(CAST(NOW() as DATETIME), INTERVAL :expires_in_days DAY),
                    export_succeeded=1
                WHERE id=:id";

        $params = array('expires_in_days' => $expires_in_days,
                        'id' => $id);

        // Return count of affected rows--should be 1.
        $result = $this->pdo->execute($sql, $params);

        return(count($result)==1);
    }

    // transition specified export request to Expired state from Available.
    // Note that when the table grows large we don't want to do date comparisons...
    // Therefore we should create an export_expired flag on the db table.
    public function availableToExpired($id)
    {
        $sql = "UPDATE batch_export_requests
                SET export_expired=1
                WHERE id=:id";

        $params = array('id' => $id);

        // Return count of affected rows--should be 1.
        $result = $this->pdo->execute($sql, $params);

        return(count($result)==1);
    }

    /* list request records states */

    // Return count of export requests presently in Submitted state.
    public function countSubmittedRecords()
    {
        $sql = "SELECT COUNT(id)
            FROM batch_export_requests
            WHERE export_succeeded IS NULL";

        // Return count of rows.
        try {
            $result = $this->pdo->query($sql);
            return($result[0]['COUNT(id)']);

        } catch (Exception $e) {
            return $e;
        }
    }

    // Return details of all export requests presently in Submitted state.
    public function listSubmittedRecords()
    {
        $sql = "SELECT id, realm, start_date, end_date
            FROM batch_export_requests
            WHERE export_succeeded IS NULL";

        // Return query results.
        $result = $this->pdo->query($sql);

        // TODO: Logic to return neat table of records and states
        // (or in another function that handles that part?)
        return($result);
    }

    // Return details of all export requests made by specified user.
    public function listRequestsForUser($user_id)
    {
        $sql = "SELECT id, realm, start_date, end_date,
                export_succeeded,
                export_expired,
                export_expires_datetime,
                export_created_datetime
            FROM batch_export_requests
            WHERE user_id=:user_id
            ORDER BY id";

        $params = array('user_id' => $user_id);

        // Return query results.
        $result = $this->pdo->query($sql, $params);

        // TODO: Logic to return neat table of records and states
        // (or in another function that handles that part?)
        return $result;
    }
}


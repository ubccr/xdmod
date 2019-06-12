<?php
/* ==========================================================================================
 *
 * Class governing database access by Data Warehouse Export batch script
 *
 *  TODO, possibly: return list of ids for records that need to be marked 'export_expired'
 *
 *  timestamp and current datetime: always use the database's value
 *
 * ------------------------------------------------------------------------------------------
 *
 *  Recognized states enforced in this class:
 *
 *  State       export_succeeded    export_created_datetime     export_expired
 *  -----       ----------------    -----------------------     --------------
 *  Submitted   NULL                NULL                        FALSE
 *  Available   TRUE                NOT NULL                    FALSE
 *  Expired     TRUE                NOT NULL                    TRUE
 *  Failed      FALSE               NULL                        FALSE
 *  (Deleted)       not   present    in    database   or    filesystem
 *
 * ------------------------------------------------------------------------------------------
 *
 *  State transitions enforced in this class:
 *
 *    Submitted -> Available -> Expired
 *      |
 *      v
 *    Failed
 *
 *    ...any state can transition to Deleted.
 *
 *  ==========================================================================================
 */

namespace DataWarehouse\Export;

use Exception;
use CCR\DB;

class QueryHandler
{
    // database handle, populated in the constructor
    private $pdo;

    // Definition of Submitted state:
    private $whereSubmitted = "WHERE export_succeeded is NULL and export_created_datetime is NULL and export_expired = FALSE ";

    public function __construct()
    {
        // Fetch the database handle
        $this->pdo = DB::factory('database');
    }

    /* ******** Transition between request record states ******** */

    // Create request record for specified export request.
    // Result is a single request in Submitted state.
    public function createRequestRecord($userId, $realm, $startDate, $endDate, $format)
    {
        $sql = "INSERT INTO batch_export_requests
                (requested_datetime, user_id, realm, start_date, end_date, export_file_format)
                VALUES
                (NOW(), :user_id, :realm, :start_date, :end_date, :export_file_format)";

        $params = array('user_id' => $userId,
                        'realm' => $realm,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'export_file_format' => $format
                    );

        // return the id for the inserted record
        $id = $this->pdo->insert($sql, $params);
        return($id);
    }

    // Transition specified export request from Submitted state to Failed state.
    public function submittedToFailed($id)
    {
        $sql = "UPDATE batch_export_requests
                SET export_succeeded=0 " .
                $this->whereSubmitted .
                "AND id=:id";

        $params = array('id' => $id);

        // Return count of affected rows--should be 1 if successful.
        $result = $this->pdo->execute($sql, $params);
        return($result);
    }

    // Transition specified export request from Submitted state to Available state.
    // All time is current time as relative to database.
    public function submittedToAvailable($id)
    {
        // read export retention duration from config file. Value is stored in days.
        $expires_in_days = \xd_utilities\getConfiguration('data_warehouse_export', 'retention_duration_days');

        $sql = "UPDATE batch_export_requests
                SET export_created_datetime=CAST(NOW() as DATETIME),
                    export_expires_datetime=DATE_ADD(CAST(NOW() as DATETIME), INTERVAL :expires_in_days DAY),
                    export_succeeded=1 " .
                $this->whereSubmitted . "AND id=:id";

        $params = array('expires_in_days' => $expires_in_days,
                        'id' => $id);

        // Return count of affected rows--should be 1 if successful.
        $result = $this->pdo->execute($sql, $params);
        return($result);
    }

    // Transition specified export request from Available state to Expired state.
    public function availableToExpired($id)
    {
        $sql = "UPDATE batch_export_requests
                SET export_expired=1
                WHERE id=:id AND
                export_succeeded = TRUE AND export_created_datetime IS NOT NULL
                AND export_expired = FALSE";

        $params = array('id' => $id);

        // Return count of affected rows--should be 1 if successful.
        $result = $this->pdo->execute($sql, $params);
        return($result);
    }

    /* ******** List request records and states ******** */

    // Return count of all export requests presently in Submitted state.
    public function countSubmittedRecords()
    {
        $sql = "SELECT COUNT(id) FROM batch_export_requests " . $this->whereSubmitted;

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
        $sql = "SELECT id, realm, start_date, end_date, export_file_format, requested_datetime
            FROM batch_export_requests " . $this->whereSubmitted;

        // Return query results.
        $result = $this->pdo->query($sql);
        return($result);
    }

    // Return details of export requests made by specified user.
    public function listRequestsForUser($user_id)
    {
        $sql = "SELECT id,
                realm,
                start_date,
                end_date,
                export_succeeded,
                export_expired,
                export_expires_datetime,
                export_created_datetime,
                export_file_format,
                requested_datetime
            FROM batch_export_requests
            WHERE user_id=:user_id
            ORDER BY id";

        $params = array('user_id' => $user_id);

        // Return query results.
        $result = $this->pdo->query($sql, $params);
        return($result);
    }

    // Return details (including state) of export requests made by specified user.
    public function listUserRequestsByState($user_id)
    {
        $attributes = "SELECT id, realm, start_date, end_date, export_succeeded, export_expired, export_expires_datetime, export_created_datetime, export_file_format, requested_datetime, ";
        $fromTable = "FROM batch_export_requests ";
        $whereAvailable = "WHERE export_succeeded = TRUE and export_created_datetime is NOT NULL and export_expired = FALSE ";
        $whereExpired = "WHERE export_succeeded = TRUE and export_created_datetime is NOT NULL and export_expired = TRUE ";
        $whereFailed = "WHERE export_succeeded = FALSE and export_created_datetime is NULL and export_expired = FALSE ";
        $userClause = "AND user_id = :user_id ";

        $sql =  $attributes . "'Submitted' as state " . $fromTable . $this->whereSubmitted . $userClause . "UNION " .
                $attributes . "'Available' as state " . $fromTable . $whereAvailable . $userClause . "UNION " .
                $attributes . "'Expired' as state "   . $fromTable . $whereExpired . $userClause . "UNION " .
                $attributes . "'Failed' as state "    . $fromTable . $whereFailed . $userClause . "ORDER BY requested_datetime";

        $params = array('user_id' => $user_id);

        // Return query results.
        $result = $this->pdo->query($sql, $params);
        return($result);
    }

    /* ******** Delete single user-submitted request record ******** */

    // Delete specified record from the database, regardless of its state.
    // Only the user who submitted the request may delete it.
    public function deleteRequest($id, $user)
    {
        // delete record, providing that requesting user owns specified record
        $sql = "DELETE FROM batch_export_requests WHERE id=:request_id AND user_id=:user_id";
        $params = array('request_id' => $id,
                        'user_id' => $user
                    );

        // Return count of deleted rows--should be 1 if successful.
        $result = $this->pdo->execute($sql, $params);
        return($result);
    }
}

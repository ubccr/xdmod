<?php
/**
 *
 * Class governing database access by Data Warehouse Export batch script
 *
 *  timestamp and current datetime: always use the database's value
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
 *  State transitions enforced in this class:
 *
 *    Submitted -> Available -> Expired
 *      |
 *      v
 *    Failed
 *
 *    ...any state can transition to Deleted.
 */

namespace DataWarehouse\Export;

use Exception;
use CCR\DB;

class QueryHandler
{
    /**
     * Database handle.
     * @var \CCR\DB\iDatabase
     */
    private $dbh;

    /**
     * Definition of Submitted state.
     * @var string
     */
    private $whereSubmitted = "WHERE export_succeeded IS NULL AND export_created_datetime IS NULL AND export_expired = 0 ";

    /**
     * Definition of Expired state.
     * @var string
     */
    private $whereExpired = "WHERE export_succeeded = TRUE AND export_created_datetime IS NOT NULL AND export_expired = 1 ";

    /**
     * Definition of Available state.
     * @var string
     */
    private $whereAvailable = "WHERE export_succeeded = 1 AND export_created_datetime IS NOT NULL AND export_expired = 0 ";

    /**
     * Definition of Failed state.
     * @var string
     */
    private $whereFailed = "WHERE export_succeeded = 0 AND export_created_datetime IS NULL AND export_expired = 0 ";

    public function __construct()
    {
        $this->dbh = DB::factory('database');
    }

    /**
     * Create request record for specified export request.
     *
     * @param integer $userId
     * @param string $realm Realm unique identifier.
     * @param string $startDate Start date formatted as YYYY-MM-DD.
     * @param string $endDate End date formatted as YYYY-MM-DD.
     * @param string $format Export format (CSV or JSON).
     * @return integer The id for the inserted record.
     */
    public function createRequestRecord(
        $userId,
        $realm,
        $startDate,
        $endDate,
        $format
    ) {
        $sql = "INSERT INTO batch_export_requests
                (requested_datetime, user_id, realm, start_date, end_date, export_file_format)
                VALUES
                (NOW(), :user_id, :realm, :start_date, :end_date, :export_file_format)";

        $params = array(
            'user_id' => $userId,
            'realm' => $realm,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'export_file_format' => $format
        );

        return $this->dbh->insert($sql, $params);
    }

    /**
     * Transition specified export request from Submitted state to Failed state.
     *
     * @param integer $id Export request primary key.
     * @return integer Count of affected rows--should be 1 if successful.
     */
    public function submittedToFailed($id)
    {
        $sql = "UPDATE batch_export_requests
                SET export_succeeded = 0 " .
                $this->whereSubmitted .
                "AND id = :id";
        return $this->dbh->execute($sql, array('id' => $id));
    }

    /**
     * Transition specified export request from Submitted state to Available state.
     *
     * @param integer $id Export request primary key.
     * @return integer Count of affected rows--should be 1 if successful.
     */
    public function submittedToAvailable($id)
    {
        // read export retention duration from config file. Value is stored in days.
        $expires_in_days = \xd_utilities\getConfiguration('data_warehouse_export', 'retention_duration_days');

        $sql = "UPDATE batch_export_requests
                SET export_created_datetime = NOW(),
                    export_expires_datetime = DATE_ADD(NOW(), INTERVAL :expires_in_days DAY),
                    export_succeeded = 1 " .
                $this->whereSubmitted . "AND id = :id";

        $params = array(
            'expires_in_days' => $expires_in_days,
            'id' => $id
        );

        return $this->dbh->execute($sql, $params);
    }

    /**
     * Transition specified export request from Available state to Expired state.
     *
     * @param integer $userId
     * @return integer Count of affected rows--should be 1 if successful.
     */
    public function availableToExpired($id)
    {
        $sql = "UPDATE batch_export_requests SET export_expired = 1 " .
                $this->whereAvailable . 'AND id = :id';
        return $this->dbh->execute($sql, array('id' => $id));
    }

    /**
     * Return count of all export requests presently in Submitted state.
     *
     * @return integer Count of rows.
     */
    public function countSubmittedRecords()
    {
        $sql = "SELECT COUNT(id) AS row_count FROM batch_export_requests " . $this->whereSubmitted;
        $result = $this->dbh->query($sql);
        return $result[0]['row_count'];
    }

    /**
     * Return details of all export requests presently in Submitted state.
     *
     * @return array
     */
    public function listSubmittedRecords()
    {
        $sql = "SELECT id, realm, start_date, end_date, export_file_format, requested_datetime
            FROM batch_export_requests " . $this->whereSubmitted . ' ORDER BY requested_datetime, id';
        return $this->dbh->query($sql);
    }

    /**
     * Return export requests in Available state that should expire.
     *
     * @return array
     */
    public function listExpiringRecords()
    {
        $sql = 'SELECT id,
                realm,
                start_date,
                end_date,
                export_succeeded,
                export_expired,
                export_expires_datetime,
                export_created_datetime,
                export_file_format,
                requested_datetime
            FROM batch_export_requests ' . $this->whereAvailable . ' AND export_expires_datetime > NOW()
            ORDER BY requested_datetime, id';
        return $this->dbh->query($sql);
    }

    /**
     * Return details of export requests made by specified user.
     *
     * @param integer $userId
     * @return array All of the user's export requests.
     */
    public function listRequestsForUser($userId)
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
            WHERE user_id = :user_id
            ORDER BY requested_datetime, id";
        return $this->dbh->query($sql, array('user_id' => $userId));
    }

    /**
     * Return details (including state) of export requests made by specified user.
     *
     * @param integer $userId
     * @return array All of the user's export requests (including state field).
     */
    public function listUserRequestsByState($userId)
    {
        $attributes = "SELECT id,
                       realm,
                       start_date,
                       end_date,
                       export_succeeded,
                       export_expired,
                       export_expires_datetime,
                       export_created_datetime,
                       export_file_format,
                       requested_datetime,
                       ";
        $fromTable = "FROM batch_export_requests ";
        $userClause = "AND user_id = :user_id ";

        $sql = $attributes . "'Submitted' AS state " . $fromTable . $this->whereSubmitted . $userClause . "UNION " .
               $attributes . "'Available' AS state " . $fromTable . $this->whereAvailable . $userClause . "UNION " .
               $attributes . "'Expired' AS state "   . $fromTable . $this->whereExpired . $userClause . "UNION " .
               $attributes . "'Failed' AS state "    . $fromTable . $this->whereFailed . $userClause . "ORDER BY requested_datetime, id";

        return $this->dbh->query($sql, array('user_id' => $userId));
    }

    /**
     * Delete specified record from the database, regardless of its state.
     *
     * Only the user who submitted the request may delete it.
     *
     * @param integer $id Export request primary key.
     * @param integer $userId
     * @return integer Count of deleted rows--should be 1 if successful.
     */
    public function deleteRequest($id, $userId)
    {
        $sql = "DELETE FROM batch_export_requests WHERE id = :request_id AND user_id = :user_id";
        return $this->dbh->execute($sql, array('request_id' => $id, 'user_id' => $userId));
    }
}

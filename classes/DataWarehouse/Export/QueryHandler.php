<?php
/* ==========================================================================================
 *
 * Class governing database access by Data Warehouse Export batch script
 *
 * TODO: Notes and choices: evaluate these:
 *  make this class a singleton--does it make sense?
 *      or do I need to have multiple instances--and who instantiates them?
 *      say I'm using it for a rest endpoint--what I do need to know?
 *  use the DataWarehouse.php (DataWarehouse class) to issue queries?
 *      No, use CCR\DB itself, which will give us PDODB.
 *      (note that it's looking rather long in the tooth. There may be a more modern way.)
 *  deciding against e.g. making the state transitions reflected by classes. Too much.
 *  think about binding variables and safe access...
 *  determine current testing practices and write tests to them
 *      these need to be component tests
 *
 *  timestamp and current datetime: always use the database's value
 *  ==========================================================================================
 */

namespace DataWarehouse\Export;

use Exception;
use CCR\DB;

class QueryHandler
{
    // Fetch the database handle
    $pdo = DB::factory('database');


    // TODO verify: singleton class should be appropriate here
    private function __construct()
    {
    }

    /* transition between request record states */

    // create request record for specified export request
    public static function createRequestRecord($realm, $startDate, $endDate)
    {
        // TODO
        $sql = "INSERT INTO batch_export_requests
                (requested_datetime, user_id, realm, start_date, end_date)
                VALUES
                ()";

        // TODO: return the id
    }

    // transition specified export request to Failed state from Submitted.
    public static function submittedToFailed($id)
    {
        $sql = "UPDATE batch_export_requests
                SET export_succeeded=0
                WHERE id=$id";
        // TODO

    }

    // transition specified export request to Available state from Submitted.
    public static function submittedToAvailable($id)
    {
        // read export retention duration from config file
        $expires_in_days = xd_utilities\getConfiguration('data_warehouse_export','retention_duration');

        // Nah, don't:
        // either here, or below...
        //$sqlDate = "SELECT CAST(NOW() as DATETIME";
        // select it
        //$currentDatetime =

        $sql = "UPDATE batch_export_requests
                SET
                    export_created=CAST(NOW() as DATETIME),
                    export_expires=DATE_ADD(CAST(NOW() as DATETIME), INTERVAL $expires_in_days DAY),
                    export_succeeded=1
                WHERE id=$id";
        // TODO

    }

    // transition specified export request to Expired state from Available.
    // Note that when the table grows large we don't want to do date comparisons...
    public static function availableToExpired($id)
    {
        // TODO: create the field in the DB.
        // no-op
        // TODO: really?
        $sql = "UPDATE batch_export_requests
                SET export_expired=1
                WHERE id=$id";
    }

    /* list request records states */

    // Return count of export requests presently in Submitted state.
    public static function countSubmittedRecords()
    {
        $sql = "SELECT COUNT(id)
            FROM batch_export_requests
            WHERE export_succeeded IS NULL";
        // TODO, you meant the PDODB execute() function here. Decide.
        $count = \DataWarehouse::connect()->execute($sql);

        return $count;
    }

    // Return details of all export requests presently in Submitted state.
    public static function listSubmittedRecords()
    {

        $sql = "SELECT id, realm, start_date, end_date
            FROM batch_export_requests
            WHERE export_succeeded IS NULL";
        $results = \DataWarehouse::connect()->query($sql);

        return $results;
    }

    // Return details of all export requests made by specified user.
    public static function listRequestsForUser($user_id)
    {
        // TODO: bind your user_id as appropriate please.
        $sql = "SELECT id, realm, start_date, end_date,
            export_succeeded, expires_datetime,
            export_created_datetime,
            FROM batch_export_requests
            WHERE user_id=".$user_id.
            "ORDER BY id";
        $results = \DataWarehouse::connect()->query($sql);

        // TODO: Logic to return neat table of records and states
        // (or in another function that handles that part?)
        return $results;
    }
}


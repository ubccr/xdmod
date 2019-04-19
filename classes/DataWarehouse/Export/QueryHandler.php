<?php
/* ==========================================================================================
 *
 * Class governing database access by Data Warehouse Export batch script
 *
 * TODO: Notes and choices: evaluate these:
 *  make this class a singleton--does it make sense?
 *  use the DataWarehouse.php (DataWarehouse class) to issue queries?
 *      note that it's looking rather long in the tooth. There may be a more modern way.
 *  deciding against e.g. making the state transitions reflected by classes. Too much.
 *  think about binding variables and safe access...
 * ==========================================================================================
 */

namespace DataWarehouse\Export;

use Exception;
use DataWarehouse; // TODO: DB access here! or??

class QueryHandler
{

    // singleton class should be appropriate here
    private function __construct()
    {
    }

    /* transition between request record states */

    public static function createRequestRecord(realm, startDate, endDate)
    {
        // TODO
    }

    // transition specified export request to Failed state from Submitted.
    public static function submittedToFailed($id)
    {
        $sql = "UPDATE batch_export_requests SET export_succeeded=0
                WHERE id=$id";
        // TODO

    }

    // transition specified export request to Available state from Submitted.
    public static function submittedToAvailable()
    {
        // TODO

    }

    // transition specified export request to Expired state from Available.
    public static function availableToExpired()
    {
        // no-op
        // TODO: really?
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


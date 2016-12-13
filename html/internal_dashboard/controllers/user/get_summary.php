<?php
/**
 * Return user summary data.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

use CCR\DB;

try {

    $pdo = DB::factory('database');

    $sql = 'SELECT COUNT(*) AS count FROM moddb.Users';
    list($userCountRow) = $pdo->query($sql);

    // TODO: Refactor these queries.
    $sql = '
        SELECT COUNT(DISTINCT user_id) AS count
        FROM moddb.SessionManager
        WHERE DATEDIFF(NOW(), FROM_UNIXTIME(init_time)) < 7
    ';
    list($last7DaysRow) = $pdo->query($sql);

    $sql = '
        SELECT COUNT(DISTINCT user_id) AS count
        FROM moddb.SessionManager
        WHERE DATEDIFF(NOW(), FROM_UNIXTIME(init_time)) < 30
    ';
    list($last30DaysRow) = $pdo->query($sql);

    $returnData = array(
        'success' => true,
        'response' => array(
            array(
                'user_count'             => $userCountRow['count'],
                'logged_in_last_7_days'  => $last7DaysRow['count'],
                'logged_in_last_30_days' => $last30DaysRow['count'],
            )
        ),
        'count' => 1,
    );

} catch (Exception $e) {
    $returnData = array(
        'success' => false,
        'message' => $e->getMessage(),
    );
}

echo json_encode($returnData);


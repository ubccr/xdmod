<?php
/**
 * Return log message data.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

use CCR\DB;

try {

    $pdo = DB::factory('logger');

    $sql = '
        SELECT id, logtime, ident, priority, message
        FROM log_table
    ';

    $clauses = array();
    $params = array();

    if (isset($_REQUEST['ident'])) {
        $clauses[] = 'ident = ?';
        $params[]  = $_REQUEST['ident'];
    }

    if (isset($_REQUEST['logLevels']) && is_array($_REQUEST['logLevels'])) {
        $clauses[] = 'priority IN (' . implode(',',
            array_pad(array(), count($_REQUEST['logLevels']), '?')) . ')';
        $params = array_merge($params, $_REQUEST['logLevels']);
    }

    if (isset($_REQUEST['only_most_recent']) && $_REQUEST['only_most_recent']) {
        if (!isset($_REQUEST['ident'])) {
            throw new Exception('"ident" required');
        }

        $summary = Log\Summary::factory($_REQUEST['ident']);

        if (null !== ($startRowId = $summary->getProcessStartRowId())) {
            $clauses[] = 'id >= ?';
            $params[]  = $startRowId;
        }

        if (null !== ($endRowId = $summary->getProcessEndRowId())) {
            $clauses[] = 'id <= ?';
            $params[]  = $endRowId;
        }
    } else {
        if (isset($_REQUEST['start_date'])) {
            $clauses[] = 'logtime >= ?';
            $params[] = $_REQUEST['start_date'] . ' 00:00:00';
        }

        if (isset($_REQUEST['end_date'])) {
            $clauses[] = 'logtime <= ?';
            $params[] = $_REQUEST['end_date'] . ' 23:59:59';
        }
    }

    if (count($clauses)) {
        $sql .= ' WHERE ' . implode(' AND ', $clauses);
    }

    $sql .= ' ORDER BY id DESC';

    if (isset($_REQUEST['start']) && isset($_REQUEST['limit'])) {
        $sql .= sprintf(
            ' LIMIT %d, %d',
            $_REQUEST['start'],
            $_REQUEST['limit']
        );
    }

    $returnData = array(
        'success'  => true,
        'response' => $pdo->query($sql, $params),
    );

    $sql = 'SELECT COUNT(*) AS count FROM log_table';

    if (count($clauses)) {
        $sql .= ' WHERE ' . implode(' AND ', $clauses);
    }

    list($countRow) = $pdo->query($sql, $params);

    $returnData['count'] = $countRow['count'];

} catch (Exception $e) {
    $returnData = array(
        'success' => false,
        'message' => $e->getMessage(),
    );
}

echo json_encode($returnData);


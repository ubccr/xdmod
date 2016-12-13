<?php
/**
 * Return log summary data.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

try {

    $summary = Log\Summary::factory($_REQUEST['ident']);

    $returnData = array(
        'success'  => true,
        'response' => array($summary->getData()),
        'count'    => 1,
    );

} catch (Exception $e) {
    $returnData = array(
        'success' => false,
        'message' => $e->getMessage(),
    );
}

echo json_encode($returnData);


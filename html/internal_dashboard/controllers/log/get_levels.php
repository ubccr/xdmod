<?php
/**
 * Return PEAR Log levels.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

try {
    require_once 'Log.php';

    $returnData = array(
        'success' => true,
        'response' => array(
            array('id' => PEAR_LOG_EMERG,   'name' => 'Emergency'),
            array('id' => PEAR_LOG_ALERT,   'name' => 'Alert'),
            array('id' => PEAR_LOG_CRIT,    'name' => 'Critical'),
            array('id' => PEAR_LOG_ERR,     'name' => 'Error'),
            array('id' => PEAR_LOG_WARNING, 'name' => 'Warning'),
            array('id' => PEAR_LOG_NOTICE,  'name' => 'Notice'),
            array('id' => PEAR_LOG_INFO,    'name' => 'Info'),
            array('id' => PEAR_LOG_DEBUG,   'name' => 'Debug'),
        ),
    );

    $returnData['count'] = count($returnData['response']);
} catch (Exception $e) {
    $returnData = array(
        'success' => false,
        'message' => $e->getMessage(),
    );
}

echo json_encode($returnData);

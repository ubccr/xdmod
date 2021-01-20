<?php
/**
 * Return Monolog Logger levels.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

try {

    $returnData = array(
        'success' => true,
        'response' => array(
            array('id' => \CCR\Log::EMERG,   'name' => 'Emergency'),
            array('id' => \CCR\Log::ALERT,   'name' => 'Alert'),
            array('id' => \CCR\Log::CRIT,    'name' => 'Critical'),
            array('id' => \CCR\Log::ERR,     'name' => 'Error'),
            array('id' => \CCR\Log::WARNING, 'name' => 'Warning'),
            array('id' => \CCR\Log::NOTICE,  'name' => 'Notice'),
            array('id' => \CCR\Log::INFO,    'name' => 'Info'),
            array('id' => \CCR\Log::DEBUG,   'name' => 'Debug'),
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


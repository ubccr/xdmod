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
            array('id' => \Monolog\Logger::EMERGENCY, 'name' => 'Emergency'),
            array('id' => \Monolog\Logger::ALERT,     'name' => 'Alert'),
            array('id' => \Monolog\Logger::CRITICAL,  'name' => 'Critical'),
            array('id' => \Monolog\Logger::ERROR,     'name' => 'Error'),
            array('id' => \Monolog\Logger::WARNING,   'name' => 'Warning'),
            array('id' => \Monolog\Logger::NOTICE,    'name' => 'Notice'),
            array('id' => \Monolog\Logger::INFO,      'name' => 'Info'),
            array('id' => \Monolog\Logger::DEBUG,     'name' => 'Debug'),
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


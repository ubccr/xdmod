<?php
/**
 * Return Monolog Logger levels.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

try {

    $returnData = ['success' => true, 'response' => [['id' => \CCR\Log::EMERG, 'name' => 'Emergency'], ['id' => \CCR\Log::ALERT, 'name' => 'Alert'], ['id' => \CCR\Log::CRIT, 'name' => 'Critical'], ['id' => \CCR\Log::ERR, 'name' => 'Error'], ['id' => \CCR\Log::WARNING, 'name' => 'Warning'], ['id' => \CCR\Log::NOTICE, 'name' => 'Notice'], ['id' => \CCR\Log::INFO, 'name' => 'Info'], ['id' => \CCR\Log::DEBUG, 'name' => 'Debug']]];

    $returnData['count'] = count($returnData['response']);

} catch (Exception $e) {
    $returnData = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($returnData);


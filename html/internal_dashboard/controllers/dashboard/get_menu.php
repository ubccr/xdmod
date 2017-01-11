<?php
/**
 * Return dashboard menu items.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

use Xdmod\Config;

try {
    $config = Config::factory();

    $returnData = array(
        'success' => true,
        'response' => $config['internal_dashboard']['menu'],
    );

    $returnData['count'] = count($returnData['response']);
} catch (Exception $e) {
    $returnData = array(
        'success' => false,
        'message' => $e->getMessage(),
    );
}

echo json_encode($returnData);

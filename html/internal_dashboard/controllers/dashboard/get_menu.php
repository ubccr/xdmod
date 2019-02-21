<?php
/**
 * Return dashboard menu items.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

try {
    $configFile = new \Configuration\XdmodConfiguration(
        'internal_dashboard.json',
        CONFIG_DIR
    );
    $configFile->initialize();

    $config = $configFile->toAssocArray();

    $returnData = array(
        'success' => true,
        'response' => $config['menu'],
    );

    $returnData['count'] = count($returnData['response']);

} catch (Exception $e) {
    $returnData = array(
        'success' => false,
        'message' => $e->getMessage(),
    );
}

echo json_encode($returnData);


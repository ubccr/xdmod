<?php
/**
 * Return dashboard menu items.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

try {
    $config = \Configuration\XdmodConfiguration::assocArrayFactory(
        'internal_dashboard.json',
        CONFIG_DIR
    );

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


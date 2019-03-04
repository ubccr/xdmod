<?php
/**
 * Return summary confifuration.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

use Log\Summary;

try {
    $config = \Configuration\XdmodConfiguration::assocArrayFactory(
        'internal_dashboard.json',
        CONFIG_DIR
    );

    $portlets = array();

    foreach ($config['portlets'] as $portlet) {

        // Add an empty config if none is found.
        if (!isset($portlet['config'])) {
            $portlet['config'] = array();
        }

        $portlets[] = $portlet;
    }

    // Add log portlets.
    foreach ($config['logs'] as $log) {
      $logSummary = Summary::factory($log['ident'], TRUE);

        if ($logSummary->getProcessStartRowId() === null) { continue; }

        $portlets[] = array(
            'class'  => 'XDMoD.Log.SummaryPortlet',
            'config' => array(
                'ident' => $log['ident'],
                'title' => $log['title'],
                'linkPath' => array(
                    'log-tab-panel',
                    $log['ident'] . '-log-panel',
                ),
            ),
        );
    }

    $returnData = array(
        'success'  => true,
        'response' => $portlets,
    );

    $returnData['count'] = count($returnData['response']);

} catch (Exception $e) {
    $returnData = array(
        'success' => false,
        'message' => $e->getMessage(),
    );
}

echo json_encode($returnData);


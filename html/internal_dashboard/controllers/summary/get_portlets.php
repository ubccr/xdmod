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

    $portlets = [];

    foreach ($config['portlets'] as $portlet) {

        // Add an empty config if none is found.
        if (!isset($portlet['config'])) {
            $portlet['config'] = [];
        }

        $portlets[] = $portlet;
    }

    // Add log portlets.
    foreach ($config['logs'] as $log) {
      $logSummary = Summary::factory($log['ident'], TRUE);

        if ($logSummary->getProcessStartRowId() === null) { continue; }

        $portlets[] = ['class'  => 'XDMoD.Log.SummaryPortlet', 'config' => ['ident' => $log['ident'], 'title' => $log['title'], 'linkPath' => ['log-tab-panel', $log['ident'] . '-log-panel']]];
    }

    $returnData = ['success'  => true, 'response' => $portlets];

    $returnData['count'] = count($returnData['response']);

} catch (Exception $e) {
    $returnData = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($returnData);


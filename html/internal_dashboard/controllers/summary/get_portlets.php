<?php
/**
 * Return summary confifuration.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 */

use Xdmod\Config;
use Log\Summary;

try {
    $config = Config::factory();

    $portlets = array();

    foreach ($config['internal_dashboard']['portlets'] as $portlet) {
        // Add an empty config if none is found.
        if (!isset($portlet['config'])) {
            $portlet['config'] = array();
        }

        $portlets[] = $portlet;
    }

    // Add log portlets.
    foreach ($config['internal_dashboard']['logs'] as $log) {
        $logSummary = Summary::factory($log['ident'], true);

        if ($logSummary->getProcessStartRowId() === null) {
            continue;
        }

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

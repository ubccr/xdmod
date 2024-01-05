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

    $summaries = [];

    foreach ($config['summary'] as $summary) {

        // Add an empty config if none is found.
        if (!isset($summary['config'])) {
            $summary['config'] = [];
        }

        // Add log config.
        if ($summary['class'] === 'XDMoD.Log.TabPanel') {
            $logList = [];

            foreach ($config['logs'] as $log) {
                $logSummary = Summary::factory($log['ident']);

                if ($logSummary->getProcessStartRowId() === null) {
                    continue;
                }

                $logList[] = ['id'    => $log['ident'] . '-log-panel', 'ident' => $log['ident'], 'title' => $log['title']];
            }

            $summary['config']['logConfigList'] = $logList;
        }

        $summaries[] = $summary;
    }

    $returnData = ['success'  => true, 'response' => $summaries];

    $returnData['count'] = count($returnData['response']);

} catch (Exception $e) {
    $returnData = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($returnData);


<?php

namespace OpenXdmod\Migration\Version800To810;

use FilterListBuilder;
use OpenXdmod\Setup\Console;

/**
* Migrate databases from version 8.0.0 to 8.1.0.
*/
class DatabasesMigration extends \OpenXdmod\Migration\DatabasesMigration
{
    /**
     * @see \OpenXdmod\Migration\Migration::execute
     **/
    public function execute()
    {
        parent::execute();

        $console = Console::factory();
        $console->displayMessage(<<<"EOT"
There have been updates to cloud aggregation statistics to make the data more accurate.
IF you have the Cloud realm enabled it is recommended that you re-ingest and aggregate your cloud data.
If you have a large amount of cloud data this may take multiple hours.
EOT
        );
        $runaggregation = $console->promptBool(
            'Do you want to run cloud aggregation now?',
            false
        );

        if (true === $runaggregation) {

            // Events that are now being used were previously discarded during ingestion
            // and need to be re-ingested from the log files in order have accurate cloud
            // statistics.
            $cloudLogDirectory = '';
            $cloudLogFormat = '';

            $ingestPipeline = array();
            $extractPipeline = array();

            $cloudLogFormatEntryAttempts = 0;
            $cloudLogDirectoryEntryAttempts = 0;

            while(!is_dir($cloudLogDirectory)){
                $cloudLogDirectoryPromptMessage = ($cloudLogDirectoryEntryAttempts === 0) ?
                    'Please enter the directory of your cloud logs to use for re-ingestion and aggregation of the Cloud realm.' :
                    'The directory you specified does not seem to exist. Please enter the directory of your cloud logs to use for re-ingestion and aggregation of the Cloud realm.';

                $cloudLogDirectory = $console->prompt(
                    $cloudLogDirectoryPromptMessage,
                    false
                );
                $cloudLogDirectoryEntryAttempts++;
            }

            $cloudResourceName = $console->prompt(
                'Please enter the name of your cloud resource as shown in XDMOD.',
                false
            );

            while(!in_array($cloudLogFormat, ['openstack', 'generic'])){
                $cloudLogFormatPromptMessage = ($cloudLogFormatEntryAttempts === 0) ?
                    'Please specify the format of your cloud logs. If your cloud logs come from OpenStack, enter openstack; otherwise enter generic.' :
                    'You specified an invalid cloud log format. If your cloud logs come from OpenStack, enter openstack; otherwise enter generic.';

                $cloudLogFormat = $console->prompt(
                    $cloudLogFormatPromptMessage,
                    false
                );
                $cloudLogFormatEntryAttempts++;
            }

            if($cloudLogFormat == 'openstack'){
                $ingestPipeline = array('jobs-cloud-ingest-openstack');
                $extractPipeline = array('jobs-cloud-extract-openstack');
            }
            else if($cloudLogFormat == 'generic'){
                $ingestPipeline = array('jobs-cloud-ingest-openstack');
                $extractPipeline = array('jobs-cloud-extract-openstack');
            }

            $this->runEtl(array('process-sections' => array('jobs-common','jobs-cloud-common','ingest-resources')));

            $this->runEtl(
                array(
                    'process-sections' => $ingestPipeline,
                    'include-only-resource-codes' => $cloudResourceName,
                    'variable-overrides' => ['CLOUD_EVENT_LOG_DIRECTORY' => $cloudLogDirectory]
                )
            );

            $this->runEtl(array('process-sections' => $extractPipeline));
            $this->runEtl(array('process-sections' => array('cloud-state-pipeline')));

            $this->logger->notice('Rebuilding filter lists');
            try {
                $builder = new FilterListBuilder();
                $builder->setLogger($this->logger);
                $builder->buildRealmLists('Cloud');
            } catch (Exception $e) {
                $this->logger->notice('Failed BuildAllLists: '  . $e->getMessage());
                $this->logger->crit(array(
                    'message'    => 'Filter list building failed: ' . $e->getMessage(),
                    'stacktrace' => $e->getTraceAsString(),
                ));
                throw new \Exception('Filter list building failed: ' . $e->getMessage());
            }
            $this->logger->notice('Done building filter lists');
        }
        else {
            $console->displayMessage(<<<"EOT"
Re-ingestion and aggregation for the Cloud realm not run.  To do this yourself please consult the XDMOD documentation
at https://open.xdmod.org/8.0/cloud.html
EOT
            );
        }
    }

    private function runEtl($scriptOptions = array()){
        if(count($scriptOptions) < 0 || (!array_key_exists('process-sections', $scriptOptions) && !array_key_exists('actions', $scriptOptions))){
            throw new \Exception('ETL Pipeline / actions not given.');
        }

        $scriptOptions['default-module-name'] = 'xdmod';
        $configOptions = array('default_module_name' => $scriptOptions['default-module-name']);

        if( array_key_exists('variable-overrides', $scriptOptions) ){
             $configOptions['config_variables'] = $scriptOptions['variable-overrides'];
             unset($scriptOptions['variable_overrides']);
        }

        $etlConfig = new \ETL\Configuration\EtlConfiguration(
            CONFIG_DIR . '/etl/etl.json',
            null,
            $this->logger,
            $configOptions
        );

        $etlConfig->initialize();
        \ETL\Utilities::setEtlConfig($etlConfig);
        $overseerOptions = new \ETL\EtlOverseerOptions($scriptOptions, $this->logger);
        $utilitySchema = $etlConfig->getGlobalEndpoint('utility')->getSchema();
        $overseerOptions->setResourceCodeToIdMapSql(sprintf("SELECT id, code from %s.resourcefact", $utilitySchema));
        $overseer = new \ETL\EtlOverseer($overseerOptions, $this->logger);
        $overseer->execute($etlConfig);
    }
}

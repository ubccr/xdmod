<?php
/**
 * Generic cloud event data shredder.
 *
 * @author Greg Dean <gmdean@ccr.buffalo.edu>
 */

namespace OpenXdmod\Shredder;

use Exception;
use CCR\DB\iDatabase;
use OpenXdmod\Shredder;
use ETL\EtlOverseer;
use ETL\iEtlOverseer;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use ETL\Utilities;

class Genericcloud extends Shredder
{

    /**
     * Time zone used when parsing datetimes.
     *
     * @var DateTimeZone
     */
    protected $timeZone;

    /**
     * @inheritdoc
     */
    public function __construct(iDatabase $db)
    {
        $this->db     = $db;
        $this->logger = \Log::singleton('null');

        $classPath = explode('\\', strtolower(get_class($this)));
        $this->format = $classPath[count($classPath) - 1];
    }

    /**
     * @inheritdoc
     */
    public function shredFile($line)
    {
        throw new Exception('The OpenStack shredder does not supported shredding by file. Please use the d option and specify a directory');

    }

    protected function runEtlPipeline(array $pipelines, array $params=array())
    {
        $this->logger->debug(
            sprintf(
                'Shredding directory using ETL pipeline "%s" with parameters %s',
                implode(', ', $pipelines),
                json_encode($params)
            )
        );

        $configOptions = array('default_module_name' => 'xdmod');
        if( array_key_exists('variable_overrides', $params) ){
            $configOptions['config_variables'] = $params['variable_overrides'];
        }

        $etlConfig = new EtlConfiguration(
            CONFIG_DIR . '/etl/etl.json',
            null,
            $this->logger,
            $configOptions
        );
        $etlConfig->initialize();
        Utilities::setEtlConfig($etlConfig);

        $scriptOptions = array_merge(
            array(
                'default-module-name' => 'xdmod',
                'process-sections' => $pipelines,
            ),
            $params
        );
        $this->logger->debug(
            sprintf(
                'Running ETL pipeline with script options %s',
                json_encode($scriptOptions)
            )
        );

        $overseerOptions = new EtlOverseerOptions(
            $scriptOptions,
            $this->logger
        );

        $utilitySchema = $etlConfig->getGlobalEndpoint('utility')->getSchema();
        $overseerOptions->setResourceCodeToIdMapSql(sprintf("SELECT id, code from %s.resourcefact", $utilitySchema));

        $overseer = new EtlOverseer($overseerOptions, $this->logger);
        $overseer->execute($etlConfig);
    }

    public function shredDirectory($directory)
    {
        $params = array(
          'include-only-resource-codes' => $this->resource,
          'variable-overrides' => ['CLOUD_EVENT_LOG_DIRECTORY' => $directory]
        );

        $this->runEtlPipeline(array('jobs-common','jobs-cloud-common','ingest-resources'), $params);
        $this->runEtlPipeline(array('jobs-cloud-ingest-eucalyptus'));
    }
}

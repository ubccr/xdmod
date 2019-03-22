<?php
/**
 * Abstract class used when shredding cloud event data.
 *
 * @author Greg Dean <gmdean@ccr.buffalo.edu>
 */
namespace OpenXdmod\Shredder;

use Exception;
use CCR\DB\iDatabase;
use OpenXdmod\Shredder;
use ETL\Utilities;

abstract class aCloud extends Shredder
{

    protected $etlPipelines = array();

    /**
     * @inheritdoc
     */
    public function __construct(iDatabase $db, array $pipelines)
    {
        $this->logger = \Log::singleton('null');
        $this->etlPipelines = $pipelines;
    }

    /**
     * Shredding by specifing a single file in not supported by the cloud pipelines.
     * Throw an exception if someone tries to shred cloud data using the -i flag instead
     * of using -d
     */
    public function shredFile($line)
    {
        throw new Exception('Cloud resources do not support shredding by file. Please use the -d option and specify a directory');
    }

    /**
     * @inheritdoc
     */
    public function shredDirectory($directory)
    {
        if (!is_dir($directory)) {
            $this->logger->crit("'$directory' is not a directory");
            return false;
        }

        if (empty($this->etlPipelines)) {
            $this->logger->crit("A pipeline to run was not specified. Please provide a pipeline to run.");
            return false;
        }

        Utilities::runEtlPipeline(array('jobs-common', 'ingest-organizations', 'ingest-resource-types', 'ingest-resources', 'jobs-cloud-common'), $this->logger);
        Utilities::runEtlPipeline(
            $this->etlPipelines,
            $this->logger,
            array(
              'include-only-resource-codes' => $this->resource,
              'variable-overrides' => ['CLOUD_EVENT_LOG_DIRECTORY' => $directory]
            )
        );
    }

    /**
     * Returns false so the same function in the parent class is not called since the cloud formats do not have jobs to ingest
     */
    public function getJobIngestor($ingestAll = false){
        return false;
    }
}

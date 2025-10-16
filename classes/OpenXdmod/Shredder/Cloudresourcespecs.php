<?php
/**
 * Abstract class used when shredding cloud event data.
 *
 * @author Greg Dean <gmdean@ccr.buffalo.edu>
 */
namespace OpenXdmod\Shredder;

use CCR\Log;
use Exception;
use CCR\DB\iDatabase;
use OpenXdmod\Shredder;
use ETL\Utilities;

class Cloudresourcespecs extends Shredder
{

    /**
     * @inheritdoc
     */
    public function __construct(iDatabase $db)
    {
        $this->logger = Log::singleton('null');
    }

    /**
     * Shredding by specifing a single file in not supported by the cloud pipelines.
     * Throw an exception if someone tries to shred cloud data using the -i flag instead
     * of using -d
     */
    public function shredFile($line)
    {
        throw new Exception('Cloud resources specs do not support shredding by file. Please use the -d option and specify a directory');
    }

    /**
     * @inheritdoc
     */
    public function shredDirectory($directory)
    {
        if (!is_dir($directory)) {
            $this->logger->critical("'$directory' is not a directory");
            return false;
        }

        Utilities::runEtlPipeline(
            ['shred-cloud-resource-specs'],
            $this->logger,
            array(
              'include-only-resource-codes' => $this->resource,
              'variable-overrides' => ['CLOUD_RESOURCE_SPECS_DIRECTORY' => $directory]
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

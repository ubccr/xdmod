<?php
/**
 * Generic cloud event data shredder.
 *
 * @author Greg Dean <gmdean@ccr.buffalo.edu>
 */

namespace OpenXdmod\Shredder;

use CCR\DB\iDatabase;
use OpenXdmod\Shredder;
use ETL\Utilities;

class Generalcloud extends Shredder
{
    /**
     * @inheritdoc
     */
    public function __construct(iDatabase $db)
    {
        $this->logger = \Log::singleton('null');
    }

    /**
     * @inheritdoc
     */
    public function shredFile($line)
    {
        throw new Exception('The OpenStack shredder does not supported shredding by file. Please use the -d option and specify a directory');

    }

    /**
     * @inheritdoc
     */
    public function shredDirectory($directory)
    {
        if (!is_dir($directory)) {
            $this->logger->err("'$directory' is not a directory");
            return false;
        }

        Utilities::runEtlPipeline(array('jobs-common','jobs-cloud-common','ingest-resources'), $this->logger);
        Utilities::runEtlPipeline(
            array('jobs-cloud-ingest-eucalyptus'),
            $this->logger,
            array(
              'include-only-resource-codes' => $this->resource,
              'variable-overrides' => ['CLOUD_EVENT_LOG_DIRECTORY' => $directory]
            )
        );
    }
}

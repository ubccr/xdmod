<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace OpenXdmod\Shredder;

use CCR\DB\iDatabase;
use ETL\Utilities;
use Exception;
use Log;
use OpenXdmod\Shredder;

/**
 * Storage shredder.
 */
class Storage extends Shredder
{
    /**
     * Override constructor to prevent job related database query.
     */
    protected function __construct(iDatabase $db)
    {
        $this->db = $db;
        $this->logger = Log::singleton('null');
        $this->format = 'storage';
    }

    /**
     * The storage shredder does not support shredding a single file so throw
     * an exception.
     */
    public function shredFile($file)
    {
        throw new Exception(<<<EOMSG
Storage shredder does not support shredding by file.  Please use the -d option
and specify a directory.
EOMSG
        );
    }

    /**
     * Shred the files in the specified directory using an ETL pipeline.
     */
    public function shredDirectory($dir)
    {
        $this->logger->notice("Shredding directory '$dir'");

        if (!is_dir($dir)) {
            $this->logger->err("'$dir' is not a directory");
            return false;
        }

        Utilities::runEtlPipeline(
            ['staging-ingest-storage'],
            $this->logger,
            ['variable-overrides' => ['STORAGE_LOG_DIRECTORY' => $dir]]
        );
    }

    /**
     * Returns false to indicate this shredder does not support ingestion of
     * jobs.
     */
    public function getJobIngestor($ingestAll = false)
    {
        return false;
    }
}

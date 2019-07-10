<?php

namespace DataWarehouse\Export\FileWriter;

use Log;

/**
 * File writer that doesn't actually write to any files.
 *
 * Used for dry runs and testing.
 */
class NullFileWriter extends aFileWriter
{
    /**
     * Don't open the file or do anything.
     *
     * @param string $file
     * @param \Log $logger
     */
    public function __construct($file, Log $logger)
    {
        $this->setLogger($logger);
    }

    /**
     * Don't close anything.
     */
    public function close()
    {
    }

    /**
     * Don't write anything.
     *
     * @param array $record
     */
    public function writeRecord(array $record)
    {
    }
}

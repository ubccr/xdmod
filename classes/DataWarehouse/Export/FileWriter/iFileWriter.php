<?php

namespace DataWarehouse\Export\FileWriter;

use Log;

/**
 * Interface for writing data warehouse batch export data to a file.
 */
interface iFileWriter
{
    /**
     * Open the file for writing.
     *
     * @param string $file
     * @param \Log $logger
     */
    public function __construct($file, Log $logger);

    /**
     * Close the file being written to.
     *
     * Must be called when done writing data.
     */
    public function close();

    /**
     * Write a data warehouse batch export record to file.
     *
     * @param array $record
     */
    public function writeRecord(array $record);
}

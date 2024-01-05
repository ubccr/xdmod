<?php

namespace DataWarehouse\Export\FileWriter;

use Psr\Log\LoggerInterface;

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
     * @param LoggerInterface $logger
     */
    public function __construct($file, LoggerInterface $logger)
    {
        $this->setLogger($logger);
    }

    /**
     * Don't close anything.
     */
    public function close(): void
    {
    }

    /**
     * Don't write anything.
     *
     * @param array $record
     */
    public function writeRecord(array $record): void
    {
    }
}

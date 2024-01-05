<?php

namespace DataWarehouse\Export\FileWriter;

use CCR\Loggable;
use Psr\Log\LoggerInterface;

/**
 * Abstract class for writing data warehouse batch export data.
 */
abstract class aFileWriter extends Loggable implements iFileWriter
{
    /**
     * @var resource
     */
    protected $fh;

    /**
     * Open the file for writing.
     *
     * @param string $file
     * @param LoggerInterface $logger
     */
    public function __construct(protected $file, LoggerInterface $logger)
    {
        parent::__construct($logger);
        $this->fh = fopen($this->file, 'w');

        if ($this->fh === false) {
            $this->logAndThrowException(
                sprintf('Failed to open file "%s"', $this->file)
            );
        }
    }

    /**
     * Close file.
     */
    public function close(): void
    {
        if (fclose($this->fh) === false) {
            $this->logAndThrowException(
                sprintf('Failed to close file "%s"', $this->file)
            );
        }
    }

    /**
     * Return string representation of class instance.
     *
     * @returns string
     */
    public function __toString(): string
    {
        return sprintf('%s(file: "%s")', static::class, $this->file);
    }
}

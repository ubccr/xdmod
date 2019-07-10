<?php

namespace DataWarehouse\Export\FileWriter;

use CCR\Loggable;
use Log;

/**
 * Abstract class for writing data warehouse batch export data.
 */
abstract class aFileWriter extends Loggable implements iFileWriter
{
    /**
     * @var string
     */
    protected $file;

    /**
     * @var resource
     */
    protected $fh;

    /**
     * Open the file for writing.
     *
     * @param string $file
     * @param \Log $logger
     */
    public function __construct($file, Log $logger)
    {
        parent::__construct($logger);

        $this->file = $file;
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
    public function close()
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
    public function __toString()
    {
        return sprintf('%s(file: "%s")', get_class($this), $this->file);
    }
}

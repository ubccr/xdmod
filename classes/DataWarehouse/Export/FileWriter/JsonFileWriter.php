<?php

namespace DataWarehouse\Export\FileWriter;

use CCR\Json;
use Log;

/**
 * Write data warehouse batch export to file in JSON format.
 *
 * The data is structured as an array of arrays.
 */
class JsonFileWriter extends aFileWriter
{
    /**
     * Tracks whether or not any records have been written.
     *
     * Used to determine if a comma should be written to the file before the
     * next record.
     *
     * @var boolean
     */
    private $recordWritten = false;

    /**
     * Open the file and write the opening bracket.
     *
     * @param string $file
     * @param \Log $logger
     */
    public function __construct($file, Log $logger)
    {
        parent::__construct($file, $logger);

        if (fwrite($this->fh, '[') === false) {
            $this->logAndThrowException(
                sprintf('Failed to write to file "%s"', $this->file)
            );
        }
    }

    /**
     * Write the closing bracket and close the file.
     */
    public function close()
    {
        if (fwrite($this->fh, "\n]") === false) {
            $this->logAndThrowException(
                sprintf('Failed to write to file "%s"', $this->file)
            );
        }

        parent::close();
    }

    /**
     * Write a record to file formatted as JSON.
     *
     * @param array $record
     */
    public function writeRecord(array $record)
    {
        $json = json_encode($record, JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $this->logAndThrowException(sprintf(
                'Failed to encode data as JSON: %s',
                Json::getLastErrorMessage()
            ));
        }

        // If any records have already been written then a comma is needed
        // before the next record.
        $separator = ($this->recordWritten ? ',' : '') . "\n    ";

        if (fwrite($this->fh, $separator . $json) === false) {
            $this->logAndThrowException(
                sprintf('Failed to write to file "%s"', $this->file)
            );
        }

        $recordWritten = true;
    }
}

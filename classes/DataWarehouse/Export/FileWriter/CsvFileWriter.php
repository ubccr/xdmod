<?php

namespace DataWarehouse\Export\FileWriter;

/**
 * Write data warehouse batch export to file in CSV format.
 */
class CsvFileWriter extends aFileWriter
{
    /**
     * Write record to file formatted as CSV.
     *
     * @param array $record
     */
    public function writeRecord(array $record)
    {
        if (fputcsv($this->fh, $record) === false) {
            $this->logAndThrowException(
                sprintf('Failed to write to file "%s"', $this->file)
            );
        }
    }
}

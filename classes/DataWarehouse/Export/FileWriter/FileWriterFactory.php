<?php

namespace DataWarehouse\Export\FileWriter;

use CCR\Loggable;

/**
 * Factory class for creating file writers.
 */
class FileWriterFactory extends Loggable
{
    /**
     * Create a file writer for the given format and file.
     *
     * @param string $format Format used by file writer.
     * @param string $file File path.
     * @return \DataWarehouse\Export\FileWriter\iFileWriter
     */
    public function getFileWriter($format, $file)
    {
        $this->logger->debug([
            'message' => 'Creating new file writer',
            'format' => $format,
            'file' => $file
        ]);

        switch ($format) {
            case 'csv':
                return new CsvFileWriter($file, $this->logger);
                break;
            case 'json':
                return new JsonFileWriter($file, $this->logger);
                break;
            case 'null':
                return new NullFileWriter($file, $this->logger);
                break;
            default:
                $this->logAndThrowException(
                    sprintf('Unsupported format "%s"', $format)
                );
                break;
        }
    }
}

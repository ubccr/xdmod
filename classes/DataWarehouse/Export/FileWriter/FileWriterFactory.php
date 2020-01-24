<?php

namespace DataWarehouse\Export\FileWriter;

use CCR\Loggable;

/**
 * Factory class for creating file writers.
 */
class FileWriterFactory extends Loggable
{
    // Constants used in log messages.
    const LOG_MODULE_KEY = 'module';
    const LOG_MODULE = 'data-warehouse-export';
    const LOG_MESSAGE_KEY = 'message';
    const LOG_FORMAT_KEY = 'format';
    const LOG_FILE_KEY = 'file';

    /**
     * Create a file writer for the given format and file.
     *
     * @param string $format Format used by file writer.
     * @param string $file File path.
     * @return \DataWarehouse\Export\FileWriter\iFileWriter
     */
    public function createFileWriter($format, $file)
    {
        $this->logger->debug([
            self::LOG_MODULE_KEY => self::LOG_MODULE,
            self::LOG_MESSAGE_KEY => 'Creating new file writer',
            self::LOG_FORMAT_KEY => $format,
            self::LOG_FILE_KEY => $file
        ]);

        switch (strtolower($format)) {
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

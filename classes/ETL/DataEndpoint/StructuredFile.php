<?php
/**
 * A data endpoint for structured files.
 */

namespace ETL\DataEndpoint;

abstract class StructuredFile extends File implements iDataEndpoint
{
    /**
     * Decode a file and return the parsed representation.
     *
     * @return object An object generated from the parsed file.
     *
     * @throw Exception If the file could not be read.
     * @throw Exception If the file could not be parsed.
     */
    abstract public function parse();
}

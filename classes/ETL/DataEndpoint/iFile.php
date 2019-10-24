<?php
/**
 * Interface for file data endpoints. Files add the ability to specify a path and mode.
 */

namespace ETL\DataEndpoint;

interface iFile extends iDataEndpoint
{

    /**
     * @return The path to the file
     */

    public function getPath();

    /**
     * @return The mode used to access the file specified by the path.
     */

    public function getMode();
}

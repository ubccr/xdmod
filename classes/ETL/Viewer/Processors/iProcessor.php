<?php

namespace ETL\Viewer\Processors;

/**
 * Interface iProcessor
 *
 * This interface is to be implemented by
 *
 * @package ETL\Viewer
 * @author Ryan Rathsam <ryanrath@buffalo.edu>
 */
interface iProcessor {

    /**
     * @param array|object $data
     * @return array|object
     */
    public function process($data);

}

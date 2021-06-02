<?php

namespace ETL\Viewer;

/**
 * Class EndpointProcessor
 *
 * This class is responsible for processing the various endpoints into a format that is usable by the ETL Viewer.
 *
 * @package ETL\Viewer
 */
class EndpointProcessor extends Processor
{

    public function process($data)
    {
        $result = new \stdClass();
        $result->name = $data->name;
        $result->type = $data->type;
        $result->key = $data->key;

    }
}

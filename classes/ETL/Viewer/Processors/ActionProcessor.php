<?php

namespace ETL\Viewer\Processors;

/**
 * Class ActionProcessor
 *
 * This class understands how to process an ETLv2 Action data structure for displaying in the ETL Graph View.
 *
 * @package ETL\Viewer
 */
class ActionProcessor extends Processor
{
    protected $sources = array();

    protected $destinations = array();

    /**
     * @see iProcessor::process()
     */
    public function process($data)
    {

    }

    /**
     * @return array
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * @return array
     */
    public function getDestinations()
    {
        return $this->destinations;
    }

}

<?php

namespace ETL\Viewer\Processors;

/**
 * Class PipelineProcessor
 *
 * This class understands how to process an ETLv2 Pipeline for displaying in the ETL Graph View Component.
 *
 * An ETLv2 Pipeline consists of one or more ETLv2 Actions.
 *
 * @package ETL\Viewer
 */
class PipelineProcessor extends Processor
{

    /**
     * The processor that understands how to parse an ETLv2 Action.
     *
     * @var iProcessor
     */
    protected $actionProcessor;

    public function __construct($actionProcessor = null)
    {
        $this->actionProcessor = isset($actionProcessor) ? $actionProcessor : new ActionProcessor();
    }


    /**
     * @param array|object $data
     *
     * @return mixed|void
     */
    public function process($data)
    {
        /**
         *
         */
        list($sources, $actions, $destinations) = $this->actionProcessor->process($data);

    }
}

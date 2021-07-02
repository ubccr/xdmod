<?php

namespace ETL\Viewer\Processors;

use ETL\Configuration\EtlConfiguration;

abstract class Processor implements iProcessor
{
    /**
     * @var EtlConfiguration
     */
    protected $etlConfig;

    public function __construct($etlConfig)
    {
        $this->etlConfig = $etlConfig;
    }

}

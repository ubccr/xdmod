<?php
/* ==========================================================================================
 * Dummy aggregator to be used as a placeholder during development and testing
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-11-01
 *
 * @see iAction
 * ==========================================================================================
 */

namespace ETL\Aggregator;

use ETL\iAction;
use ETL\EtlConfiguration;
use ETL\EtlOverseerOptions;
use \Log

class DummyAggregator
implements iAction
{
    protected $options;
    public function __construct(AggregatorOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        $this->options = $options;
    }
    public function execute(EtlOverseerOptions $etlOptions)
    {
        return true;
    }
    public function verify(EtlOverseerOptions $etlConfig)
    {
        return true;
    }
    public function getName()
    {
        return $this->options->name;
    }
    public function getClass()
    {
        return $this->options->class;
    }
    public function __toString()
    {
        return $this->getName() . "(" . $this->getClass() . ")";
    }
}  // class DummyAggregator

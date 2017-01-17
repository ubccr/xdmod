<?php
/* ==========================================================================================
 * Dummy ingestor to be used as a placeholder during development and testing
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2015-11-01
 *
 * @see iAction
 * ==========================================================================================
 */

namespace ETL\Ingestor;

use ETL\aOptions;
use ETL\iAction;
use ETL\EtlConfiguration;
use ETL\EtlOverseerOptions;
use \Log;

class DummyIngestor implements iAction
{
    protected $options;
    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        $this->options = $options;
    }
    public function execute(EtlOverseerOptions $etlOptions)
    {
        return true;
    }
    public function verify(EtlOverseerOptions $etlConfig = null)
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
    public function getOptions()
    {
        return $this->options;
    }
    public function isVerified()
    {
        return true;
    }
    public function __toString()
    {
        return $this->getName() . "(" . $this->getClass() . ")";
    }
}  // class DummyIngestor

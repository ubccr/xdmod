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
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use Log;

class DummyIngestor extends aAction implements iAction
{
    public function __construct(aOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);
    }

    public function execute(EtlOverseerOptions $etlOverseerOptions)
    {
        return true;
    }

    public function initialize(EtlOverseerOptions $etlOverseerOptions = null)
    {
        parent::initialize($etlOverseerOptions);
        return true;
    }

    protected function performPreExecuteTasks()
    {
        return true;
    }

    protected function performPostExecuteTasks($numRecordsProcessed = null)
    {
        return true;
    }
}  // class DummyIngestor

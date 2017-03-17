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
use Log;

class DummyAggregator extends aAction implements iAction
{
    public function __construct(AggregatorOptions $options, EtlConfiguration $etlConfig, Log $logger = null)
    {
        parent::__construct($options, $etlConfig, $logger);
    }

    public function execute(EtlOverseerOptions $etlOptions)
    {
        return true;
    }

    public function verify(EtlOverseerOptions $etlOptions = null)
    {
        list($startDate, $endDate) = $etlOptions->getDatePeriod();
        $this->currentStartDate = $startDate;
        $this->currentEndDate = $endDate;
        return true;
    }
}  // class DummyAggregator

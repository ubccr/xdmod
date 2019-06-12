<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class MaxProcessorCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(MAX(jf.processor_count),0)',
            'max_processors',
            'Job Size: Max',
            'Core Count',
            0
        );
    }

    public function getInfo()
    {
        return 'The maximum size ' . ORGANIZATION_NAME . ' job in number of cores.<br/><i>Job Size: </i>The total number of processor cores used by a (parallel) job.';
    }
}

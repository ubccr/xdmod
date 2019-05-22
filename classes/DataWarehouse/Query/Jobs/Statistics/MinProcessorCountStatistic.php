<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class MinProcessorCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(
                MIN(
                    CASE
                        WHEN jf.processor_count = 0
                            THEN
                                NULL
                            ELSE
                                jf.processor_count
                    END
                ),
            0)',
            'min_processors',
            'Job Size: Min',
            'Core Count',
            0
        );
        $this->setOrderByStat(SORT_DESC);
    }
    public function getInfo()
    {
        return 'The minimum size ' . ORGANIZATION_NAME . ' job in number of cores.<br/><i>Job Size: </i>The total number of processor cores used by a (parallel) job.';
    }
}

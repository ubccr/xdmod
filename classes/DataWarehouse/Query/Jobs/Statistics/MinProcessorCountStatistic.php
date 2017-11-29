<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/**
 * @author Amin Ghadersohi
 * @date 2011-Feb-07
 *
 * class for calculating the min processor count
 */

class MinProcessorCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(
                MIN(
                    CASE
                        WHEN jf.processors = 0
                            THEN
                                NULL
                            ELSE
                                jf.processors
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
        return 'The minimum size ' . ORGANIZATION_NAME . ' job in number of cores.<br/>
            <i>Job Size: </i>The total number of processor cores used by a (parallel) job.';
    }
}

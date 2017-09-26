<?php
namespace DataWarehouse\Query\Jobs\Statistics;

/*
 * @author Amin Ghadersohi
 * @date 2011-Feb-07
 *
 * class for calculating the normalized average processor count
 */

class NormalizedAverageProcessorCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            '100.0 *
                COALESCE(
                    SUM(jf.processor_count * jf.running_job_count)
                    /
                    SUM(jf.running_job_count)
                    /
                    (
                        SELECT
                            SUM(rrf.processors)
                        FROM
                            modw.resourcespecs rrf
                        WHERE
                            FIND_IN_SET(
                                rrf.resource_id,
                                GROUP_CONCAT(DISTINCT jf.task_resource_id)
                            ) <> 0
                            AND ' . $query_instance->getAggregationUnit()->getUnitName().'_end_ts >= rrf.start_date_ts
                            AND (
                                rrf.end_date_ts IS NULL
                                OR ' . $query_instance->getAggregationUnit()->getUnitName() . '_end_ts <= rrf.end_date_ts
                            )
                    )
                , 0)',
            'normalized_avg_processors',
            'Job Size: Normalized',
            '% of Total Cores',
            1
        );
    }

    public function getInfo()
    {
        return "The percentage average size " . ORGANIZATION_NAME . " job over total machine cores.<br>
            <i>Normalized Job Size: </i>The percentage total number of processor cores used by a (parallel) job over the total number of cores on the machine.";
    }

    /**
     * @see DataWarehouse\Query\Statistic
     */
    public function usesTimePeriodTablesForAggregate()
    {
        return false;
    }
}

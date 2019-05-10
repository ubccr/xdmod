<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class AverageProcessorCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('COALESCE(SUM(jf.processor_count*jf.running_job_count)/SUM(jf.running_job_count),0)', 'avg_processors', 'Job Size: Per Job', 'Core Count', 1);
    }

    public function getInfo()
    {
        return  'The average job size per  ' . ORGANIZATION_NAME . ' job.<br><i>Job Size: </i>The number of processor cores used by a (parallel) job.';
    }

    /**
     * @see DataWarehouse\Query\Statistic
     */
    public function usesTimePeriodTablesForAggregate()
    {
        return false;
    }
}

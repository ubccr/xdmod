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
        $job_count_formula = $query_instance->getQueryType() == 'aggregate'?'job_count':'running_job_count';
        parent::__construct('100.0*coalesce(sum(jf.processors*jf.'.$job_count_formula.')/sum(jf.'.$job_count_formula.')/(select sum(rrf.processors) from modw.resourcespecs rrf where find_in_set(rrf.resource_id,group_concat(distinct jf.resource_id)) <> 0 and '.$query_instance->getAggregationUnit()->getUnitName().'_end_ts >= rrf.start_date_ts and (rrf.end_date_ts is null or '.$query_instance->getAggregationUnit()->getUnitName().'_end_ts <= rrf.end_date_ts)),0)', 'normalized_avg_processors', 'Job Size: Normalized', '% of Total Cores', 1);
    }

    public function getInfo()
    {
        return  "The percentage average size ".ORGANIZATION_NAME." job over total machine cores.<br>
        <i>Normalized Job Size: </i>The percentage total number of processor cores used by a (parallel) job over the total number of cores on the machine.";
    }
}

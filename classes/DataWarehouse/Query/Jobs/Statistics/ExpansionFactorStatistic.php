<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class ExpansionFactorStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.sum_weighted_expansion_factor)/SUM(jf.sum_job_weights),0)',
            'expansion_factor',
            'User Expansion Factor',
            'User Expansion Factor',
            1
        );
    }

    public function getInfo()
    {
        return  'Gauging ' . ORGANIZATION_NAME . ' job-turnaround time, it measures the ratio of wait time and the total time from submission to end of execution.<br/><i>User Expansion Factor = ((wait duration + wall duration) / wall duration). </i>';
    }
}

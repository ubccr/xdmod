<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class JobCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct(
            'COALESCE(SUM(jf.ended_job_count),0)',
            'job_count',
            'Number of Jobs Ended',
            'Number of Jobs',
            0
        );
    }

    public function getInfo()
    {
        return 'The total number of ' . ORGANIZATION_NAME . ' jobs that ended within the selected duration.<br/>
            <i>Job: </i>A scheduled process for a computer resource in a batch processing environment.';
    }
}

<?php
namespace DataWarehouse\Query\Jobs\Statistics;

class SubmittedJobCountStatistic extends \DataWarehouse\Query\Jobs\Statistic
{
    public function __construct($query_instance = null)
    {
        parent::__construct('COALESCE(SUM(jf.submitted_job_count),0)', 'submitted_job_count', 'Number of Jobs Submitted', 'Number of Jobs', 0);
    }

    public function getDefinition()
    {
        return  'The total number of ' . ORGANIZATION_NAME . ' jobs that were submitted/queued within the selected duration.<br/><i>Job: </i>A scheduled process for a computer resource in a batch processing environment.';
    }
    public function isVisible()
    {
        return true;
    }
}

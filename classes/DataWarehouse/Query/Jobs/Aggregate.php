<?php
namespace DataWarehouse\Query\Jobs;

class Aggregate extends \DataWarehouse\Query\Query
{
    public function __construct(
        $aggregation_unit_name,
        $start_date,
        $end_date,
        $group_by,
        $stat = 'job_count',
        array $parameters = array()
    ) {
        parent::__construct(
            'Jobs',
            'modw_aggregates',
            'jobfact',
            array('started_job_count', 'running_job_count'),
            $aggregation_unit_name,
            $start_date,
            $end_date,
            $group_by,
            $stat,
            $parameters
        );
    }
}

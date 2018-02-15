<?php
namespace DataWarehouse\Query\Cloud;

/*
* @author Rudra Chakraborty
* @date 2018-02-15
*/
class Timeseries extends \DataWarehouse\Query\Timeseries
{
    public function __construct(
        $aggregation_unit_name,
        $start_date,
        $end_date,
        $group_by,
        $stat = 'job_count',
        array $parameters = array(),
        $query_groupname = 'query_groupname',
        array $parameter_description = array(),
        $single_stat = false
    ) {
        parent::__construct(
            'Cloud',
            'modw_cloud',
            'cm_euca_fact',
            array('started_job_count', 'running_job_count'),
            $aggregation_unit_name,
            $start_date,
            $end_date,
            $group_by,
            $stat ,
            $parameters,
            $query_groupname,
            $parameter_description,
            $single_stat
        );
    }
}

?>

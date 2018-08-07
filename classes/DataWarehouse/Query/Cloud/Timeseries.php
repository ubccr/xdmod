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
        $stat = 'cloud_num_vms_running',
        array $parameters = array(),
        $query_groupname = 'query_groupname',
        array $parameter_description = array(),
        $single_stat = false
    ) {
        parent::__construct(
            'Cloud',
            'modw_cloud',
            'cloudfact',
            array('cloud_num_vms_started', 'cloud_num_vms_running'),
            $aggregation_unit_name,
            $start_date,
            $end_date,
            $group_by,
            $stat,
            $parameters,
            $query_groupname,
            $parameter_description,
            $single_stat
        );
    }
}

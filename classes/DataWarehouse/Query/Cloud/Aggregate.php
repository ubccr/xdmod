<?php
namespace DataWarehouse\Query\Cloud;

/*
* @author Rudra Chakraborty
* @date 2018-02-15
*/
class Aggregate extends \DataWarehouse\Query\Query
{
    public function __construct(
        $aggregation_unit_name,
        $start_date,
        $end_date,
        $group_by,
        $stat = 'num_vms_ended',
        array $parameters = array(),
        $query_groupname = 'query_groupname',
        array $parameterDescriptions = array(),
        $single_stat = false
    ) {
        parent::__construct(
            'Cloud',
            'modw_cloud',
            'cloudfact',
            array('num_vms_started', 'num_vms_running'),
            $aggregation_unit_name,
            $start_date,
            $end_date,
            $group_by,
            $stat,
            $parameters,
            $query_groupname,
            $parameterDescriptions,
            $single_stat
        );
    }
}

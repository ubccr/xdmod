<?php
namespace DataWarehouse\Query\Cloud;

class Aggregate extends \DataWarehouse\Query\Query
{
    public function __construct(
        $aggregation_unit_name,
        $start_date,
        $end_date,
        $group_by,
        $stat = 'cloud_num_sessions_ended',
        array $parameters = array()
    ) {
        parent::__construct(
            'Cloud',
            'modw_cloud',
            'cloudfact',
            array('cloud_num_sessions_started', 'cloud_num_sessions_running'),
            $aggregation_unit_name,
            $start_date,
            $end_date,
            $group_by,
            $stat,
            $parameters
        );
    }
}

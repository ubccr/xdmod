<?php
namespace DataWarehouse\Query\Cloud\Statistics;

use DataWarehouse\Query\Model\TableField;

/*
* @author Rudra Chakraborty
* @date 05-31-2018
*
* Total of Running VMs
*/
class RunningVMCountStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {
        $sql = 'COALESCE(SUM(jf.num_vms_running), 0)';

        if ($query_instance->getQueryType() == 'aggregate') {
            $date_table = $query_instance->getDateTable();
            if ($date_table) {
                $date_id_field = new TableField($date_table, 'id');

                $sql = 'COALESCE(SUM(CASE ' . $date_id_field . ' WHEN ' . $query_instance->getMinDateId() . ' THEN jf.num_vms_running ELSE jf.num_vms_started END), 0)';
            }
        }

        parent::__construct(
            $sql,
            'cloud_num_vms_running',
            'Number of VMs Running',
            'Number of VMs',
            0
        );
    }

    public function getInfo()
    {
        return  "The total number of running virtual machines on a cloud resource.<br/>";
    }

    public function isVisible()
    {
        return true;
    }
}

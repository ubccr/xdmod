<?php
namespace DataWarehouse\Query\Cloud\Statistics;

class CoreUtilizationStatistic extends \DataWarehouse\Query\Cloud\Statistic
{
    public function __construct($query_instance = null)
    {

        $sql = 'COALESCE((SUM(jf.core_time) / SUM(DISTINCT jf.core_time_available)) * 100, 0)';

        if ($query_instance->getQueryType() == 'aggregate') {
            $agg_unit = $query_instance->getAggregationUnit()->getUnitName();
            $agg_table = "cloudfact_by_" . $agg_unit;
            $agg_id = $agg_unit."_id";

            $core_hours_sql = '
               SELECT SUM(rsa.core_time_avasilable) FROM modw_aggregates.resourcespecsfact_by_'.$agg_unit.' as rsa WHERE rsa.'.$agg_id.' BETWEEN '.$query_instance->getMinDateId().' AND '. $query_instance->getMaxDateId().')';

            $sql = "COALESCE((SUM(jf.core_time) / ($core_hours_sql) * 100, 0)";
        }

        parent::__construct(
            $sql,
            'cloud_core_utilization',
            'Core Hours Utilization:',
            '%',
            0
        );
    }

    public function getInfo()
    {
        return 'A pecentage that shows how many core hours were reserved over a time period against how many core hours a resource had during that time period.<br/><b>Core Hours</b>: The product of the number of cores assigned to a VM and its wall time, in hours.<br/><b>Core Hours Available:</b> The total number of core hours available for a time period. Calculated by taking the product of the number of cores available over a time period, number of days in a time period and the number of hours in a day.';
    }
}

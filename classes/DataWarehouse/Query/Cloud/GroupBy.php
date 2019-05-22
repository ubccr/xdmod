<?php
namespace DataWarehouse\Query\Cloud;

abstract class GroupBy extends \DataWarehouse\Query\GroupBy
{
    public function __construct($name, array $additional_permitted_parameters = array(), $possible_values_query = null)
    {
        $permitted_parameters = array_unique(
            array_merge(
                array_keys(Aggregate::getRegisteredStatistics()),
                $additional_permitted_parameters
            )
        );

        parent::__construct($name, $permitted_parameters, $possible_values_query);
    }

    public function getDrillTargets($statistic_name, $query_classname)
    {
        $registerd_group_bys = Aggregate::getRegisteredGroupBys();
        $drill_target_group_bys = array();

        foreach ($registerd_group_bys as $group_by_name => $group_by_classname) {
            if ($group_by_name == 'none' || $group_by_name == $this->getName()) {
                continue;
            }

            $group_by_classname = $query_classname::getGroupByClassname($group_by_name);
            $group_by_instance = $query_classname::getGroupBy($group_by_name);
            $permitted_stats = $group_by_instance->getPermittedStatistics();

            if ($group_by_instance->getAvailableOnDrilldown() !== false && array_search($statistic_name, $permitted_stats) !== false) {
                $drill_target_group_bys[] = $group_by_name.'-'.$group_by_instance->getLabel();
            }
        }

        sort($drill_target_group_bys);
        return $drill_target_group_bys;
    }
}

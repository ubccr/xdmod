<?php

namespace DataWarehouse\Query\Jobs;

/*
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* class for adding group bys to a jobs query
*
*/
use Xdmod\Config;

abstract class GroupBy extends \DataWarehouse\Query\GroupBy
{
    public function __construct($name, array $additional_permitted_parameters = array(), $possible_values_query = null)
    {
        $permitted_paramters = array_unique(
            array_merge(
                array_keys(Aggregate::getRegisteredStatistics()),
                $additional_permitted_parameters
            )
        );

        parent::__construct($name, $permitted_paramters, $possible_values_query);
    }

    public function getDrillTargets($statistic_name, $query_classname)
    {
        $registerd_group_bys = Aggregate::getRegisteredGroupBys();
        $drill_target_group_bys = array();
        $config = Config::factory();
        $multipleServiceProviders = \xd_utilities\getConfiguration('features', 'multiple_service_providers') === 'on';
        foreach ($registerd_group_bys as $group_by_name => $group_by_classname) {
            if ($group_by_name == 'none' || $group_by_name == $this->getName()) {
                continue;
            }

            $group_by_classname = $query_classname::getGroupByClassname($group_by_name);
            $group_by_instance = $query_classname::getGroupBy($group_by_name);
            $permitted_stats = $group_by_instance->getPermittedStatistics();

            $realm = $group_by_instance->getRealm();
            $found = array_pop(
                array_filter(
                    $config['datawarehouse']['realms'][$realm]['group_bys'],
                    function ($value) use ($group_by_name) {
                        return $value['name'] === $group_by_name;
                    }
                )
            );
            $visible = true;
            if (array_key_exists('visible', $found)) {
                $visible = $multipleServiceProviders || $found['visible'];
            }

            if ($group_by_instance->getAvailableOnDrilldown() !== false && array_search($statistic_name, $permitted_stats) !== false && $visible === true) {
                $drill_target_group_bys[] = $group_by_name . '-' . $group_by_instance->getLabel();
            }
        }

        sort($drill_target_group_bys);
        return $drill_target_group_bys;
    }
}

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
}

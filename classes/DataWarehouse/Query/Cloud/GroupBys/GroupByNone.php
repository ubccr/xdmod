<?php

namespace DataWarehouse\Query\Cloud\GroupBys;

/*
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* class for adding no group by to a query
*
*/

class GroupByNone extends \DataWarehouse\Query\Cloud\GroupBy
{
    public function __construct()
    {
        parent::__construct('none', array());
    }
    public static function getLabel()
    {
        return  ORGANIZATION_NAME;
    }

    public function getDefaultDatasetType()
    {
        return 'timeseries';
    }
    public function getInfo()
    {
        return "Summarizes cloud data reported to the " . ORGANIZATION_NAME . ".";
    }
    public function getDefaultDisplayType($dataset_type = null)
    {
        if($dataset_type == 'timeseries') {
            return 'line';
        }
        else
        {
            return 'h_bar';
        }
    }
    public function getDefaultCombineMethod()
    {
        return 'stack';
    }

    // JMS Oct 15
    // Use the GroupBy subclass to add a Where clause and needed Join
    public function addWhereJoin(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group, $operation, $whereConstraint)
    {
        // NO-OP
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addField(new \DataWarehouse\Query\Model\FormulaField('-9999', $this->getIdColumnName($multi_group)));

        $fieldLabel = "'".ORGANIZATION_NAME."'";

        $query->addField(new \DataWarehouse\Query\Model\FormulaField($fieldLabel, $this->getLongNameColumnName($multi_group)));
        $query->addField(new \DataWarehouse\Query\Model\FormulaField($fieldLabel, $this->getShortNameColumnName($multi_group)));
        $query->addField(new \DataWarehouse\Query\Model\FormulaField($fieldLabel, $this->getOrderIdColumnName($multi_group)));
    }
    public function pullQueryParameterDescriptions(&$request)
    {
        $parameters = array();


        return $parameters;
    }
}

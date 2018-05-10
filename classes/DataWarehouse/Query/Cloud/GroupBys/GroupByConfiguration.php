<?php

namespace DataWarehouse\Query\Cloud\GroupBys;

use DataWarehouse\Query\Query;
use DataWarehouse\Query\Model\OrderBy;
use DataWarehouse\Query\Model\Schema;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Model\WhereCondition;

/*
* @author Rudra Chakraborty
* @date 03/06/2018
*
* Group By Configuration
*/

class GroupByConfiguration extends \DataWarehouse\Query\Cloud\GroupBy
{
    public static function getLabel()
    {
        return 'Instance Type';
    }

    public function getInfo()
    {
        return "The instance type of a virtual machine.";
    }

    public function __construct()
    {
        parent::__construct(
            'configuration',
            array(),
            "SELECT distinct
                gt.id,
                gt.instance_type as short_name,
                gt.display as long_name,
                gt.instance_type_id as order_id
            FROM instance_type gt
            where 1
            order by order_id"
        );
        $this->_id_field_name = 'instance_type_id';
        $this->_long_name_field_name = 'display';
        $this->_short_name_field_name = 'instance_type';
        $this->_order_id_field_name = 'instance_type_id';
        $this->modw_schema = new Schema('modw_cloud');
        $this->configuration_table = new Table($this->modw_schema, 'instance_type', 'p');
    }

    public function applyTo(Query &$query, Table $data_table, $multi_group = false)
    {
        $query->addTable($this->configuration_table);

        $configurationtable_id_field = new TableField($this->configuration_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $configuration_name_field = new TableField($this->configuration_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $configuration_shortname_field = new TableField($this->configuration_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new TableField($this->configuration_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($configurationtable_id_field);
        $query->addField($configuration_name_field);
        $query->addField($configuration_shortname_field);

        $query->addGroup($configurationtable_id_field);

        $datatable_configuration_id_field = new TableField($data_table, 'instance_type_id');
        $query->addWhereCondition(new WhereCondition($configurationtable_id_field, '=', $datatable_configuration_id_field));
        $query->addWhereCondition(new WhereCondition(new TableField($this->configuration_table, 'resource_id'), '=', new TableField($data_table, 'host_resource_id')));

        $this->addOrder($query, $multi_group);
    }

    public function addWhereJoin(Query &$query, Table $data_table, $multi_group = false, $operation, $whereConstraint) // phpcs:ignore
    {
        // construct the join between the main data_table and this group by table
        $query->addTable($this->configuration_table);

        $configurationtable_id_field = new TableField($this->configuration_table, $this->_id_field_name);
        $datatable_configuration_id_field = new TableField($data_table, 'instance_type_id');

        // the where condition that specifies the join of the tables
        $query->addWhereCondition(new WhereCondition($configurationtable_id_field, '=', $datatable_configuration_id_field));
        $query->addWhereCondition(new WhereCondition(new TableField($this->configuration_table, 'resource_id'), '=', new TableField($data_table, 'host_resource_id')));
        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint="(". implode(",", $whereConstraint) .")";
        }

        $query->addWhereCondition(
            new WhereCondition(
                $configurationtable_id_field,
                $operation,
                $whereConstraint
            )
        );
    }

    public function addOrder(
        Query &$query,
        $multi_group = false,
        $dir = 'asc',
        $prepend = false
    ) {
        $orderField = new OrderBy(new TableField($this->configuration_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'instance_type_id');
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            "select long_name as field_label from modw_cloud.instance_type where id in (_filter_) order by instance_type_id"
        );
    }
}

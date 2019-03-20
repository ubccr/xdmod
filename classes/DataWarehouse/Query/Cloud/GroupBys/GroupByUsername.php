<?php

namespace DataWarehouse\Query\Cloud\GroupBys;

/*
* @author Greg Dean
* @date 2019-01-27
*
* class for adding group by system username to a query
*
*/

class GroupByUsername extends \DataWarehouse\Query\Cloud\GroupBy
{
    public static function getLabel()
    {
        return 'System Username';
    }

    public function getInfo()
    {
        return "The specific system username associated with a running session of a virtual machine.";
    }
    public function __construct()
    {
        parent::__construct(
            'username',
            array(),
            "select distinct
                gt.username as id,
                gt.username as short_name,
                gt.username as long_name
                from systemaccount gt
                where 1
                order by gt.username",
            array()
        );
        $this->_id_field_name = 'username';
        $this->_short_name_field_name = 'username';
        $this->_long_name_field_name = 'username';
        $this->_order_id_field_name = 'username';
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->systemaccount_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'systemaccount', 'sa');
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->systemaccount_table);

        $systemaccounttable_id_field = new \DataWarehouse\Query\Model\TableField($this->systemaccount_table, 'id');
        $datatable_systemaccount_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'systemaccount_id');

        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $systemaccounttable_id_field,
            '=',
            $datatable_systemaccount_id_field
        ));

        $id_field = new \DataWarehouse\Query\Model\TableField($this->systemaccount_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $name_field =  new \DataWarehouse\Query\Model\TableField($this->systemaccount_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $shortname_field =  new \DataWarehouse\Query\Model\TableField($this->systemaccount_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->systemaccount_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($id_field);
        $query->addField($name_field);
        $query->addField($shortname_field);

        $query->addGroup($id_field);

        $this->addOrder($query, $multi_group);
    }

    public function addWhereJoin(
        \DataWarehouse\Query\Query &$query,
        \DataWarehouse\Query\Model\Table $data_table,
        $multi_group,
        $operation,
        $whereConstraint
    ) {
        // construct the join between the main data_table and this group by table
        $query->addTable($this->systemaccount_table);

        $systemaccounttable_id_field = new \DataWarehouse\Query\Model\TableField($this->systemaccount_table, 'id');
        $datatable_systemaccount_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'systemaccount_id');

        // the where condition that specifies the join of the tables
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $systemaccounttable_id_field,
            '=',
            $datatable_systemaccount_id_field
        ));

        // the where condition that specifies the constraint on the joined table
        // note that the where condition applies to strings
        if (is_array($whereConstraint)) {
            $whereConstraint="('". implode("','", $whereConstraint) ."')";
        }

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $systemaccounttable_id_field,
                $operation,
                $whereConstraint
            )
        );
    } // addWhereJoin

    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->systemaccount_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, 'select id from modw.systemaccount where username in (_filter_)', 'systemaccount_id');
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            "select distinct username as field_label from modw.systemaccount  where username in (_filter_) order by username"
        );
    }
}

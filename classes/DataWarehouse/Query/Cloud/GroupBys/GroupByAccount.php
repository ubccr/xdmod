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
* Group By Account
*/

class GroupByAccount extends \DataWarehouse\Query\Cloud\GroupBy
{
    public static function getLabel()
    {
        return 'Account';
    }

    public function getInfo()
    {
        return "The account of the principal investigator associated with a VM instance.";
    }

    public function __construct()
    {
        parent::__construct(
            'account',
            array(),
            "SELECT distinct
                gt.id,
                gt.short_name as short_name,
                gt.long_name as long_name
            FROM account gt
            where 1
            order by gt.order_id
        "
        );
        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'long_name';
        $this->_short_name_field_name = 'short_name';
        $this->_order_id_field_name = 'order_id';
        $this->modw_schema = new Schema('modw');
        $this->account_table = new Table($this->modw_schema, 'account', 'acc');
    }

    public function applyTo(Query &$query, Table $data_table, $multi_group = false)
    {
        $query->addTable($this->account_table);

        $accounttable_id_field = new TableField($this->account_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $account_name_field = new TableField($this->account_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $account_shortname_field = new TableField($this->account_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new TableField($this->account_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($accounttable_id_field);
        $query->addField($account_name_field);
        $query->addField($account_shortname_field);

        $query->addGroup($accounttable_id_field);

        $datatable_account_id_field = new TableField($data_table, 'account_id');
        $query->addWhereCondition(new WhereCondition($accounttable_id_field, '=', $datatable_account_id_field));

        $this->addOrder($query, $multi_group);
    }

    public function addWhereJoin(Query &$query, Table $data_table, $multi_group = false, $operation, $whereConstraint)
    {
        // construct the join between the main data_table and this group by table
        $query->addTable($this->account_table);

        $accounttable_id_field = new TableField($this->account_table, $this->_id_field_name);
        $datatable_account_id_field = new TableField($data_table, 'account_id');

        // the where condition that specifies the join of the tables
        $query->addWhereCondition(new WhereCondition(
            $accounttable_id_field,
                                                    '=',
                                                    $datatable_account_id_field
                                                    ));
        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint="(". implode(",", $whereConstraint) .")";
        }

        $query->addWhereCondition(
            new WhereCondition(
                $accounttable_id_field,
                $operation,
                $whereConstraint
            )
        );
    }

    public function addOrder(Query &$query, $multi_group = false, $dir = 'asc',
        $prepend = false)
    {
        $orderField = new OrderBy(new TableField($this->account_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'account_id');
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
                $request,
                            "select long_name as field_label from modw.account  where id in (_filter_) order by order_id"
            );
    }
}

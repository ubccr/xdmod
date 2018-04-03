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
* @date 03/27/2018
*
* Group By Memory Usage
*/

class GroupByMemoryUsage extends \DataWarehouse\Query\Cloud\GroupBy
{
    public static function getLabel()
    {
        return 'Memory Usage';
    }

    public function getInfo()
    {
        return "The amount of memory utilized by a VM.";
    }

    public function __construct()
    {
        parent::__construct(
            'memory_buckets',
            array(),
            "SELECT distinct
                gt.id,
                gt.min_memory as short_name,
                gt.description as long_name,
                gt.min_memory as order_id
            FROM memory_buckets gt
            where 1
            order by order_id"
        );
        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'description';
        $this->_short_name_field_name = 'min_memory';
        $this->_order_id_field_name = 'min_memory';
        $this->modw_schema = new Schema('modw');
        $this->bucket_table = new Table($this->modw_schema, 'memory_buckets', 'p');
    }

    public function applyTo(Query &$query, Table $data_table, $multi_group = false)
    {
        $query->addTable($this->bucket_table);

        $buckettable_id_field = new TableField($this->bucket_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $bucket_name_field = new TableField($this->bucket_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $bucket_shortname_field = new TableField($this->bucket_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new TableField($this->bucket_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($buckettable_id_field);
        $query->addField($bucket_name_field);
        $query->addField($bucket_shortname_field);

        $query->addGroup($buckettable_id_field);

        $datatable_bucket_id_field = new TableField($data_table, 'min_memory');
        $query->addWhereCondition(new WhereCondition($buckettable_id_field, '=', $datatable_bucket_id_field));

        $this->addOrder($query, $multi_group);
    }

    public function addWhereJoin(Query &$query, Table $data_table, $multi_group = false, $operation, $whereConstraint)
    {
        // construct the join between the main data_table and this group by table
        $query->addTable($this->bucket_table);

        $buckettable_id_field = new TableField($this->bucket_table, $this->_id_field_name);
        $datatable_bucket_id_field = new TableField($data_table, 'min_memory');

        // the where condition that specifies the join of the tables
        $query->addWhereCondition(new WhereCondition($buckettable_id_field, '=', $datatable_bucket_id_field));
        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint="(". implode(",", $whereConstraint) .")";
        }

        $query->addWhereCondition(
            new WhereCondition(
                $buckettable_id_field,
                $operation,
                $whereConstraint
            )
        );
    }

    public function addOrder(Query &$query, $multi_group = false, $dir = 'asc',
        $prepend = false)
    {
        $orderField = new OrderBy(new TableField($this->bucket_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'min_memory');
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
                $request,
                            "select long_name as field_label from modw.memory_buckets where id in (_filter_) order by min_memory"
            );
    }

}

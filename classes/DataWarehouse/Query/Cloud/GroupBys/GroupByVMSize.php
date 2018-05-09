<?php

namespace DataWarehouse\Query\Cloud\GroupBys;

/*
* @author Rudra Chakraborty
* @date 03/28/2018
*
* Group By VM Size
*/

class GroupByVMSize extends \DataWarehouse\Query\Cloud\GroupBy
{
    public function __construct()
    {
        parent::__construct(
            'vm_size',
            array(),
            "
			select
				gt.id,
				gt.description as short_name,
				gt.description as long_name
			from processor_buckets gt
			where 1
			order by gt.id
		"
        );

        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'description';
        $this->_short_name_field_name = 'description';
        $this->_order_id_field_name = 'id';
        $this->setOrderByStat(null);
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->processor_buckets_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'processor_buckets', 'pb');
    }

    public function getInfo()
    {
        return     "A categorization of virtual machines into discrete groups based on the number of cores used by each VM.";
    }

    public static function getLabel()
    {
        return 'VM Size: Cores';
    }

    public function getDefaultDatasetType()
    {
        return 'aggregate';
    }

    public function getDefaultDisplayType($dataset_type = null)
    {
        if ($dataset_type == 'timeseries') {
            return 'area';
        } else {
            return 'bar';
        }
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->processor_buckets_table);

        $processor_buckets_id_field = new \DataWarehouse\Query\Model\TableField($this->processor_buckets_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $processor_buckets_description_field = new \DataWarehouse\Query\Model\TableField($this->processor_buckets_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $processor_buckets_shortname_field = new \DataWarehouse\Query\Model\TableField($this->processor_buckets_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->processor_buckets_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($processor_buckets_id_field);
        $query->addField($processor_buckets_description_field);
        $query->addField($processor_buckets_shortname_field);

        $query->addGroup($processor_buckets_id_field);

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                new \DataWarehouse\Query\Model\TableField($data_table, 'processorbucket_id'),
                '=',
                new \DataWarehouse\Query\Model\TableField($this->processor_buckets_table, 'id')
            )
        );

        $this->addOrder($query, $multi_group, 'asc', true);
    }

    public function addWhereJoin(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false, $operation, $whereConstraint)
    {
        $query->addTable($this->processor_buckets_table);

        $processor_buckets_id_field = new \DataWarehouse\Query\Model\TableField($this->processor_buckets_table, $this->_id_field_name);

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $processor_buckets_id_field,
                '=',
                new \DataWarehouse\Query\Model\TableField($data_table, 'processorbucket_id')
            )
        );
        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint="(". implode(",", $whereConstraint) .")";
        }

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $processor_buckets_id_field,
                $operation,
                $whereConstraint
            )
        );
    } // addWhereJoin()

    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->processor_buckets_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'processorbucket_id');
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            "select description as field_label from modw.processor_buckets  where id in (_filter_) order by id"
        );
    }
}

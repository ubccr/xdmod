<?php

namespace DataWarehouse\Query\Jobs\GroupBys;

use DataWarehouse\Query\Jobs\GroupBy;
use DataWarehouse\Query\Model\OrderBy;
use DataWarehouse\Query\Model\Schema;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Model\WhereCondition;
use DataWarehouse\Query\Query;

class GroupByGPUCount extends GroupBy
{
    public function __construct()
    {
        parent::__construct(
            'gpucount',
            [],
            'SELECT
                gt.id,
                gt.description AS short_name,
                gt.description AS long_name
            FROM gpu_buckets gt
            WHERE 1
            ORDER BY gt.id'
        );

        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'description';
        $this->_short_name_field_name = 'description';
        $this->_order_id_field_name = 'id';
        $this->setOrderByStat(null);
        $this->modw_schema = new Schema('modw');
        $this->gpu_buckets_table = new Table(
            $this->modw_schema,
            'gpu_buckets',
            'gb'
        );
    }

    public function getInfo()
    {
        return  'A categorization of jobs into discrete groups based on the number of GPUs used by each job.';
    }

    public static function getLabel()
    {
        return 'GPU Count';
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

    public function applyTo(
        Query &$query,
        Table $data_table,
        $multi_group = false
    ) {
        $query->addTable($this->gpu_buckets_table);

        $gpu_buckets_id_field = new TableField(
            $this->gpu_buckets_table,
            $this->_id_field_name,
            $this->getIdColumnName($multi_group)
        );

        $gpu_buckets_description_field = new TableField(
            $this->gpu_buckets_table,
            $this->_long_name_field_name,
            $this->getLongNameColumnName($multi_group)
        );

        $gpu_buckets_shortname_field = new TableField(
            $this->gpu_buckets_table,
            $this->_short_name_field_name,
            $this->getShortNameColumnName($multi_group)
        );

        $order_id_field = new TableField(
            $this->gpu_buckets_table,
            $this->_order_id_field_name,
            $this->getOrderIdColumnName($multi_group)
        );

        $query->addField($order_id_field);
        $query->addField($gpu_buckets_id_field);
        $query->addField($gpu_buckets_description_field);
        $query->addField($gpu_buckets_shortname_field);

        $query->addGroup($gpu_buckets_id_field);

        $query->addWhereCondition(new WhereCondition(
            new TableField($data_table, 'gpubucket_id'),
            '=',
            new TableField($this->gpu_buckets_table, 'id')
        ));

        $this->addOrder($query, $multi_group, 'asc', true);
    }

    public function addWhereJoin(
        Query &$query,
        Table $data_table,
        $multi_group,
        $operation,
        $whereConstraint
    ) {
        $query->addTable($this->gpu_buckets_table);

        $gpu_buckets_id_field = new TableField(
            $this->gpu_buckets_table,
            $this->_id_field_name
        );

        $query->addWhereCondition(new WhereCondition(
            $gpu_buckets_id_field,
            '=',
            new TableField($data_table, 'gpubucket_id')
        ));

        if (is_array($whereConstraint)) {
            $whereConstraint = '(' . implode(',', $whereConstraint) . ')';
        }

        $query->addWhereCondition(
            new WhereCondition(
                $gpu_buckets_id_field,
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
        $orderField = new OrderBy(
            new TableField(
                $this->gpu_buckets_table,
                $this->_order_id_field_name
            ),
            $dir,
            $this->getName()
        );

        if ($prepend) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2(
            $request,
            '_filter_',
            'gpubucket_id'
        );
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            'SELECT description AS field_label FROM modw.gpu_buckets WHERE id IN (_filter_) ORDER BY id'
        );
    }
}

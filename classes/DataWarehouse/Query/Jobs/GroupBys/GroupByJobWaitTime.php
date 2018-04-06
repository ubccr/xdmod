<?php

namespace DataWarehouse\Query\Jobs\GroupBys;

/**
 * @author Trey Dockendorf
 * @date 2018-Apr-17
 *
 * class for adding group by job wait time (wait duration) to a query
 *
 */
class GroupByJobWaitTime extends \DataWarehouse\Query\Jobs\GroupBy
{
    public function __construct()
    {
        parent::__construct(
            'jobwaittime',
            array('avg_waitduration_hours','sem_avg_waitduration_hours'),
            'SELECT
                gt.id,
                gt.description AS short_name,
                gt.description AS long_name
            FROM
                job_wait_times gt
            WHERE 1
            ORDER BY gt.id'
        );

        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'description';
        $this->_short_name_field_name = 'description';
        $this->_order_id_field_name = 'id';
        $this->setOrderByStat(null);
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->job_wait_times_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'job_wait_times', 'jt');
    }
    public function getInfo()
    {
        return     "A categorization of jobs into discrete groups based on the total linear time each job waited.";
    }
    public static function getLabel()
    {
        return  'Job Wait Time';
    }

    public function getDefaultDatasetType()
    {
        return 'aggregate';
    }
    public function getDefaultDisplayType($dataset_type = null)
    {
        if($dataset_type == 'timeseries') {
            return 'area';
        }
        else
        {
            return 'bar';
        }
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->job_wait_times_table);

        $job_wait_times_id_field = new \DataWarehouse\Query\Model\TableField($this->job_wait_times_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $job_wait_times_description_field = new \DataWarehouse\Query\Model\TableField($this->job_wait_times_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $job_wait_times_shortname_field = new \DataWarehouse\Query\Model\TableField($this->job_wait_times_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->job_wait_times_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($job_wait_times_id_field);
        $query->addField($job_wait_times_description_field);
        $query->addField($job_wait_times_shortname_field);

        $query->addGroup($job_wait_times_id_field);

        $datatable_jobwaittime_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'job_wait_time_bucket_id');
        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $datatable_jobwaittime_id_field,
                '=',
                $job_wait_times_id_field
            )
        );

        $this->addOrder($query, $multi_group, 'asc', true);
    }

    public function addWhereJoin(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false, $operation, $whereConstraint)
    {
        $query->addTable($this->job_wait_times_table);

        $job_wait_times_id_field = new \DataWarehouse\Query\Model\TableField($this->job_wait_times_table, $this->_id_field_name);
        $datatable_jobwaittime_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'job_wait_time_bucket_id');

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $datatable_jobwaittime_id_field,
                '=',
                $job_wait_times_id_field
            )
        );

        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint="(". implode(",", $whereConstraint) .")";
        }

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $job_wait_times_id_field,
                $operation,
                $whereConstraint
            )
        );
    } // addWhereJoin()

    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->job_wait_times_table, $this->_order_id_field_name), $dir, $this->getName());
        if($prepend === true) {
            $query->prependOrder($orderField);
        }else
        {
            $query->addOrder($orderField);
        }
    }
    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'job_wait_time_bucket_id');
    }
    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            "select description as field_label from modw.job_wait_times  where id in (_filter_) order by id"
        );
    }
}

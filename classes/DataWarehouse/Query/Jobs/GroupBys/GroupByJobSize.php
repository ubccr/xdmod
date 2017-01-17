<?php

namespace DataWarehouse\Query\Jobs\GroupBys;

/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* class for adding group by job size (processor bucket) to a query
* 
*/
class GroupByJobSize extends \DataWarehouse\Query\Jobs\GroupBy
{
   

    public function __construct()
    {
        parent::__construct(
            'jobsize',
            array('avg_waitduration_hours','sem_avg_waitduration_hours'),
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
        return  "A categorization of jobs into discrete groups based on the number of cores used by each job.";
    }
    public static function getLabel()
    {
         return 'Job Size';
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
                                
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            new \DataWarehouse\Query\Model\TableField($data_table, 'processorbucket_id'),
            '=',
            new \DataWarehouse\Query\Model\TableField($this->processor_buckets_table, 'id')
        ));
                                                    
        $this->addOrder($query, $multi_group, 'asc', true);
    }

    public function addWhereJoin(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false, $operation, $whereConstraint)
    {
        $query->addTable($this->processor_buckets_table);

        $processor_buckets_id_field = new \DataWarehouse\Query\Model\TableField($this->processor_buckets_table, $this->_id_field_name);

        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $processor_buckets_id_field,
            '=',
            new \DataWarehouse\Query\Model\TableField($data_table, 'processorbucket_id')
        ));
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
        
        /*$parameters = array();
		if(isset($request[$this->getName().'_filter']) && $request[$this->getName().'_filter'] != '')
		{
			$filterString = $request[$this->getName().'_filter'];
			
			$filterItems = explode(',',$filterString);
			
			if(isset($request[$this->getName()])) 
			{
				$filterItems[] = $request[$this->getName()];
			}
			
			if(count($filterItems) > 0) $parameters[] = new \DataWarehouse\Query\Model\Parameter('processorbucket_id', 'in', "(".implode(',',$filterItems).")");		
		}
		else
		if(isset($request[$this->getName()]))
		{
			$parameters[] = new \DataWarehouse\Query\Model\Parameter('processorbucket_id', '=', $request[$this->getName()]);	
		}
		return $parameters;*/
    }
    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            "select description as field_label from modw.processor_buckets  where id in (_filter_) order by id"
        );
        /*
		$parameters = array();
		if(isset($request[$this->getName()]))
		{
			$fieldLabelQuery = "select description as field_label from modw.processor_buckets  where id=".$request[$this->getName()];
			$fieldLabelResults = \DataWarehouse::connect()->query($fieldLabelQuery);
		
			foreach($fieldLabelResults as $fieldLabelResult)
			{
				$parameters[] = $this->getLabel().' = '.$fieldLabelResult['field_label'];
				break;
			}
		}
		return $parameters;*/
    }
}

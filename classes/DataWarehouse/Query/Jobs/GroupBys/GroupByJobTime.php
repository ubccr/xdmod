<?php

namespace DataWarehouse\Query\Jobs\GroupBys;

/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* class for adding group by job time (wall duration) to a query
* 
*/
class GroupByJobTime extends \DataWarehouse\Query\Jobs\GroupBy
{	public function __construct()
	{
		parent::__construct('jobwalltime', array('avg_waitduration_hours','sem_avg_waitduration_hours'), 
		"
		select 
			gt.id, 
			gt.description as short_name, 
			gt.description as long_name 
		from job_times gt
		where 1
		order by gt.id
		");
		
		$this->_id_field_name = 'id';
		$this->_long_name_field_name = 'description';
		$this->_short_name_field_name = 'description';
		$this->_order_id_field_name = 'id';
		$this->setOrderByStat(NULL);
		$this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
		$this->job_times_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'job_times', 'jt');
	}	
	public function getInfo() 
	{
		return 	"A categorization of jobs into discrete groups based on the total linear time each job took to execute.";
	}
	public static function getLabel()
	{
		return  'Job Wall Time';	
	}

	public function getDefaultDatasetType()
	{
		return 'aggregate';
	}
	public function getDefaultDisplayType($dataset_type = NULL)
	{
		if($dataset_type == 'timeseries')
		{
			return 'area';
		}
		else
		{
			return 'bar';
		}
	}
	
	public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
	{
		$query->addTable($this->job_times_table);
		
		$job_times_id_field = new \DataWarehouse\Query\Model\TableField($this->job_times_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
		$job_times_description_field = new \DataWarehouse\Query\Model\TableField($this->job_times_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
		$job_times_shortname_field = new \DataWarehouse\Query\Model\TableField($this->job_times_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
		$order_id_field = new \DataWarehouse\Query\Model\TableField($this->job_times_table,$this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));
		
		$query->addField($order_id_field);
		$query->addField($job_times_id_field);
		$query->addField($job_times_description_field);	
		$query->addField($job_times_shortname_field);	
		
		$query->addGroup($job_times_id_field);	
		
		$datatable_jobtime_id_field = new \DataWarehouse\Query\Model\TableField($data_table,'jobtime_id');
		$query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($datatable_jobtime_id_field,
													'=',
													$job_times_id_field
													));		
																						
		$this->addOrder($query,$multi_group, 'asc', true);
	}

    public function addWhereJoin(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false, $operation, $whereConstraint)
    {
        $query->addTable($this->job_times_table);

        $job_times_id_field = new \DataWarehouse\Query\Model\TableField($this->job_times_table, $this->_id_field_name);
        $datatable_jobtime_id_field = new \DataWarehouse\Query\Model\TableField($data_table,'jobtime_id');

        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($datatable_jobtime_id_field,
                                                    '=',
                                                    $job_times_id_field
                                                    ));

        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) $whereConstraint="(". implode(",",$whereConstraint) .")";

        $query->addWhereCondition(
         new \DataWarehouse\Query\Model\WhereCondition(
            $job_times_id_field,
            $operation,
            $whereConstraint
         )
        );
    } // addWhereJoin()

	public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
	{
		$orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->job_times_table, $this->_order_id_field_name),$dir, $this->getName());
		if($prepend === true)
		{
			$query->prependOrder($orderField);
		}else
		{
			$query->addOrder($orderField);
		}
	}
	public function pullQueryParameters(&$request)
	{
		return parent::pullQueryParameters2($request, '_filter_', 'jobtime_id');
		/*
		$parameters = array();
		if(isset($request[$this->getName().'_filter']) && $request[$this->getName().'_filter'] != '')
		{
			$filterString = $request[$this->getName().'_filter'];
			
			$filterItems = explode(',',$filterString);
			
			if(isset($request[$this->getName()])) 
			{
				$filterItems[] = $request[$this->getName()];
			}
			
			if(count($filterItems) > 0) $parameters[] = new \DataWarehouse\Query\Model\Parameter('jobtime_id', 'in', "(".implode(',',$filterItems).")");		
		}
		else
		if(isset($request[$this->getName()]))
		{
			$parameters[] = new \DataWarehouse\Query\Model\Parameter('jobtime_id', '=', $request[$this->getName()]);	
		}
		return $parameters;*/
	}
	public function pullQueryParameterDescriptions(&$request)
	{
		return parent::pullQueryParameterDescriptions2($request, 
							"select description as field_label from modw.job_times  where id in (_filter_) order by id");
		/*
		$parameters = array();
		if(isset($request[$this->getName()]))
		{
			$fieldLabelQuery = "select description as field_label from modw.job_times  where id=".$request[$this->getName()];
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
?>

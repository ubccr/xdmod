<?php

namespace DataWarehouse\Query\Jobs\GroupBys;

/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* class for adding group by allocation to a query
* 
*/

class GroupByAllocation extends \DataWarehouse\Query\Jobs\GroupBy
{
	public static function getLabel()
	{
		 return  'Allocation';
	}

	public function getInfo() 
	{
		return 	"A funded project that is allowed to run jobs on resources.";
	}
	public function __construct()
	{
		parent::__construct('allocation', 
							array(),
							"select distinct 
								gt.account_id as id, 
								gt.short_name, 
								gt.long_name 
							from allocation gt 
							where 1
							group by gt.long_name
							order by gt.order_id",
							array('person')
							);
		$this->_id_field_name = 'account_id';
		$this->_long_name_field_name = 'long_name';
		$this->_short_name_field_name = 'short_name';	
		 $this->_order_id_field_name = 'order_id';
		$this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
		$this->allocation_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'allocation', 'al');
	}
	
	public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
	{		
		$query->addTable($this->allocation_table);		
	
		$id_field = new \DataWarehouse\Query\Model\TableField($this->allocation_table,$this->_id_field_name, $this->getIdColumnName($multi_group));
		$name_field = new \DataWarehouse\Query\Model\TableField($this->allocation_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
		$shortname_field = new \DataWarehouse\Query\Model\TableField($this->allocation_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
		$order_id_field = new \DataWarehouse\Query\Model\TableField($this->allocation_table,$this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));
		
		$query->addField($order_id_field);
		$query->addField($id_field);	
		$query->addField($name_field);
		$query->addField($shortname_field);		
		
		$query->addGroup(new \DataWarehouse\Query\Model\TableField($this->allocation_table,$this->_id_field_name));
		
		$datatable_allocation_id_field = new \DataWarehouse\Query\Model\TableField($data_table,'allocation_id');
		$query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($datatable_allocation_id_field,
													'=',
													new \DataWarehouse\Query\Model\TableField($this->allocation_table,'id')
													));

		$this->addOrder($query,$multi_group,'asc',false);
	}
	
    // JMS: add join with where clause, October 2015
    public function addWhereJoin(\DataWarehouse\Query\Query &$query, 
                                 \DataWarehouse\Query\Model\Table $data_table, 
                                 $multi_group = false,
                                 $operation,
                                 $whereConstraint)
    {
        // construct the join between the main data_table and this group by table
        $query->addTable($this->allocation_table);

        $id_field = new \DataWarehouse\Query\Model\TableField($this->allocation_table,'id');
        $datatable_allocation_id_field = new \DataWarehouse\Query\Model\TableField($data_table,'allocation_id');

        // construct the join between the main data_table and this group by table
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($datatable_allocation_id_field,
                                                    '=',
                                                    $id_field
                                                    ));
        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) $whereConstraint="(". implode(",",$whereConstraint) .")";

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $id_field,
                $operation,
                $whereConstraint
            )
        );
    } // addWhereJoin

	public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
	{
		$orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->allocation_table,$this->_order_id_field_name),$dir, $this->getName());
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
		return parent::pullQueryParameters2($request, 
							"select distinct id from modw.allocation where account_id in (_filter_)",
							"allocation_id");
		/*$parameters = array();
		if(isset($request[$this->getName().'_filter']) && $request[$this->getName().'_filter'] != '')
		{
			$filterString = $request[$this->getName().'_filter'];
			
			$filterItems = explode(',',$filterString);
			
			if(isset($request[$this->getName()])) 
			{
				$filterItems[] = $request[$this->getName()];
			}
			
			if(count($filterItems) > 0) $parameters[] = new \DataWarehouse\Query\Model\Parameter('allocation_id', 'in', "(select distinct id from modw.allocation where account_id in "."(".implode(",",$filterItems)."))");		
		}
		else
		if(isset($request[$this->getName()]))
		{
			$parameters[] = new \DataWarehouse\Query\Model\Parameter('allocation_id', 'in', "(select distinct id from modw.allocation where account_id=".$request[$this->getName()].")");		
			
		}
		return $parameters;*/
	}
	public function pullQueryParameterDescriptions(&$request)
	{
		return parent::pullQueryParameterDescriptions2($request, 
							"select distinct long_name as field_label from modw.allocation where account_id in (_filter_) group by long_name order by order_id");
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
			
			if(count($filterItems) > 0)
			{
				$fieldLabelQuery = "select distinct long_name as field_label from modw.allocation where account_id in "."(".implode(",",$filterItems).") order by order_id";
				$fieldLabelResults = \DataWarehouse::connect()->query($fieldLabelQuery);
		
				$parameter = $this->getLabel().'s = (';
				foreach($fieldLabelResults as $fieldLabelResult)
				{
					$parameter .= ' '.$fieldLabelResult['field_label'].','; 
				} 
				$parameter = substr($parameter,0,-1);
				$parameters[] = $parameter.' )';
			}
		}
		else
		if(isset($request[$this->getName()]))
		{
			$fieldLabelQuery = "select long_name as field_label from modw.allocation where account_id=".$request[$this->getName()]."";
			$fieldLabelResults = \DataWarehouse::connect()->query($fieldLabelQuery);
		
			foreach($fieldLabelResults as $fieldLabelResult)
			{
				$parameters[] = $this->getLabel().' = '.$fieldLabelResult['field_label'];
				break;
			}
		}
		return $parameters;*/
	}
	
	
	public function getPossibleValues($hint = NULL, $limit = NULL, $offset = NULL, array $parameters = array())
	{
		if($this->_possible_values_query == NULL)
		{
			return array();
		}
		
		$possible_values_query = $this->_possible_values_query;
		
		foreach($parameters as $pname => $pvalue)
		{
			if($pname == 'person')
			{
				$possible_values_query = str_ireplace('from ', "from modw.peopleonaccount poa, ",$possible_values_query);
				$possible_values_query = str_ireplace('where ', "where poa.person_id = $pvalue and gt.account_id = poa.account_id  and ",$possible_values_query);
			}else
			if($pname == 'provider')
			{
				$possible_values_query = str_ireplace('from ', "from modw.resourcefact rf, modw.allocationonresource alor, ",$possible_values_query);
				$possible_values_query = str_ireplace('where ', "where rf.organization_id = $pvalue and gt.id = alor.allocation_id and rf.id = alor.resource_id and ",$possible_values_query);
			}else
			if($pname == 'institution')
			{
			$possible_values_query = str_ireplace('from ', "from modw.person p,  modw.peopleonaccount poa, ",$possible_values_query);
				$possible_values_query = str_ireplace('where ', "where p.organization_id = $pvalue and gt.account_id = poa.account_id and p.id = poa.person_id  and ",$possible_values_query);
			}else
			if($pname == 'pi')
			{
				$possible_values_query = str_ireplace('from ', "from modw.peopleunderpi pup, modw.peopleonaccount poa, ",$possible_values_query);
				$possible_values_query = str_ireplace('where ', "where pup.principalinvestigator_person_id = $pvalue and gt.account_id = poa.account_id and pup.person_id = poa.person_id  and ",$possible_values_query);
			}
		}
		
		return parent::getPossibleValues($hint,$limit,$offset,$parameters,$possible_values_query);
	}
}
?>

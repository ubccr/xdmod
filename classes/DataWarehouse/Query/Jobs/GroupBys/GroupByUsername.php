<?php

namespace DataWarehouse\Query\Jobs\GroupBys;

/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* class for adding group by system username to a query
* 
*/

class GroupByUsername extends \DataWarehouse\Query\Jobs\GroupBy
{
	public static function getLabel()
	{
		 return 'System Username';
	}

	public function getInfo() 
	{
		return 	"The specific system username of the users who ran jobs.";
	}
	public function __construct()
	{
		parent::__construct('username', 
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
		
		$query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($systemaccounttable_id_field,
												'=',
												$datatable_systemaccount_id_field
												));		
		
		$id_field = new \DataWarehouse\Query\Model\TableField($this->systemaccount_table,$this->_id_field_name, $this->getIdColumnName($multi_group));
		$name_field =  new \DataWarehouse\Query\Model\TableField($this->systemaccount_table,$this->_long_name_field_name , $this->getLongNameColumnName($multi_group));
		$shortname_field =  new \DataWarehouse\Query\Model\TableField($this->systemaccount_table,$this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
		$order_id_field = new \DataWarehouse\Query\Model\TableField($this->systemaccount_table,$this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));
		
		$query->addField($order_id_field);
		$query->addField($id_field);
		$query->addField($name_field);
		$query->addField($shortname_field);
		
		$query->addGroup($id_field);
		
		$this->addOrder($query,$multi_group);
	}
	
    // JMS: add join with where clause, October 2015
    public function addWhereJoin(\DataWarehouse\Query\Query &$query, 
                                 \DataWarehouse\Query\Model\Table $data_table, 
                                 $multi_group = false,
                                 $operation,
                                 $whereConstraint)
    {
        // construct the join between the main data_table and this group by table
        $query->addTable($this->systemaccount_table);

        $systemaccounttable_id_field = new \DataWarehouse\Query\Model\TableField($this->systemaccount_table, 'id');
        $datatable_systemaccount_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'systemaccount_id');

        // the where condition that specifies the join of the tables
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($systemaccounttable_id_field,
                                                '=',
                                                $datatable_systemaccount_id_field
                                                ));

        // the where condition that specifies the constraint on the joined table
        // note that the where condition applies to strings
        if (is_array($whereConstraint)) $whereConstraint="('". implode("','",$whereConstraint) ."')";

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
		$orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->systemaccount_table,$this->_order_id_field_name), $dir, $this->getName());
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
		return parent::pullQueryParameters2($request, 'select id from modw.systemaccount where username in (_filter_)', 'systemaccount_id');
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
			
			if(count($filterItems) > 0) $parameters[] = new \DataWarehouse\Query\Model\Parameter('systemaccount_id', 'in',  "(select id from modw.systemaccount where username in "."('".implode("','",$filterItems)."')".")"  );		
		}
		else
		if(isset($request[$this->getName()]))
		{
			$parameters[] = new \DataWarehouse\Query\Model\Parameter('systemaccount_id', 'in', "(select id from modw.systemaccount where username = '".$request[$this->getName()]."')");		
		}
		return $parameters;*/
	}
	public function pullQueryParameterDescriptions(&$request)
	{
		return parent::pullQueryParameterDescriptions2($request, 
							"select distinct username as field_label from modw.systemaccount  where username in (_filter_) order by username");
							/*
		$parameters = array();
		if(isset($request[$this->getName()]))
		{
			$parameters[] = $this->getLabel().' = '.$request[$this->getName()];
		}
		return $parameters;*/
	}
	
	public function getPossibleValues($hint = null, $limit = null, $offset = null, array $parameters = array(), $base_query = null, $filter = null)
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
				$possible_values_query = str_ireplace('where ', "where gt.person_id = $pvalue and ",$possible_values_query);
			}else
			if($pname == 'provider')
			{
				$possible_values_query = str_ireplace('from ', "from modw.resourcefact rf, ",$possible_values_query);
				$possible_values_query = str_ireplace('where ', "where rf.organization_id = $pvalue and gt.resource_id = rf.id  and ",$possible_values_query); 
			}else
			if($pname == 'institution')
			{
				$possible_values_query = str_ireplace('from ', "from modw.person p, ",$possible_values_query);
				$possible_values_query = str_ireplace('where ', "where p.organization_id = $pvalue and gt.person_id = p.id  and ",$possible_values_query);
			}else
			if($pname == 'pi')
			{
				$possible_values_query = str_ireplace('from ', "from modw.peopleunderpi pup, ",$possible_values_query);
				$possible_values_query = str_ireplace('where ', "where pup.principalinvestigator_person_id = $pvalue and gt.person_id = pup.person_id  and ",$possible_values_query);
			}
		}
		
		return parent::getPossibleValues($hint,$limit,$offset,$parameters,$possible_values_query);
	}
}
?>

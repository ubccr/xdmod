<?php

namespace DataWarehouse\Query\Jobs\GroupBys;

/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* class for adding group by nsf status to a query
* 
*/

class GroupByNSFStatus extends \DataWarehouse\Query\Jobs\GroupBy
{	public function __construct()
	{
		parent::__construct('nsfstatus', array(), 
		"select distinct gt.id, 
				gt.code as short_name, 
				gt.name as long_name
				from nsfstatuscode gt
				where 1
				order by gt.name");
		
		$this->_id_field_name = 'id';
		$this->_long_name_field_name = 'name';
		$this->_short_name_field_name = 'name';
		$this->_order_id_field_name = 'name';
		$this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
		$this->person_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'person', 'p'); 
		$this->nsfstatuscode_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'nsfstatuscode', 'ns'); 
	}
	public function getInfo() 
	{
		return 	"Categorization of the users who ran jobs.";
	}
	public static function getLabel()
	{
		 return  'User NSF Status';
	}

	public function getDefaultDatasetType()
	{
		return 'timeseries';
	}

	public function getDefaultCombineMethod()
	{
		return 'stack';
	}
	
	public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
	{
		$query->addTable($this->nsfstatuscode_table);
		
		$nsfstatus_id_field = new \DataWarehouse\Query\Model\TableField($this->nsfstatuscode_table,$this->_id_field_name, $this->getIdColumnName($multi_group));
		$nsfstatus_name_field = new \DataWarehouse\Query\Model\TableField($this->nsfstatuscode_table,$this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
		$nsfstatus_shortname_field = new \DataWarehouse\Query\Model\TableField($this->nsfstatuscode_table,$this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
		$order_id_field = new \DataWarehouse\Query\Model\TableField($this->nsfstatuscode_table,$this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));
		
		$query->addField($order_id_field);
		$query->addField($nsfstatus_id_field);		
		$query->addField($nsfstatus_name_field);
		$query->addField($nsfstatus_shortname_field);

		$query->addGroup($nsfstatus_id_field);
		
		$datatable_person_nsfstatuscode_id_field = new \DataWarehouse\Query\Model\TableField($data_table,'person_nsfstatuscode_id');
		$query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($nsfstatus_id_field,
													'=',
													$datatable_person_nsfstatuscode_id_field
													));			
		$this->addOrder($query,$multi_group);
	}

    public function addWhereJoin(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false, $operation, $whereConstraint)
    {
        $query->addTable($this->nsfstatuscode_table);

        $nsfstatus_id_field = new \DataWarehouse\Query\Model\TableField($this->nsfstatuscode_table,$this->_id_field_name);
        $datatable_person_nsfstatuscode_id_field = new \DataWarehouse\Query\Model\TableField($data_table,'person_nsfstatuscode_id');

        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($nsfstatus_id_field,
                                                    '=',
                                                    $datatable_person_nsfstatuscode_id_field
                                                    ));
        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) $whereConstraint="(". implode(",",$whereConstraint) .")";

        $query->addWhereCondition(
         new \DataWarehouse\Query\Model\WhereCondition(
            $nsfstatus_id_field,
            $operation,
            $whereConstraint
         )
        );
    } // addWhereJoin()


	
	public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
	{
		$orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->nsfstatuscode_table,$this->_order_id_field_name), $dir, $this->getName());
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
		return parent::pullQueryParameters2($request,'_filter_','person_nsfstatuscode_id');
	}
	public function pullQueryParameterDescriptions(&$request)
	{
		return parent::pullQueryParameterDescriptions2($request, 
							"select name as field_label from modw.nsfstatuscode  where id in (_filter_) order by name");
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
				$possible_values_query = str_ireplace('from ', "from modw.person p, ",$possible_values_query);
				$possible_values_query = str_ireplace('where ', "where p.nsfstatuscode_id = gt.id and p.id = $pvalue and ",$possible_values_query);
			}else
			if($pname == 'provider')//find the names all the people that have accounts on the resources at the provider.
			{
				$possible_values_query = str_ireplace('from ', "from modw.person p, modw.systemaccount sa,  modw.resourcefact rf, ",$possible_values_query);
				$possible_values_query = str_ireplace('where ', "where  p.nsfstatuscode_id = gt.id and rf.id = sa.resource_id and rf.organization_id = $pvalue and p.id = sa.person_id  and ",$possible_values_query);
			
			}else
			if($pname == 'institution')
			{
				$possible_values_query = str_ireplace('from ', "from modw.person p, ",$possible_values_query);
				$possible_values_query = str_ireplace('where ', "where p.nsfstatuscode_id = gt.id and p.organization_id = $pvalue and ",$possible_values_query);
			}else
			if($pname == 'pi')
			{
				$possible_values_query = str_ireplace('from ', "from modw.peopleunderpi pup, modw.person p, ",$possible_values_query);
				$possible_values_query = str_ireplace('where ', "where pup.principalinvestigator_person_id = $pvalue and p.id = pup.person_id and gt.id = p.nsfstatuscode_id  and ",$possible_values_query);
			}
		}
		
		return parent::getPossibleValues($hint,$limit,$offset,$parameters,$possible_values_query);
	}
}
?>

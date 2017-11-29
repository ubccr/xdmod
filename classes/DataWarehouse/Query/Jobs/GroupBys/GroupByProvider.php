<?php

namespace DataWarehouse\Query\Jobs\GroupBys;

/*
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* class for adding group by resource provider to a query
*
*/

class GroupByProvider extends \DataWarehouse\Query\Jobs\GroupBy
{
	public static function getLabel()
	{
		 return  'Service Provider';
	}

	public function getInfo()
	{
		return 	"A service provider is an institution that hosts resources.";
	}
	//public function getChartSettings()
	//{
	//	return 'aggregate/bar/auto/y/20/0';
	//}
	public function __construct()
	{
		parent::__construct('provider', array(), "SELECT distinct
								gt.organization_id as id,
								gt.short_name as short_name,
								gt.long_name as long_name
								FROM
								serviceprovider gt
								where 1
								order by gt.order_id", array('resource'));
		$this->_id_field_name = 'organization_id';
		$this->_long_name_field_name = 'short_name';
		$this->_short_name_field_name = 'short_name';
		$this->_order_id_field_name = 'order_id';
		$this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
		$this->organization_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'serviceprovider', 'sp');
	}

	public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
	{
		$query->addTable($this->organization_table);

		$id_field = new \DataWarehouse\Query\Model\TableField($this->organization_table,$this->_id_field_name,$this->getIdColumnName($multi_group));
		$organization_name_field = new \DataWarehouse\Query\Model\TableField($this->organization_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
		$organization_shortname_field = new \DataWarehouse\Query\Model\TableField($this->organization_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
		$order_id_field = new \DataWarehouse\Query\Model\TableField($this->organization_table,$this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

		$query->addField($order_id_field);
		$query->addField($id_field);
		$query->addField($organization_name_field);
		$query->addField($organization_shortname_field);

		$query->addGroup($id_field);

		$datatable_organization_id_field = new \DataWarehouse\Query\Model\TableField($data_table,'resource_organization_id');
		$query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($id_field,
													'=',
													$datatable_organization_id_field
													));

		$this->addOrder($query,$multi_group);

	}

    public function addWhereJoin(\DataWarehouse\Query\Query &$query,
                                 \DataWarehouse\Query\Model\Table $data_table,
                                 $multi_group = false,
                                 $operation,
                                 $whereConstraint)
    {
        // construct the join between the main data_table and this group by table
        $query->addTable($this->organization_table);

        $id_field = new \DataWarehouse\Query\Model\TableField($this->organization_table,$this->_id_field_name);
        $datatable_organization_id_field = new \DataWarehouse\Query\Model\TableField($data_table,'organization_id');

        // the where condition that specifies the join of the tables
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition($id_field,
                                                    '=',
                                                    $datatable_organization_id_field
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
    } // addWhereJoin()

	public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
	{
		$orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->organization_table,$this->_order_id_field_name),$dir, $this->getName());
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
		return parent::pullQueryParameters2($request,'_filter_', 'organization_id');

		/*$parameters = array();
		if(isset($request[$this->getName().'_filter']) && $request[$this->getName().'_filter'] != '')
		{
			$filterString = $request[$this->getName().'_filter'];

			$filterItems = explode(',',$filterString);

			if(isset($request[$this->getName()]))
			{
				$filterItems[] = $request[$this->getName()];
			}

			if(count($filterItems) > 0) $parameters[] = new \DataWarehouse\Query\Model\Parameter('organization_id', 'in', "(".implode(',',$filterItems).")");
		}
		else
		if(isset($request[$this->getName()]))
		{
			$parameters[] = new \DataWarehouse\Query\Model\Parameter('organization_id', '=', $request[$this->getName()]);
		}
		return $parameters;*/
	}
	public function pullQueryParameterDescriptions(&$request)
	{
		return parent::pullQueryParameterDescriptions2($request,
							"select short_name as field_label from modw.organization  where id in (_filter_) order by order_id");
							/*
		$parameters = array();
		if(isset($request[$this->getName()]))
		{
			$fieldLabelQuery = "select short_name as field_label from modw.organization  where id=".$request[$this->getName()];
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

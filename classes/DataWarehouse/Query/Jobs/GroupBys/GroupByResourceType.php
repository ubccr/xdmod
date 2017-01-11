<?php

namespace DataWarehouse\Query\Jobs\GroupBys;

/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* class for adding group by resource to a query
* 
*/

class GroupByResourceType extends \DataWarehouse\Query\Jobs\GroupBy
{
    public static function getLabel()
    {
        return 'Resource Type';
    }


    public function getInfo()
    {
        return  "A categorization of resources into by their general capabilities.";
    }
    public function __construct()
    {
        parent::__construct(
            'resource_type',
            array(),
            "SELECT distinct
								gt.id, 
								gt.abbrev as short_name, 
								gt.description as long_name
								FROM 
								resourcetype gt
								where 1
								order by long_name",
            array()
        );
        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'description';
        $this->_short_name_field_name = 'abbrev';
        $this->_order_id_field_name = 'description';
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->resourcetype_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'resourcetype', 'rt');
    }
    
    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->resourcetype_table);
        
        $id_field = new \DataWarehouse\Query\Model\TableField($this->resourcetype_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $name_field = new \DataWarehouse\Query\Model\TableField($this->resourcetype_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $shortname_field = new \DataWarehouse\Query\Model\TableField($this->resourcetype_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->resourcetype_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));
        
        $query->addField($order_id_field);
        $query->addField($id_field);
        $query->addField($name_field);
        $query->addField($shortname_field);
        
        $query->addGroup($id_field);

        $datatable_resourcetype_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'resourcetype_id');
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $id_field,
            '=',
            $datatable_resourcetype_id_field
        ));
        $this->addOrder($query, $multi_group);
    }

    // JMS: add join with where clause, October 2015
    public function addWhereJoin(
        \DataWarehouse\Query\Query &$query,
        \DataWarehouse\Query\Model\Table $data_table,
        $multi_group = false,
        $operation,
        $whereConstraint
    ) {
    
        // construct the join between the main data_table and this group by table
        $query->addTable($this->resourcetype_table);

        $id_field = new \DataWarehouse\Query\Model\TableField($this->resourcetype_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $datatable_resourcetype_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'resourcetype_id');

        // the where condition that specifies the join of the tables
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $id_field,
            '=',
            $datatable_resourcetype_id_field
        ));

        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint="(". implode(",", $whereConstraint) .")";
        }

        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $id_field,
            $operation,
            $whereConstraint
        ));
    }

    
    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField =new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->resourcetype_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }
    
    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'resourcetype_id');
        /*$parameters = array();
		if(isset($request[$this->getName().'_filter']) && $request[$this->getName().'_filter'] != '')
		{
			$filterString = $request[$this->getName().'_filter'];
			
			$filterItems = explode(',',$filterString);
			
			if(isset($request[$this->getName()])) 
			{
				$filterItems[] = $request[$this->getName()];
			}
			
			if(count($filterItems) > 0) $parameters[] = new \DataWarehouse\Query\Model\Parameter('resourcetype_id', 'in', "(".implode(',',$filterItems).")");		
		}
		else
		if(isset($request[$this->getName()]))
		{
			$parameters[] = new \DataWarehouse\Query\Model\Parameter('resourcetype_id', '=', $request[$this->getName()]);		
		}
		return $parameters;*/
    }
    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            "select description as field_label from modw.resourcetype  where id in (_filter_) order by description"
        );
                            /*
		$parameters = array();
		if(isset($request[$this->getName()]))
		{
			$fieldLabelQuery = "select description as field_label from modw.resourcetype  where id=".$request[$this->getName()];
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

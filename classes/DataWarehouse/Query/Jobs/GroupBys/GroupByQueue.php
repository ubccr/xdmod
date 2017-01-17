<?php

namespace DataWarehouse\Query\Jobs\GroupBys;

/* 
* @author Amin Ghadersohi
* @date 2011-Jan-07
*
* class for adding group by resource queue to a query
* 
*/

class GroupByQueue extends \DataWarehouse\Query\Jobs\GroupBy
{
    public static function getLabel()
    {
         return 'Queue';
    }

    public function getInfo()
    {
        return  "Queue pertains to the low level job queues on each resource.";
    }
    //public function getChartSettings()
    //{
    //	return 'aggregate/bar/auto/y/20/0';
    //}
    public function __construct()
    {
        parent::__construct(
            'queue',
            array('avg_waitduration_hours','sem_avg_waitduration_hours'),
            "SELECT distinct
								gt.id as id, 
								gt.id as short_name, 
								gt.id as long_name
								FROM 
								queue gt
								where 1
								order by gt.id"
        );
        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'id';
        $this->_short_name_field_name = 'id';
        $this->_order_id_field_name = 'id';
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->queue_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'queue', 'q');
    }
    
    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->queue_table);
        
        $id_field = new \DataWarehouse\Query\Model\TableField($this->queue_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $name_field = new \DataWarehouse\Query\Model\TableField($this->queue_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $short_name_field = new \DataWarehouse\Query\Model\TableField($this->queue_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->queue_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));
        
        $query->addField($order_id_field);
        $query->addField($id_field);
        $query->addField($name_field);
        $query->addField($short_name_field);
        
        $query->addGroup($id_field);
        
        $datatable_queue_id_field = new \DataWarehouse\Query\Model\TableField($data_table, "queue_id");
        
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $id_field,
            '=',
            $datatable_queue_id_field
        ));
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            new \DataWarehouse\Query\Model\TableField($this->queue_table, 'resource_id'),
            '=',
            new \DataWarehouse\Query\Model\TableField($data_table, "resource_id")
        ));
        $this->addOrder($query, $multi_group);
    }

    // JMS: add join with where clause, October 2015
    // specific case for this GroupBy class; requires two joins to Queue table, where clause consists of strings
    public function addWhereJoin(
        \DataWarehouse\Query\Query &$query,
        \DataWarehouse\Query\Model\Table $data_table,
        $multi_group = false,
        $operation,
        $whereConstraint
    ) { // NOTE that in this particular case we have STRINGS for our where constraint!!
    
        // construct the join between the main data_table and this group by table
        $query->addTable($this->queue_table);

        $id_field = new \DataWarehouse\Query\Model\TableField($this->queue_table, $this->_id_field_name);
        $datatable_queue_id_field = new \DataWarehouse\Query\Model\TableField($data_table, "queue_id");

        // note: resource_id is the column in the queue table that is an int...
        $resource_id_field = new \DataWarehouse\Query\Model\TableField($data_table, "resource_id");

        // the where conditions that specify the joins of the tables
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $id_field,
            '=',
            $datatable_queue_id_field
        ));
        //$query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition( $resource_id_field, //new \DataWarehouse\Query\Model\TableField($this->queue_table,'resource_id'),
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            new \DataWarehouse\Query\Model\TableField($this->queue_table, 'resource_id'),
            '=',
            //$resource_id_field // new \DataWarehouse\Query\Model\TableField($data_table, "resource_id")
                                                    new \DataWarehouse\Query\Model\TableField($data_table, "resource_id")
        ));
        // the where condition that specifies the constraint on the joined table
        // NOTE that in this particular case we have STRINGS for our where constraint!!
        if (is_array($whereConstraint)) {
            $whereConstraint="('". implode("','", $whereConstraint) ."')";
        }

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
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->queue_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }
    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'queue_id');
        
        /*$parameters = array();
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
				$parameters[] = new \DataWarehouse\Query\Model\Parameter('queue_id', 'in', "('".implode("','",$filterItems)."')");		
			}
		}
		else
		if(isset($request[$this->getName()]))
		{
			$parameters[] = new \DataWarehouse\Query\Model\Parameter('queue_id', '=', "'".$request[$this->getName()]."'");		
		}
		return $parameters;*/
    }
    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            "select distinct id as field_label from modw.queue  where id in (_filter_) order by id"
        );
        
        /*$parameters = array();
		if(isset($request[$this->getName()]))
		{
			$parameters[] = $this->getLabel().' = '.$request[$this->getName()];
		}
		return $parameters;*/
    }
}

<?php
namespace DataWarehouse\Query\Cloud\GroupBys;

class GroupByResource extends \DataWarehouse\Query\Cloud\GroupBy
{
    public static function getLabel()
    {
        return  'Resource';
    }

    public function getInfo()
    {
        return 'A resource is defined as any infrastructure that hosts virtual machines.';
    }

    public function __construct()
    {
        parent::__construct(
            'resource',
            array(),
            'SELECT DISTINCT
                gt.id,
                gt.code AS short_name,
                gt.code AS long_name
            FROM
                resourcefact gt, resourcespecs rs
            WHERE 1
                AND gt.id = rs.resource_id
                AND rs.processors IS NOT NULL
            ORDER BY
                    gt.code',
            array('nsfdirectorate')
        );
        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'code';
        $this->_short_name_field_name = 'code';
        $this->_order_id_field_name = 'code';
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->resourcefact_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'resourcefact', 'rf');
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->resourcefact_table);

        $id_field = new \DataWarehouse\Query\Model\TableField($this->resourcefact_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $resourcefact_code_field = new \DataWarehouse\Query\Model\FormulaField("replace(rf.$this->_long_name_field_name,'-',' ')", $this->getLongNameColumnName($multi_group));
        $resourcefact_shortname_field = new \DataWarehouse\Query\Model\FormulaField("replace(rf.$this->_short_name_field_name,'-',' ')", $this->getShortNameColumnName($multi_group)); //replace(SUBSTRING(code, locate('-',code)+1),'-',' ')
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->resourcefact_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($id_field);
        $query->addField($resourcefact_code_field);
        $query->addField($resourcefact_shortname_field);
        $query->addGroup($id_field);

        $datatable_resource_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'host_resource_id', $this->getIdColumnName($multi_group));
        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $id_field,
                '=',
                $datatable_resource_id_field
            )
        );
        $this->addOrder($query, $multi_group);
    }

    public function addWhereJoin(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group, $operation, $whereConstraint)
    {
        // construct the join between the main data_table and this group by table
        $query->addTable($this->resourcefact_table);

        $id_field = new \DataWarehouse\Query\Model\TableField($this->resourcefact_table, $this->_id_field_name);

        $datatable_resource_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'host_resource_id');
        // the where condition that specifies the join of the tables
        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $id_field,
                '=',
                $datatable_resource_id_field
            )
        );

        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint = '(' . implode(',', $whereConstraint) . ')';
        }


        // the where condition that specifies the constraint on the joined table
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
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->resourcefact_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'host_resource_id');
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            'SELECT code AS field_label FROM modw.resourcefact  WHERE id IN (_filter_) ORDER BY code'
        );
    }
}

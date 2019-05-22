<?php
namespace DataWarehouse\Query\Jobs\GroupBys;

class GroupByNodeCount extends \DataWarehouse\Query\Jobs\GroupBy
{
    public function __construct()
    {
        parent::__construct(
            'nodecount',
            array(),
            'SELECT
                gt.id,
                gt.nodes AS short_name,
                gt.nodes AS long_name
            FROM modw.nodecount gt
            WHERE 1
            ORDER BY gt.id'
        );

        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'nodes';
        $this->_short_name_field_name = 'nodes';
        $this->_order_id_field_name = 'id';

        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->nodes_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'nodecount', 'n');
    }
    public function getInfo()
    {
        return 'A categorization of jobs into discrete groups based on node count.';
    }
    public static function getLabel()
    {
        return 'Node Count';
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->nodes_table);

        $node_count_id_field = new \DataWarehouse\Query\Model\TableField($this->nodes_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $node_count_description_field = new \DataWarehouse\Query\Model\TableField($this->nodes_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $node_count_shortname_field = new \DataWarehouse\Query\Model\TableField($this->nodes_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->nodes_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($node_count_id_field);
        $query->addField($node_count_description_field);
        $query->addField($node_count_shortname_field);

        $query->addGroup($node_count_id_field);

        $datatable_node_count_field = new \DataWarehouse\Query\Model\TableField($data_table, 'node_count');
        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $datatable_node_count_field,
                '=',
                $node_count_id_field
            )
        );

        $this->addOrder($query, $multi_group, 'asc', true);
    }

    public function addWhereJoin(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false, $operation, $whereConstraint)
    {
        $query->addTable($this->nodes_table);

        $node_count_id_field = new \DataWarehouse\Query\Model\TableField($this->nodes_table, $this->_id_field_name);
        $datatable_node_count_field = new \DataWarehouse\Query\Model\TableField($data_table, 'node_count');

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $datatable_node_count_field,
                '=',
                $node_count_id_field
            )
        );
        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint = '(' . implode(',', $whereConstraint) . ')';
        }

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $node_count_id_field,
                $operation,
                $whereConstraint
            )
        );
    } // addWhereJoin()

    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->nodes_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }
    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'node_count');
    }
    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            'SELECT nodes AS field_label FROM modw.nodecount  WHERE id IN (_filter_) ORDER BY id'
        );
    }
}

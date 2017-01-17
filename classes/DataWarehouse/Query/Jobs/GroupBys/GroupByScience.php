<?php

namespace DataWarehouse\Query\Jobs\GroupBys;

/**
 * Class for adding group by fieldofscience to a query.
 *
 * @author Amin Ghadersohi
 * @date 2011-Jan-07
 */
class GroupByScience extends \DataWarehouse\Query\Jobs\GroupBy
{

    public static function getLabel()
    {
        return HIERARCHY_BOTTOM_LEVEL_LABEL;
    }

    public function getInfo()
    {
        return HIERARCHY_BOTTOM_LEVEL_INFO;
    }

    public function __construct()
    {
        parent::__construct(
            'fieldofscience',
            array(),
            "SELECT
         gt.id,
         gt.description as short_name,
         gt.description as long_name
         FROM `fieldofscience_hierarchy` gt
         where 1
         order by gt.order_id",
            array('pi')
        );

        $this->_id_field_name = 'id';
        $this->_short_name_field_name = 'description';
        $this->_long_name_field_name = 'description';
        $this->_order_id_field_name = 'order_id';
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->fos_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'fieldofscience_hierarchy', 'fos');
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->fos_table);

        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->fos_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));
        $query->addField($order_id_field);
        $fos_science_id_field = new \DataWarehouse\Query\Model\TableField($this->fos_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $query->addField($fos_science_id_field);
        $fos_science_name_field = new \DataWarehouse\Query\Model\FormulaField("fos.". $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $query->addField($fos_science_name_field);
        $fos_science_shortname_field = new \DataWarehouse\Query\Model\FormulaField("fos.". $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $query->addField($fos_science_shortname_field);

        $query->addGroup($fos_science_id_field);

        $fostable_id_field = new \DataWarehouse\Query\Model\TableField($this->fos_table, 'id');
        $datatable_fos_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'fos_id');

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $fostable_id_field,
                '=',
                $datatable_fos_id_field
            )
        );

        $this->addOrder($query, $multi_group);
    }

   // JMS: add join with where clause, October 2015
   // caveats: the group by table is not accessible to the outside, is not generalized. Why? Does it get that hairy elsewhere??
    public function addWhereJoin(
        \DataWarehouse\Query\Query &$query,
        \DataWarehouse\Query\Model\Table $data_table,
        $multi_group = false,
        $operation,
        $whereConstraint
    ) {
    

        // construct the join between the main data_table and this group by table
        $query->addTable($this->fos_table);

        $fostable_id_field = new \DataWarehouse\Query\Model\TableField($this->fos_table, 'id');
        $datatable_fos_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'fos_id');

        // the where condition that specifies the join of the tables
        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $fostable_id_field,
                '=',
                $datatable_fos_id_field
            )
        );

        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint="(". implode(",", $whereConstraint) .")";
        }

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $fostable_id_field,
                $operation,
                $whereConstraint
            )
        );
    } // addWhereJoin()
    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->fos_table, $this->_order_id_field_name), $dir, $this->getName());

        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'fos_id');
    }

    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            "select description as field_label from modw.fieldofscience_hierarchy where id in (_filter_) order by order_id"
        );
    }
}

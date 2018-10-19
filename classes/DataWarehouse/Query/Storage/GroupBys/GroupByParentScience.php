<?php
/**
 * @author Amin Ghadersohi
 */

namespace DataWarehouse\Query\Storage\GroupBys;

use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Model\WhereCondition;
use DataWarehouse\Query\Query;
use DataWarehouse\Query\Storage\GroupBy;

/**
 * Class for adding group by parent fieldofscience to a query.
 */
class GroupByParentScience extends GroupBy
{
    public static function getLabel()
    {
        return HIERARCHY_MIDDLE_LEVEL_LABEL;
    }

    public function __construct()
    {
        parent::__construct(
            'parentscience',
            array(),
            '
                SELECT DISTINCT
                     gt.parent_id AS id,
                     gt.parent_description AS short_name,
                     gt.parent_description AS long_name
                 FROM fieldofscience_hierarchy gt
                 WHERE 1
                 ORDER BY gt.parent_description
             '
        );
        $this->_id_field_name = 'parent_id';
        $this->pk_field_name = 'id';
        $this->fk_field_name = 'fos_id';
        $this->_long_name_field_name = 'parent_description';
        $this->_short_name_field_name = 'parent_description';
        $this->_order_id_field_name = 'parent_description';
        $this->table = new Table($this->schema, 'fieldofscience_hierarchy', 'fos');
        $this->info = HIERARCHY_MIDDLE_LEVEL_INFO;
    }

    public function applyTo(
        Query &$query,
        Table $data_table,
        $multi_group = false
    ) {
        $query->addTable($this->table);

        $fos_parentscience_id_field = new TableField($this->table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $fos_parentscience_name_field = new TableField($this->table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $fos_parentscience_shortname_field = new TableField($this->table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new TableField($this->table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($fos_parentscience_name_field);
        $query->addField($fos_parentscience_id_field);
        $query->addField($fos_parentscience_shortname_field);

        $query->addGroup($fos_parentscience_id_field);

        $fostable_id_field = new TableField($this->table, 'id');
        $datatable_fos_id_field = new TableField($data_table, 'fos_id');
        $query->addWhereCondition(
            new WhereCondition(
                $fostable_id_field,
                '=',
                $datatable_fos_id_field
            )
        );
        $this->addOrder($query, $multi_group);
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2(
            $request,
            'SELECT id FROM modw.fieldofscience_hierarchy WHERE parent_id IN (_filter_)',
            'fos_id'
        );
    }
}

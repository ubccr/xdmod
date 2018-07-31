<?php
/**
 * @author Amin Ghadersohi
 */

namespace DataWarehouse\Query\Storage\GroupBys;

use DataWarehouse\Query\Model\FormulaField;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Model\WhereCondition;
use DataWarehouse\Query\Query;
use DataWarehouse\Query\Storage\GroupBy;

/**
 * Class for adding group by fieldofscience to a query.
 */
class GroupByScience extends GroupBy
{
    public static function getLabel()
    {
        return HIERARCHY_BOTTOM_LEVEL_LABEL;
    }

    public function __construct()
    {
        parent::__construct(
            'fieldofscience',
            array(),
            '
                SELECT DISTINCT
                    gt.id,
                    gt.description AS short_name,
                    gt.description AS long_name
                FROM fieldofscience_hierarchy gt
                WHERE 1
                ORDER BY gt.order_id
            '
        );
        $this->_id_field_name = 'id';
        $this->pk_field_name = 'id';
        $this->fk_field_name = 'fos_id';
        $this->_short_name_field_name = 'description';
        $this->_long_name_field_name = 'description';
        $this->_order_id_field_name = 'order_id';
        $this->table = new Table($this->schema, 'fieldofscience_hierarchy', 'fos');
        $this->info = HIERARCHY_BOTTOM_LEVEL_INFO;
    }

    public function applyTo(
        Query &$query,
        Table $data_table,
        $multi_group = false
    ) {
        $query->addTable($this->table);

        $order_id_field = new TableField($this->table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));
        $query->addField($order_id_field);

        $fos_science_id_field = new TableField($this->table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $query->addField($fos_science_id_field);
        $query->addGroup($fos_science_id_field);

        $fos_science_name_field = new FormulaField('fos.'. $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $query->addField($fos_science_name_field);

        $fos_science_shortname_field = new FormulaField('fos.'. $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $query->addField($fos_science_shortname_field);

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
}

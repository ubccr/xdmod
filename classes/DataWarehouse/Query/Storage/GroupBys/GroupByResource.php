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
 * class for adding group by resource to a query
 */
class GroupByResource extends GroupBy
{
    public static function getLabel()
    {
        return  'Resource';
    }

    public function __construct()
    {
        parent::__construct(
            'resource',
            array(),
            '
                SELECT DISTINCT
                    gt.id,
                    gt.code AS short_name,
                    gt.code AS long_name
                FROM resourcefact gt
                WHERE 1
                ORDER BY gt.code
            '
        );
        $this->_id_field_name = 'id';
        $this->pk_field_name = 'id';
        $this->fk_field_name = 'resource_id';
        $this->_long_name_field_name = 'code';
        $this->_short_name_field_name = 'code';
        $this->_order_id_field_name = 'code';
        $this->table = new Table($this->schema, 'resourcefact', 'rf');
        $this->info = 'A resource is a remote computer that can store data.';
    }

    public function applyTo(
        Query &$query,
        Table $data_table,
        $multi_group = false
    ) {
        $query->addTable($this->table);

        $id_field = new TableField($this->table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $query->addField($id_field);
        $query->addGroup($id_field);

        $resourcefact_code_field = new FormulaField("REPLACE(rf.{$this->_long_name_field_name}, '-', ' ')", $this->getLongNameColumnName($multi_group));
        $query->addField($resourcefact_code_field);

        $resourcefact_shortname_field = new FormulaField("REPLACE(rf.{$this->_short_name_field_name}, '-', ' ')", $this->getShortNameColumnName($multi_group));
        $query->addField($resourcefact_shortname_field);

        $order_id_field = new TableField($this->table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));
        $query->addField($order_id_field);

        $datatable_resource_id_field = new TableField($data_table, 'resource_id', $this->getIdColumnName($multi_group));
        $query->addWhereCondition(
            new WhereCondition(
                $id_field,
                '=',
                $datatable_resource_id_field
            )
        );
        $this->addOrder($query, $multi_group);
    }
}

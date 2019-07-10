<?php

namespace DataWarehouse\Query\Cloud\GroupBys;

use DataWarehouse\Query\Cloud\GroupBy as GroupBy;
use DataWarehouse\Query\Model\OrderBy;
use DataWarehouse\Query\Model\Schema as Schema;
use DataWarehouse\Query\Model\Table as Table;
use DataWarehouse\Query\Model\TableField;
use DataWarehouse\Query\Model\WhereCondition;
use DataWarehouse\Query\Query;

/**
 * Class GroupByDomain
 *
 * @package DataWarehouse\Query\Cloud\GroupBys
 */
class GroupByDomain extends GroupBy
{
    /**
     * The fields that data including this GroupBy should be ordered by.
     *
     * @var array
     */
    private $_order_fields;

    /**
     * The schema that this data resides in.
     *
     * @var Schema
     */
    private $schema;

    /**
     * The table that this data resides in.
     *
     * @var Table
     */
    private $table;

    /**
     * @see GroupBy::getLabel
     */
    public static function getLabel()
    {
        return 'Domain';
    }

    /**
     * @see GroupBy::getInfo
     */
    public function getInfo()
    {
        return "A domain is defined as .... ";
    }

    public function __construct()
    {
        parent::__construct(
            'domain',
            array(),
            '
                SELECT DISTINCT
                    d.id,
                    d.name as short_name,
                    d.name as long_name
                FROM modw_cloud.domains d
                ORDER BY resource_id, domain_id, name
            '
        );

        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'name';
        $this->_short_name_field_name = 'name';
        $this->_order_fields = array('resource_id', 'domain_id', 'name');

        $this->schema = new Schema('modw_cloud');
        $this->table = new Table($this->schema, 'domains', 'dm');
    }

    /**
     * @see GroupBy::applyTo()
     */
    public function applyTo(Query &$query, Table $dataTable, $multiGroup = false)
    {
        $query->addTable($this->table);

        $idField = new TableField($this->table, $this->_id_field_name, $this->getColumnName('id', $multiGroup));

        $query->addField($idField);
        $query->addField(new TableField($this->table, $this->_long_name_field_name, $this->getColumnName('name', $multiGroup)));
        $query->addField(new TableField($this->table, $this->_short_name_field_name, $this->getColumnName('short_name', $multiGroup)));
        $query->addField(new TableField($this->table, $this->_id_field_name, $this->getColumnName('order_id', $multiGroup)));

        $this->addOrderFields($query, $multiGroup);

        $query->addGroup($idField);

        $fkField = new TableField($dataTable, 'domain_id');
        $query->addWhereCondition(new WhereCondition($idField, '=', $fkField));

        $this->addOrderBys($query);
    }

    /**
     * Add this GroupBy's `order_fields` to the provided $query's fields.
     *
     * @param Query $query the query that this GroupBy's order fields should be added to.
     * @param bool $multiGroup whether or not this query has multiple group bys.
     */
    private function addOrderFields(Query &$query, $multiGroup = false)
    {
        foreach ($this->_order_fields as $orderFieldName) {
            $orderField = new TableField($this->table, $orderFieldName, $this->getColumnName($orderFieldName, $multiGroup));
            $query->addField($orderField);
        }
    }

    /**
     * Add this GroupBy's `order_fields` to the provided $query as order by fields.
     *
     * @param Query $query  the query that this GroupBy's order fields to.
     * @param string $dir   the
     * @param bool $prepend if true, then prepend else append.
     */
    private function addOrderBys(Query $query, $dir = 'asc', $prepend = false)
    {
        foreach($this->_order_fields as $orderFieldName) {
            $orderGroupBy = new OrderBy(new TableField($this->table, $orderFieldName), $dir, $this->getName());
            if ($prepend === true) {
                $query->prependOrder($orderGroupBy);
            } else {
                $query->addOrder($orderGroupBy);
            }
        }
    }

    /**
     * Conditionally format $columnName based on $multiGroup.
     *
     * @param $columnName
     * @param bool $multiGroup
     * @return string
     */
    private function getColumnName($columnName, $multiGroup = false)
    {
        if ($multiGroup === false) {
            return $columnName;
        } else {
            return $this->getName() . '_' . $columnName;
        }
    }
}

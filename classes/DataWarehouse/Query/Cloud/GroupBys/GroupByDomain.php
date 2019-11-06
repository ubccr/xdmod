<?php

namespace DataWarehouse\Query\Cloud\GroupBys;

use DataWarehouse\Query\Cloud\GroupBy as GroupBy;
use DataWarehouse\Query\Model\FormulaField;
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
        return "A domain is a high-level container for projects, users and groups";
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
                ORDER BY resource_id, name
            '
        );

        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'name';
        $this->_short_name_field_name = 'name';
        $this->_order_fields = array('resource_id','name');

        $this->schema = new Schema('modw_cloud');
        $this->table = new Table($this->schema, 'domains', 'dm');
    }

    /**
     * @see GroupBy::applyTo()
     */
    public function applyTo(Query &$query, Table $dataTable, $multiGroup = false)
    {
        $resourceTable = new Table(new Schema('modw'), 'resourcefact', 'rf');
        $resourceFactIdField = new TableField($resourceTable, 'id');
        $resourceFactNameField = new TableField($resourceTable, 'name');

        $query->addTable($this->table);
        $query->addTable($resourceTable);

        $domainIdField = new TableField($this->table, $this->_id_field_name, $this->getColumnName('id', $multiGroup));
        $domainNameField = new TableField($this->table, $this->_long_name_field_name, $this->getColumnName('name', $multiGroup));
        $resourceIdField = new TableField($this->table, 'resource_id', $this->getColumnName('resource_id', $multiGroup));

        $query->addField($domainIdField);
        $query->addField(new FormulaField("CONCAT({$domainNameField->getQualifiedName()}, ' - ', {$resourceFactNameField->getQualifiedName()})", $this->getColumnName('name', $multiGroup)));
        $query->addField(new TableField($this->table, $this->_short_name_field_name, $this->getColumnName('short_name', $multiGroup)));
        $query->addField(new TableField($this->table, $this->_id_field_name, $this->getColumnName('order_id', $multiGroup)));

        $query->addGroup($domainIdField);

        $fkField = new TableField($dataTable, 'domain_id');
        $resourceFkField = new TableField($dataTable, 'host_resource_id');

        $query->addWhereCondition(new WhereCondition($domainIdField, '=', $fkField));
        $query->addWhereCondition(new WhereCondition($resourceIdField, '=', $resourceFkField));
        $query->addWhereCondition(new WhereCondition($resourceFactIdField, '=', $resourceFkField));

        $this->addOrder($query);
    }

    /**
     * Add this GroupBy's order by clauses to the provided `Query` instance.
     *
     * @param Query $query      The query to add this GroupBy's order by clauses to.
     * @param bool $multi_group Whether or not there are multiple Group By's?
     * @param string $dir       Which direction the order by's are to sort.
     * @param bool $prepend     Whether or not to prepend or append this Group By's order by clauses.
     */
    public function addOrder(Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
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

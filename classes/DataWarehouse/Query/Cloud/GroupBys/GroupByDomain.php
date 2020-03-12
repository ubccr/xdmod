<?php
namespace DataWarehouse\Query\Cloud\GroupBys;

class GroupByDomain extends \DataWarehouse\Query\Cloud\GroupBy
{
    public static function getLabel()
    {
        return 'Domain';
    }

    public function getInfo()
    {
        return 'A domain is a high-level container for projects, users and groups';
    }
    public function __construct()
    {
        parent::__construct(
            'domain',
            array(),
            'SELECT DISTINCT
                d.name AS id,
                d.name AS short_name,
                d.name AS long_name
            FROM domains d
            WHERE 1
            ORDER BY gt.id',
            array()
        );
        $this->_id_field_name = 'name';
        $this->_short_name_field_name = 'name';
        $this->_long_name_field_name = 'name';
        $this->_order_id_field_name = 'name';
        $this->schema = new \DataWarehouse\Query\Model\Schema('modw_cloud');
        $this->table = new \DataWarehouse\Query\Model\Table($this->schema, 'domains', 'dm');
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->table);

        $domainIdField = new \DataWarehouse\Query\Model\TableField($this->table, 'id');
        $agg_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'domain_id');

        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $domainIdField,
            '=',
            $agg_id_field
        ));

        $id_field = new \DataWarehouse\Query\Model\TableField($this->table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $name_field =  new \DataWarehouse\Query\Model\TableField($this->table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $shortname_field =  new \DataWarehouse\Query\Model\TableField($this->table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($id_field);
        $query->addField($name_field);
        $query->addField($shortname_field);

        $query->addGroup($id_field);

        $this->addOrder($query);
    }

    public function addWhereJoin(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group, $operation, $whereConstraint) {
        $query->addTable($this->table);

        $domainIdField = new \DataWarehouse\Query\Model\TableField($this->table, 'id');
        $agg_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'domain_id');

        // the where condition that specifies the join of the tables
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $domainIdField,
            '=',
            $agg_id_field
        ));

        if (is_array($whereConstraint)) {
            $whereConstraint = '(' . implode(',', $whereConstraint) . ')';
        }

        $id_field = new \DataWarehouse\Query\Model\TableField(
            $this->table,
            $this->_id_field_name,
            $this->getIdColumnName($multi_group)
        );

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $id_field,
                $operation,
                $whereConstraint
            )
        );
    }

    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }
    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, 'SELECT id FROM modw_cloud.domains WHERE name IN (_filter_)', 'domain_id');
    }
    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            'SELECT DISTINCT name AS field_label FROM modw_cloud.domains WHERE name IN (_filter_) ORDER BY name'
        );
    }
}

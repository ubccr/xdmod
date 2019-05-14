<?php
namespace DataWarehouse\Query\Jobs\GroupBys;

class GroupByGrantType extends \DataWarehouse\Query\Jobs\GroupBy
{
    public static function getLabel()
    {
        return  'Grant Type';
    }

    public function getInfo()
    {
        return  'A categorization of the projects/allocations.';
    }

    public function __construct()
    {
        parent::__construct(
            'grant_type',
            array(),
            'SELECT DISTINCT
                gt.id,
                gt.name AS short_name,
                gt.name AS long_name
            FROM
                granttype gt
            WHERE 1
            ORDER BY long_name',
            array()
        );
        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'name';
        $this->_short_name_field_name = 'name';
        $this->_order_id_field_name = 'name';
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->granttype_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'granttype', 'grt');
        $this->account_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'account', 'acc');
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->granttype_table);
        $query->addTable($this->account_table);

        $id_field = new \DataWarehouse\Query\Model\TableField($this->granttype_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $name_field = new \DataWarehouse\Query\Model\TableField($this->granttype_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $shortname_field = new \DataWarehouse\Query\Model\TableField($this->granttype_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->granttype_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($id_field);
        $query->addField($name_field);
        $query->addField($shortname_field);

        $query->addGroup($id_field);

        $datatable_account_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'account_id');
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            new \DataWarehouse\Query\Model\TableField($this->account_table, 'id'),
            '=',
            $datatable_account_id_field
        ));
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            new \DataWarehouse\Query\Model\TableField($this->account_table, 'granttype_id'),
            '=',
            $id_field
        ));
        $this->addOrder($query, $multi_group);
    }

    // JMS: add join with where clause, October 2015
    // Note: special case, multiple tables and joins needed
    public function addWhereJoin(
        \DataWarehouse\Query\Query &$query,
        \DataWarehouse\Query\Model\Table $data_table,
        $multi_group = false,
        $operation,
        $whereConstraint
    ) {
        // the where condition that specifies the joins
        $query->addTable($this->granttype_table);
        $query->addTable($this->account_table);

        $id_field = new \DataWarehouse\Query\Model\TableField($this->granttype_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $datatable_account_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'account_id');

        // the where condition that specifies the constraint on the joined table
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            new \DataWarehouse\Query\Model\TableField($this->account_table, 'id'),
            '=',
            $datatable_account_id_field
        ));
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            new \DataWarehouse\Query\Model\TableField($this->account_table, 'granttype_id'),
            '=',
            $id_field
        ));

        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint = '(' . implode(',', $whereConstraint) . ')';
        }

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
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->granttype_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, 'SELECT id FROM modw.account WHERE granttype_id IN (_filter_)', 'account_id');
    }
    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            'SELECT name AS field_label FROM modw.granttype  WHERE id IN (_filter_) ORDER BY name'
        );
    }
}

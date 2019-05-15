<?php
namespace DataWarehouse\Query\Jobs\GroupBys;

class GroupByAllocation extends \DataWarehouse\Query\Jobs\GroupBy
{
    public static function getLabel()
    {
        return  'Allocation';
    }

    public function getInfo()
    {
        return  'A funded project that is allowed to run jobs on resources.';
    }
    public function __construct()
    {
        parent::__construct(
            'allocation',
            array(),
            'SELECT DISTINCT
                gt.account_id AS id,
                gt.short_name,
                gt.long_name
            FROM
                allocation gt
            WHERE 1
            GROUP BY
                gt.long_name
            ORDER BY
                gt.order_id',
            array('person')
        );
        $this->_id_field_name = 'account_id';
        $this->_long_name_field_name = 'long_name';
        $this->_short_name_field_name = 'short_name';
        $this->_order_id_field_name = 'order_id';
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->allocation_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'allocation', 'al');
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->allocation_table);

        $id_field = new \DataWarehouse\Query\Model\TableField($this->allocation_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $name_field = new \DataWarehouse\Query\Model\TableField($this->allocation_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $shortname_field = new \DataWarehouse\Query\Model\TableField($this->allocation_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->allocation_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($id_field);
        $query->addField($name_field);
        $query->addField($shortname_field);

        $query->addGroup(new \DataWarehouse\Query\Model\TableField($this->allocation_table, $this->_id_field_name));

        $datatable_allocation_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'allocation_id');
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $datatable_allocation_id_field,
            '=',
            new \DataWarehouse\Query\Model\TableField($this->allocation_table, 'id')
        ));

        $this->addOrder($query, $multi_group, 'asc', false);
    }

    // JMS: add join with where clause, October 2015
    public function addWhereJoin(
        \DataWarehouse\Query\Query &$query,
        \DataWarehouse\Query\Model\Table $data_table,
        $multi_group = false,
        $operation,
        $whereConstraint
    ) {
        // construct the join between the main data_table and this group by table
        $query->addTable($this->allocation_table);

        $id_field = new \DataWarehouse\Query\Model\TableField($this->allocation_table, 'id');
        $datatable_allocation_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'allocation_id');

        // construct the join between the main data_table and this group by table
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $datatable_allocation_id_field,
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
    } // addWhereJoin

    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->allocation_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }
    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2(
            $request,
            'SELECT DISTINCT id FROM modw.allocation WHERE account_id IN (_filter_)',
            'allocation_id'
        );
    }
    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            'SELECT DISTINCT long_name AS field_label FROM modw.allocation WHERE account_id IN (_filter_) GROUP BY long_name ORDER BY order_id'
        );
    }


    public function getPossibleValues($hint = null, $limit = null, $offset = null, array $parameters = array())
    {
        if ($this->_possible_values_query == null) {
            return array();
        }

        $possible_values_query = $this->_possible_values_query;

        foreach ($parameters as $pname => $pvalue) {
            if ($pname == 'person') {
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.peopleonaccount poa, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE poa.person_id = ' . $pvalue .' AND gt.account_id = poa.account_id  AND ', $possible_values_query);
            } elseif ($pname == 'provider') {
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.resourcefact rf, modw.allocationonresource alor, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE rf.organization_id = ' . $pvalue .' AND gt.id = alor.allocation_id AND rf.id = alor.resource_id AND ', $possible_values_query);
            } elseif ($pname == 'institution') {
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.person p,  modw.peopleonaccount poa, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE p.organization_id = ' . $pvalue .' and gt.account_id = poa.account_id and p.id = poa.person_id  AND ', $possible_values_query);
            } elseif ($pname == 'pi') {
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.peopleunderpi pup, modw.peopleonaccount poa, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE pup.principalinvestigator_person_id = ' . $pvalue .' AND gt.account_id = poa.account_id AND pup.person_id = poa.person_id  AND ', $possible_values_query);
            }
        }

        return parent::getPossibleValues($hint, $limit, $offset, $parameters, $possible_values_query);
    }
}

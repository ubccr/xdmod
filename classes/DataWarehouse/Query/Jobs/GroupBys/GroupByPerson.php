<?php
namespace DataWarehouse\Query\Jobs\GroupBys;

class GroupByPerson extends \DataWarehouse\Query\Jobs\GroupBy
{
    public static function getLabel()
    {
        return  'User';
    }


    public function getInfo()
    {
        return 'A person who is on a PIs allocation, hence able run jobs on resources.';
    }
    public function __construct()
    {
        parent::__construct(
            'person',
            array(),
            'SELECT distinct
                gt.id,
                gt.short_name AS short_name,
                gt.long_name AS long_name
            FROM person gt
            WHERE 1
            ORDER BY gt.order_id'
        );
        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'long_name';
        $this->_short_name_field_name = 'short_name';
        $this->_order_id_field_name = 'order_id';
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->person_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'person', 'p');
    }
    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->person_table);

        $persontable_id_field = new \DataWarehouse\Query\Model\TableField($this->person_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $person_name_field = new \DataWarehouse\Query\Model\TableField($this->person_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $person_shortname_field = new \DataWarehouse\Query\Model\TableField($this->person_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->person_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($persontable_id_field);
        $query->addField($person_name_field);
        $query->addField($person_shortname_field);

        $query->addGroup($persontable_id_field);

        $datatable_person_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'person_id');
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $persontable_id_field,
            '=',
            $datatable_person_id_field
        ));

        $this->addOrder($query, $multi_group);
    }

    public function addWhereJoin(
        \DataWarehouse\Query\Query &$query,
        \DataWarehouse\Query\Model\Table $data_table,
        $multi_group = false,
        $operation,
        $whereConstraint
    ) {
        // construct the join between the main data_table and this group by table
        $query->addTable($this->person_table);

        $persontable_id_field = new \DataWarehouse\Query\Model\TableField($this->person_table, $this->_id_field_name);
        $datatable_person_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'person_id');

        // the where condition that specifies the join of the tables
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $persontable_id_field,
            '=',
            $datatable_person_id_field
        ));
        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint = '(' . implode(',', $whereConstraint) . ')';
        }

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $persontable_id_field,
                $operation,
                $whereConstraint
            )
        );
    }

    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->person_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }
    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'person_id');
    }
    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            'SELECT long_name AS field_label FROM modw.person  WHERE id IN (_filter_) ORDER BY order_id'
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
                $possible_values_query = str_ireplace('WHERE ', 'WHERE gt.id = ' . $pvalue . ' AND ', $possible_values_query);
            } elseif ($pname == 'provider') {
                //find the names all the people that have accounts on the resources at the provider.
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.systemaccount sa,  modw.resourcefact rf, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE rf.id = sa.resource_id and rf.organization_id = ' . $pvalue . ' AND gt.id = sa.person_id  AND ', $possible_values_query);
            } elseif ($pname == 'institution') {
                $possible_values_query = str_ireplace('WHERE ', 'WHERE gt.organization_id = ' . $pvalue . '  AND ', $possible_values_query);
            } elseif ($pname == 'pi') {
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.peopleunderpi pup, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE pup.principalinvestigator_person_id = ' . $pvalue . ' AND gt.id = pup.person_id  AND ', $possible_values_query);
            }
        }

        return parent::getPossibleValues($hint, $limit, $offset, $parameters, $possible_values_query);
    }
}

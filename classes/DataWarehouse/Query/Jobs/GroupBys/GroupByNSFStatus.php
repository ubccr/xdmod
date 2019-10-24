<?php
namespace DataWarehouse\Query\Jobs\GroupBys;

class GroupByNSFStatus extends \DataWarehouse\Query\Jobs\GroupBy
{
    public function __construct()
    {
        parent::__construct(
            'nsfstatus',
            array(),
            'SELECT DISTINCT
                gt.id,
                gt.code AS short_name,
                gt.name AS long_name
            FROM nsfstatuscode gt
            WHERE 1
            ORDER BY gt.name'
        );

        $this->_id_field_name = 'id';
        $this->_long_name_field_name = 'name';
        $this->_short_name_field_name = 'name';
        $this->_order_id_field_name = 'name';
        $this->modw_schema = new \DataWarehouse\Query\Model\Schema('modw');
        $this->person_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'person', 'p');
        $this->nsfstatuscode_table = new \DataWarehouse\Query\Model\Table($this->modw_schema, 'nsfstatuscode', 'ns');
    }
    public function getInfo()
    {
        return  'Categorization of the users who ran jobs.';
    }
    public static function getLabel()
    {
        return  'User NSF Status';
    }

    public function getDefaultDatasetType()
    {
        return 'timeseries';
    }

    public function getDefaultCombineMethod()
    {
        return 'stack';
    }

    public function applyTo(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false)
    {
        $query->addTable($this->nsfstatuscode_table);

        $nsfstatus_id_field = new \DataWarehouse\Query\Model\TableField($this->nsfstatuscode_table, $this->_id_field_name, $this->getIdColumnName($multi_group));
        $nsfstatus_name_field = new \DataWarehouse\Query\Model\TableField($this->nsfstatuscode_table, $this->_long_name_field_name, $this->getLongNameColumnName($multi_group));
        $nsfstatus_shortname_field = new \DataWarehouse\Query\Model\TableField($this->nsfstatuscode_table, $this->_short_name_field_name, $this->getShortNameColumnName($multi_group));
        $order_id_field = new \DataWarehouse\Query\Model\TableField($this->nsfstatuscode_table, $this->_order_id_field_name, $this->getOrderIdColumnName($multi_group));

        $query->addField($order_id_field);
        $query->addField($nsfstatus_id_field);
        $query->addField($nsfstatus_name_field);
        $query->addField($nsfstatus_shortname_field);

        $query->addGroup($nsfstatus_id_field);

        $datatable_person_nsfstatuscode_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'person_nsfstatuscode_id');
        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $nsfstatus_id_field,
            '=',
            $datatable_person_nsfstatuscode_id_field
        ));
        $this->addOrder($query, $multi_group);
    }

    public function addWhereJoin(\DataWarehouse\Query\Query &$query, \DataWarehouse\Query\Model\Table $data_table, $multi_group = false, $operation, $whereConstraint)
    {
        $query->addTable($this->nsfstatuscode_table);

        $nsfstatus_id_field = new \DataWarehouse\Query\Model\TableField($this->nsfstatuscode_table, $this->_id_field_name);
        $datatable_person_nsfstatuscode_id_field = new \DataWarehouse\Query\Model\TableField($data_table, 'person_nsfstatuscode_id');

        $query->addWhereCondition(new \DataWarehouse\Query\Model\WhereCondition(
            $nsfstatus_id_field,
            '=',
            $datatable_person_nsfstatuscode_id_field
        ));
        // the where condition that specifies the constraint on the joined table
        if (is_array($whereConstraint)) {
            $whereConstraint = '(' . implode(',', $whereConstraint) . ')';
        }

        $query->addWhereCondition(
            new \DataWarehouse\Query\Model\WhereCondition(
                $nsfstatus_id_field,
                $operation,
                $whereConstraint
            )
        );
    } // addWhereJoin()



    public function addOrder(\DataWarehouse\Query\Query &$query, $multi_group = false, $dir = 'asc', $prepend = false)
    {
        $orderField = new \DataWarehouse\Query\Model\OrderBy(new \DataWarehouse\Query\Model\TableField($this->nsfstatuscode_table, $this->_order_id_field_name), $dir, $this->getName());
        if ($prepend === true) {
            $query->prependOrder($orderField);
        } else {
            $query->addOrder($orderField);
        }
    }

    public function pullQueryParameters(&$request)
    {
        return parent::pullQueryParameters2($request, '_filter_', 'person_nsfstatuscode_id');
    }
    public function pullQueryParameterDescriptions(&$request)
    {
        return parent::pullQueryParameterDescriptions2(
            $request,
            'SELECT name AS field_label FROM modw.nsfstatuscode  WHERE id IN (_filter_) ORDER BY name'
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
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.person p, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE p.nsfstatuscode_id = gt.id AND p.id = ' . $pvalue . ' AND ', $possible_values_query);
            } elseif ($pname == 'provider') {
                //find the names all the people that have accounts on the resources at the provider.
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.person p, modw.systemaccount sa,  modw.resourcefact rf, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE  p.nsfstatuscode_id = gt.id and rf.id = sa.resource_id AND rf.organization_id = ' . $pvalue . ' AND p.id = sa.person_id  AND ', $possible_values_query);
            } elseif ($pname == 'institution') {
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.person p, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE p.nsfstatuscode_id = gt.id and p.organization_id = ' . $pvalue . ' AND ', $possible_values_query);
            } elseif ($pname == 'pi') {
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.peopleunderpi pup, modw.person p, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE pup.principalinvestigator_person_id = ' . $pvalue . ' AND p.id = pup.person_id AND gt.id = p.nsfstatuscode_id  AND ', $possible_values_query);
            }
        }

        return parent::getPossibleValues($hint, $limit, $offset, $parameters, $possible_values_query);
    }
}

<?php
/**
 * @author Amin Ghadersohi
 */

namespace DataWarehouse\Query\Storage\GroupBys;

use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Storage\GroupBy;

/**
 * class for adding group by person to a query
 */
class GroupByPerson extends GroupBy
{
    public static function getLabel()
    {
        return  'User';
    }

    public function __construct()
    {
        parent::__construct(
            'person',
            array(),
            '
                SELECT DISTINCT
                    gt.id,
                    gt.short_name AS short_name,
                    gt.long_name AS long_name
                FROM person gt
                WHERE 1
                ORDER BY gt.order_id
            '
        );
        $this->_id_field_name = 'id';
        $this->pk_field_name = 'id';
        $this->fk_field_name = 'person_id';
        $this->_long_name_field_name = 'long_name';
        $this->_short_name_field_name = 'short_name';
        $this->_order_id_field_name = 'order_id';
        $this->table = new Table($this->schema, 'person', 'p');
        $this->info = 'A person who is on a PIs allocation, hence able to store data on resources.';
    }

    public function getPossibleValues(
        $hint = null,
        $limit = null,
        $offset = null,
        array $parameters = array()
    ) {
        if ($this->_possible_values_query == null) {
            return array();
        }

        $possible_values_query = $this->_possible_values_query;

        foreach ($parameters as $pname => $pvalue) {
            if ($pname == 'person') {
                $possible_values_query = str_ireplace('WHERE ', "WHERE gt.id = $pvalue AND ", $possible_values_query);
            } elseif ($pname == 'provider') {//find the names all the people that have accounts on the resources at the provider.
                $possible_values_query = str_ireplace('FROM ', "FROM modw.systemaccount sa,  modw.resourcefact rf, ", $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', "WHERE rf.id = sa.resource_id AND rf.organization_id = $pvalue AND gt.id = sa.person_id  AND ", $possible_values_query);
            } elseif ($pname == 'institution') {
                $possible_values_query = str_ireplace('WHERE ', "WHERE gt.organization_id = $pvalue  AND ", $possible_values_query);
            } elseif ($pname == 'pi') {
                $possible_values_query = str_ireplace('FROM ', "FROM modw.peopleunderpi pup, ", $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', "WHERE pup.principalinvestigator_person_id = $pvalue AND gt.id = pup.person_id  AND ", $possible_values_query);
            }
        }

        return parent::getPossibleValues($hint, $limit, $offset, $parameters, $possible_values_query);
    }
}

<?php
/**
 * @author Amin Ghadersohi
 */

namespace DataWarehouse\Query\Storage\GroupBys;

use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Storage\GroupBy;

/**
 * class for adding group by system username to a query
 */
class GroupByUsername extends GroupBy
{
    public static function getLabel()
    {
        return 'System Username';
    }

    public function __construct()
    {
        parent::__construct(
            'username',
            array(),
            '
                SELECT DISTINCT
                    gt.username AS id,
                    gt.username AS short_name,
                    gt.username as long_name
                FROM systemaccount gt
                WHERE 1
                ORDER BY gt.username
            '
        );
        $this->_id_field_name = 'username';
        $this->pk_field_name = 'id';
        $this->fk_field_name = 'systemaccount_id';
        $this->_short_name_field_name = 'username';
        $this->_long_name_field_name = 'username';
        $this->_order_id_field_name = 'username';
        $this->table = new Table($this->schema, 'systemaccount', 'sa');
        $this->info = 'The specific system username of the users who stores data.';
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
                $possible_values_query = str_ireplace('WHERE ', 'WHERE gt.person_id = $pvalue AND ', $possible_values_query);
            } elseif ($pname == 'provider') {
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.resourcefact rf, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE rf.organization_id = $pvalue AND gt.resource_id = rf.id  AND ', $possible_values_query);
            } elseif ($pname == 'institution') {
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.person p, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE p.organization_id = $pvalue AND gt.person_id = p.id  AND ', $possible_values_query);
            } elseif ($pname == 'pi') {
                $possible_values_query = str_ireplace('FROM ', 'FROM modw.peopleunderpi pup, ', $possible_values_query);
                $possible_values_query = str_ireplace('WHERE ', 'WHERE pup.principalinvestigator_person_id = $pvalue AND gt.person_id = pup.person_id  AND ', $possible_values_query);
            }
        }

        return parent::getPossibleValues(
            $hint,
            $limit,
            $offset,
            $parameters,
            $possible_values_query
        );
    }
}

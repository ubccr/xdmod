<?php
/**
 * @author Amin Ghadersohi
 */

namespace DataWarehouse\Query\Storage\GroupBys;

use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Storage\GroupBy;

/**
 * class for adding group by resource to a query
 */
class GroupByResourceType extends GroupBy
{
    public static function getLabel()
    {
        return 'Resource Type';
    }

    public function __construct()
    {
        parent::__construct(
            'resource_type',
            array(),
            '
                SELECT DISTINCT
                    gt.id,
                    gt.abbrev AS short_name,
                    gt.description AS long_name
                FROM resourcetype gt
                WHERE 1
                ORDER BY long_name
            '
        );
        $this->_id_field_name = 'id';
        $this->pk_field_name = 'id';
        $this->fk_field_name = 'resourcetype_id';
        $this->_long_name_field_name = 'description';
        $this->_short_name_field_name = 'abbrev';
        $this->_order_id_field_name = 'description';
        $this->table = new Table($this->schema, 'resourcetype', 'rt');
        $this->info = 'A categorization of resources into by their general capabilities.';
    }
}

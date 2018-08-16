<?php
/**
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\GroupBys;

use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Storage\GroupBy;

/**
 * class for adding group by mountpoint to a query
 */
class GroupByMountpoint extends GroupBy
{
    public static function getLabel()
    {
        return 'Mountpoint';
    }

    public function __construct()
    {
        parent::__construct(
            'mountpoint',
            array(),
            '
                SELECT DISTINCT
                    gt.mountpoint_id AS id,
                    gt.path AS short_name,
                    gt.path AS long_name
                FROM mountpoint gt
                WHERE 1
                ORDER BY gt.path
            '
        );
        $this->_id_field_name = 'mountpoint_id';
        $this->pk_field_name = 'mountpoint_id';
        $this->fk_field_name = 'mountpoint_id';
        $this->_long_name_field_name = 'path';
        $this->_short_name_field_name = 'path';
        $this->_order_id_field_name = 'path';
        $this->table = new Table($this->schema, 'mountpoint', 'm');
        $this->info = 'A mountpoint is a directory where a file system is mounted.';
    }
}

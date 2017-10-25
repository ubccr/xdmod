<?php
/**
 * @package OpenXdmod\Storage
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\GroupBys;

use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Storage\GroupBy;

/**
 * GroupBy for viewing data by file system.
 */
class GroupByFileSystem extends GroupBy
{
    public function __construct()
    {
        parent::__construct(
            'file_system',
            array(),
            '
                SELECT DISTINCT
                    gt.file_system_id AS id,
                    gt.name AS short_name,
                    gt.name AS long_name
                FROM modw_storage.file_system gt
                WHERE 1
                ORDER BY gt.name
            '
        );

        $this->_id_field_name = 'file_system_id';
        $this->_long_name_field_name = 'name';
        $this->_short_name_field_name = 'name';
        $this->_order_id_field_name = 'name';
        $this->table = new Table(
            $this->schema,
            'file_system',
            'fs'
        );
        $this->info = 'A file system stores data in files';
    }

    public static function getLabel()
    {
        return 'File System';
    }
}

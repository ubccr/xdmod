<?php
/**
 * @package OpenXdmod\Storage
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\GroupBys;

use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Storage\GroupBy;

/**
 * File system directory GroupBy.
 */
class GroupByDirectory extends GroupBy
{
    public function __construct()
    {
        parent::__construct(
            'directory',
            array(),
            '
                SELECT DISTINCT
                    gt.directory_id AS id,
                    gt.path AS short_name,
                    gt.path AS long_name
                FROM modw_storage.directory gt
                WHERE 1
                ORDER BY gt.path
            '
        );

        $this->_id_field_name = 'directory_id';
        $this->_long_name_field_name = 'path';
        $this->_short_name_field_name = 'path';
        $this->_order_id_field_name = 'path';
        $this->table = new Table(
            $this->schema,
            'directory',
            'dir'
        );
        $this->info = 'A directory is a file system structure which contains files and other directories.';
    }

    public static function getLabel()
    {
        return 'Directory';
    }
}

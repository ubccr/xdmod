<?php
/**
 * @package OpenXdmod\Storage
 * @author Jeffrey T. Palmer <jtpalmer@buffalo.edu>
 */

namespace DataWarehouse\Query\Storage\GroupBys;

use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Storage\GroupBy;

/**
 * GroupBy for viewing data by system username.
 */
class GroupByUsername extends GroupBy
{
    public function __construct()
    {
        parent::__construct(
            'username',
            array(),
            '
                SELECT DISTINCT
                    gt.user_id AS id,
                    gt.username AS short_name,
                    gt.username AS long_name
                FROM modw_storage.user gt
                WHERE 1
                ORDER BY gt.username
            ',
            array()
        );

        $this->_id_field_name = 'user_id';
        $this->_short_name_field_name = 'username';
        $this->_long_name_field_name = 'username';
        $this->_order_id_field_name = 'username';
        $this->table = new Table(
            $this->schema,
            'user',
            'u'
        );
        $this->info = 'The system username of the user.';
    }

    public static function getLabel()
    {
        return 'System Username';
    }
}

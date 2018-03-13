<?php

namespace DataWarehouse\Query\Cloud\GroupBys;

use DataWarehouse\Query\Query;
use DataWarehouse\Query\Model\Schema;
use DataWarehouse\Query\Model\Table;
use DataWarehouse\Query\Model\WhereCondition;

/*
* @author Rudra Chakraborty
* @date 03/06/2018
*
* Group By Account
*/

class GroupByAccount extends \DataWarehouse\Query\Cloud\GroupBy
{
    public static function getLabel()
    {
        return 'Account';
    }

    public function getInfo()
    {
        return "The account of the principal investigator associated with a VM instance.";
    }

    public function applyTo(Query &$query, Table $data_table) {
        
    }

}

<?php

namespace UnitTesting\DataWarehouse\Query;

use \UnitTesting\mock;

class GroupByPersonTest extends \PHPUnit_Framework_TestCase
{
    public function testGetPossibleValues()
    {
        \DataWarehouse::$mockDatabaseImplementation = $this->getMockBuilder('CCR\DB\iDatabase')->getMock();

        \DataWarehouse::$mockDatabaseImplementation->method('query')-> will(
            $this->returnCallback(
                function ($query, $params) {
                    \PHPUnit_Framework_Assert::assertEquals(
                        "SELECT distinct
                        gt.id,
                        gt.short_name as short_name,
                        gt.long_name as long_name
                        FROM person gt
                        where 1
                        order by gt.order_id
                        ",
                        $query
                    );
                }
            )
        );

        $q = new \DataWarehouse\Query\Jobs\GroupBys\GroupByPerson();
        $q->getPossibleValues();
    }
}

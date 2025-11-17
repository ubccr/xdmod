<?php

namespace ComponentTests;

use CCR\Json;

/**
 * This test is designed for class \DataWarehouse\Query\AggregateQuery
 */

class QueryTest extends BaseTest
{
    private $_query;
    /**
     * @dataProvider QueryDataProvider
     */
    public function testGetDurrationResult($period, $start, $end, $groupby, $expected)
    {
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", self::$XDMOD_REALMS)) {
            $this->markTestSkipped('Needs realm integration.');
        }

        $q = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            $period,
            $start,
            $end,
            $groupby
        );
        $duration = $q->getDurationFormula();
        $this->assertEquals($expected, $duration->getDefinition());
    }

    public function queryDataProvider(){
        $expectedFileName = $this->getTestFiles()->getFile('acls', 'aggregate_durations');
        $expected = JSON::loadFile($expectedFileName);
        return $expected;
    }
}

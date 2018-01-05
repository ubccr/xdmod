<?php

namespace ComponentTests;

use CCR\Json;

/**
 * This test is designed for class \DataWarehouse\Query\Jobs\Aggregate
 */

class AggregateTest extends BaseTest
{
    private $_query;
    /**
     * @dataProvider QueryDataProvider
     */
    public function testGetDurrationResult($period, $start, $end, $groupby, $expected)
    {
        $q = new \DataWarehouse\Query\Jobs\Aggregate(
            $period,
            $start,
            $end,
            $groupby
        );
        $duration = $q->getDurationFormula();
        $this->assertEquals($expected, $duration->getDefinition());
    }

    public function queryDataProvider(){
        $expectedFileName = $this->getTestFile('aggregate_durations.json');
        $expected = JSON::loadFile($expectedFileName);
        return $expected;
    }
}

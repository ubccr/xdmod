<?php

namespace ComponentTests;

use CCR\Json;
use Models\Services\Realms;

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
        //Get current realms enabled
        $xdmod_realms = array();
        $rawRealms = Realms::getRealms();
        foreach($rawRealms as $item) {
            array_push($xdmod_realms,$item->name);
        }
        $this->xdmod_realms = $xdmod_realms;
        //TODO: Needs further integration for other realms
        if (!in_array("jobs", $this->xdmod_realms)) {
            $this->markTestSkipped('Needs realm integration.');
        }

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
        $expectedFileName = $this->getTestFiles()->getFile('acls', 'aggregate_durations');
        $expected = JSON::loadFile($expectedFileName);
        return $expected;
    }
}

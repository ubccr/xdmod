<?php

namespace UnitTesting\DataWarehouse\Query\Jobs\Aggregate;

/**
 * This test is designed for class \DataWarehouse\Query\Jobs\Aggregate
 */

class AggregateTest extends \PHPUnit_Framework_TestCase
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
        return array(
            array(
                'day',
                '2017-09-01',
                '2017-09-30',
                'none',
                '(1)'
            ),
            array(
                'day',
                '2016-11-01',
                '2016-11-30',
                'none',
                '(1)'
            ),
            array(
                'day',
                '2016-12-01',
                '2016-12-15',
                'none',
                '(96)'
            ),
            array(
                'day',
                '2016-12-15',
                '2017-01-05',
                'none',
                '(432)'
            ),
            array(
                'day',
                '2016-12-15',
                '2016-12-29',
                'none',
                '(360)'
            ),
            array(
                'day',
                '2016-12-12',
                '2017-01-01',
                'none',
                '(504)'
            )
        );
    }
}

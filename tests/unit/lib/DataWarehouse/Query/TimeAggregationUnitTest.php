<?php

namespace UnitTests\DataWarehouse\Query;

use DomainException;
use Exception;
use UnitTests\TestHelpers\mock;

class TimeAggregationUnitTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidTimePeriod(): void
    {
        $this->expectException(Exception::class);
        \DataWarehouse\Query\TimeAggregationUnit::factory('era', 'Palenzoic', 'Mesoproterozoic', 'fossilfact_by');
    }

    public function testGetRawTimePeriod(): void
    {
        $timep = 1_420_088_400;

        [$start_date, $end_date] = \DataWarehouse\Query\TimeAggregationUnit::getRawTimePeriod($timep, 'day');
        $this->assertEquals('2015-01-01', $start_date);
        $this->assertEquals('2015-01-01', $end_date);

        [$start_date, $end_date] = \DataWarehouse\Query\TimeAggregationUnit::getRawTimePeriod($timep, 'month');
        $this->assertEquals('2015-01-01', $start_date);
        $this->assertEquals('2015-01-31', $end_date);

        [$start_date, $end_date] = \DataWarehouse\Query\TimeAggregationUnit::getRawTimePeriod($timep, 'quarter');
        $this->assertEquals('2015-01-01', $start_date);
        $this->assertEquals('2015-03-31', $end_date);

        [$start_date, $end_date] = \DataWarehouse\Query\TimeAggregationUnit::getRawTimePeriod($timep, 'year');
        $this->assertEquals('2015-01-01', $start_date);
        $this->assertEquals('2015-12-31', $end_date);
    }

    public function testGetRawTimePeriodInvalid1(): void
    {
        $this->expectException(DomainException::class);
        \DataWarehouse\Query\TimeAggregationUnit::getRawTimePeriod('seven', 'day');
    }

    public function testGetRawTimePeriodInvalid2(): void
    {
        $this->expectException(DomainException::class);
        \DataWarehouse\Query\TimeAggregationUnit::getRawTimePeriod(142_008_840, 'millenium');
    }


    public function testDeriveAggregationUnitName(): void
    {
        $aggname = \DataWarehouse\Query\TimeAggregationUnit::deriveAggregationUnitName('auto', '2016-01-01', '2016-01-30');
        $this->assertEquals($aggname, 'day');

        $aggname = \DataWarehouse\Query\TimeAggregationUnit::deriveAggregationUnitName('auto', '2016-01-01', '2016-06-30');
        $this->assertEquals($aggname, 'day');

        $aggname = \DataWarehouse\Query\TimeAggregationUnit::deriveAggregationUnitName('auto', '2016-01-01', '2016-07-01');
        $this->assertEquals($aggname, 'month');

        $aggname = \DataWarehouse\Query\TimeAggregationUnit::deriveAggregationUnitName('auto', '2006-01-01', '2015-12-31');
        $this->assertEquals($aggname, 'month');

        $aggname = \DataWarehouse\Query\TimeAggregationUnit::deriveAggregationUnitName('auto', '2006-01-01', '2016-01-01');
        $this->assertEquals($aggname, 'quarter');
    }
}

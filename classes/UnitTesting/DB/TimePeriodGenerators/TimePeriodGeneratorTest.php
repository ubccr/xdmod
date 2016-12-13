<?php

require_once __DIR__.'/../../../../configuration/linker.php';

require_once __DIR__ . '/../../TestHelper.php';

/**
 * Tests for TimePeriodGenerator and its subclasses.
 */
class TimePeriodGeneratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * A cache of TimePeriodGenerator instances.
     *
     * @var array
     */
    private $generatorCache = array();

    /**
     * Get an instance of a TimePeriodGenerator for the given unit.
     *
     * @param  string $unitName    The name of the unit to get a generator for.
     * @return TimePeriodGenerator The TimePeriodGenerator for the unit.
     */
    private function getGenerator($unitName)
    {
        if (!array_key_exists($unitName, $this->generatorCache)) {
            $this->generatorCache[$unitName] = TimePeriodGenerator::getGeneratorForUnit($unitName);
        }
        return $this->generatorCache[$unitName];
    }

    /**
     * Format test data for consumption by a test.
     *
     * @param  array $testData The test data to format.
     * @return array           Formatted test data.
     */
    private function flattenTestData(array $testData)
    {
        $flatTestData = array();
        foreach ($testData as $input => $expectedData) {
            foreach ($expectedData as $unit => $expected) {
                $flatTestData[] = array($unit, $input, $expected);
            }
        }
        return $flatTestData;
    }

    /**
     * Test a TimePeriodGenerator method.
     *
     * @param  string $unit        The name of the unit associated with the
     *                             TimePeriodGenerator to test.
     * @param  string $methodName  The name of the generator method to test.
     * @param  array  $inputArgs   The input arguments to the method.
     * @param  mixed  $expected    The expected output for the input.
     */
    private function doGeneratorMethodTest($unit, $methodName, array $inputArgs, $expected)
    {
        $generator = $this->getGenerator($unit);
        $generatorMethod = TestHelper::unlockMethod($generator, $methodName);
        $this->assertEquals(
            $expected,
            $generatorMethod->invokeArgs($generator, $inputArgs)
        );
    }

    /**
     * Test the function that gets the next time period start from a DateTime.
     *
     * @dataProvider nextStartProvider
     *
     * @param  string $unit        The name of the unit associated with the
     *                             TimePeriodGenerator to test.
     * @param  string $inputStr    A string representing the DateTime to use as
     *                             an input.
     * @param  string $expectedStr A string representing the DateTime to use as
     *                             the expected output.
     */
    public function testNextStart($unit, $inputStr, $expectedStr)
    {
        $this->doGeneratorMethodTest(
            $unit,
            'getNextTimePeriodStart',
            array(new DateTime($inputStr)),
            new DateTime($expectedStr)
        );
    }

    /**
     * Provide data for next time period start tests.
     *
     * @return array The arguments to use for the next time period start tests.
     */
    public function nextStartProvider()
    {
        return $this->flattenTestData(array(
            '2016-01-01T00:00:00' => array(
                'day' => '2016-01-02T00:00:00',
                'month' => '2016-02-01T00:00:00',
                'quarter' => '2016-04-01T00:00:00',
                'year' => '2017-01-01T00:00:00',
            ),
            '2016-08-24T12:00:00' => array(
                'day' => '2016-08-25T00:00:00',
                'month' => '2016-09-01T00:00:00',
                'quarter' => '2016-10-01T00:00:00',
                'year' => '2017-01-01T00:00:00',
            ),
            '2016-12-31T23:59:59' => array(
                'day' => '2017-01-01T00:00:00',
                'month' => '2017-01-01T00:00:00',
                'quarter' => '2017-01-01T00:00:00',
                'year' => '2017-01-01T00:00:00',
            ),
        ));
    }

    /**
     * Test the function that gets the time period end from a DateTime.
     *
     * @dataProvider endProvider
     *
     * @param  string $unit        The name of the unit associated with the
     *                             TimePeriodGenerator to test.
     * @param  string $inputStr    A string representing the DateTime to use as
     *                             an input.
     * @param  string $expectedStr A string representing the DateTime to use as
     *                             the expected output.
     */
    public function testEnd($unit, $inputStr, $expectedStr)
    {
        $this->doGeneratorMethodTest(
            $unit,
            'getTimePeriodEnd',
            array(new DateTime($inputStr)),
            new DateTime($expectedStr)
        );
    }

    /**
     * Provide data for time period end tests.
     *
     * @return array The arguments to use for the time period end tests.
     */
    public function endProvider()
    {
        return $this->flattenTestData(array(
            '2016-01-01T00:00:00' => array(
                'day' => '2016-01-01T23:59:59',
                'month' => '2016-01-31T23:59:59',
                'quarter' => '2016-03-31T23:59:59',
                'year' => '2016-12-31T23:59:59',
            ),
            '2016-08-24T12:00:00' => array(
                'day' => '2016-08-24T23:59:59',
                'month' => '2016-08-31T23:59:59',
                'quarter' => '2016-09-30T23:59:59',
                'year' => '2016-12-31T23:59:59',
            ),
            '2016-12-31T23:59:59' => array(
                'day' => '2016-12-31T23:59:59',
                'month' => '2016-12-31T23:59:59',
                'quarter' => '2016-12-31T23:59:59',
                'year' => '2016-12-31T23:59:59',
            ),
        ));
    }

    /**
     * Test the function that gets the time period index from a DateTime.
     *
     * @dataProvider indexProvider
     *
     * @param  string $unit        The name of the unit associated with the
     *                             TimePeriodGenerator to test.
     * @param  string $inputStr    A string representing the DateTime to use as
     *                             an input.
     * @param  mixed  $expected    The expected output.
     */
    public function testIndex($unit, $inputStr, $expected)
    {
        $this->doGeneratorMethodTest(
            $unit,
            'getTimePeriodInYear',
            array(new DateTime($inputStr)),
            $expected
        );
    }

    /**
     * Provide data for time period index tests.
     *
     * @return array The arguments to use for the time period index tests.
     */
    public function indexProvider()
    {
        return $this->flattenTestData(array(
            '2016-01-01T00:00:00' => array(
                'day' => 1,
                'month' => 1,
                'quarter' => 1,
                'year' => 0,
            ),
            '2016-08-24T12:00:00' => array(
                'day' => 237,
                'month' => 8,
                'quarter' => 3,
                'year' => 0,
            ),
            '2016-12-31T23:59:59' => array(
                'day' => 366,
                'month' => 12,
                'quarter' => 4,
                'year' => 0,
            ),
        ));
    }

    /**
     * Test the function that gets the time period start from a DateTime.
     *
     * @dataProvider startProvider
     *
     * @param  string $unit        The name of the unit associated with the
     *                             TimePeriodGenerator to test.
     * @param  string $inputStr    A string representing the DateTime to use as
     *                             an input.
     * @param  string $expectedStr A string representing the DateTime to use as
     *                             the expected output.
     */
    public function testStart($unit, $inputStr, $expectedStr)
    {
        $this->doGeneratorMethodTest(
            $unit,
            'getTimePeriodStart',
            array(new DateTime($inputStr)),
            new DateTime($expectedStr)
        );
    }

    /**
     * Provide data for time period start tests.
     *
     * @return array The arguments to use for the time period start tests.
     */
    public function startProvider()
    {
        return $this->flattenTestData(array(
            '2016-01-01T00:00:00' => array(
                'day' => '2016-01-01T00:00:00',
                'month' => '2016-01-01T00:00:00',
                'quarter' => '2016-01-01T00:00:00',
                'year' => '2016-01-01T00:00:00',
            ),
            '2016-08-24T12:00:00' => array(
                'day' => '2016-08-24T00:00:00',
                'month' => '2016-08-01T00:00:00',
                'quarter' => '2016-07-01T00:00:00',
                'year' => '2016-01-01T00:00:00',
            ),
            '2016-12-31T23:59:59' => array(
                'day' => '2016-12-31T00:00:00',
                'month' => '2016-12-01T00:00:00',
                'quarter' => '2016-10-01T00:00:00',
                'year' => '2016-01-01T00:00:00',
            ),
        ));
    }

    /**
     * Test conversion from database strings to DateTimes.
     *
     * @dataProvider databaseDateTimeProvider
     *
     * @param  DateTime $expectedDateTime The expected DateTime.
     * @param  string   $inputStr         The input string.
     */
    public function testStringToDateTime($expectedDateTime, $inputStr)
    {
        $this->doGeneratorMethodTest('day', 'getDatabaseDateTime', array($inputStr), $expectedDateTime);
    }

    /**
     * Test conversion from DateTimes to database strings.
     *
     * @dataProvider databaseDateTimeProvider
     *
     * @param  DateTime $inputDateTime The input DateTime.
     * @param  string   $expectedStr   The expected string.
     */
    public function testDateTimeToString($inputDateTime, $expectedStr)
    {
        $this->doGeneratorMethodTest('day', 'getDatabaseDateTimeString', array($inputDateTime), $expectedStr);
    }

    /**
     * Provide data for the database/DateTime conversion tests.
     *
     * @return array The arguments to use for the conversion tests.
     */
    public function databaseDateTimeProvider()
    {
        return array(
            array(new DateTime('2016-01-01T00:00:00'), '2016-01-01 00:00:00'),
            array(new DateTime('2016-08-24T12:00:00'), '2016-08-24 12:00:00'),
            array(new DateTime('2016-12-31T23:59:59'), '2016-12-31 23:59:59'),
        );
    }

    /**
     * Test extraction of the year from a DateTime.
     *
     * @dataProvider yearFromDateTimeProvider
     *
     * @param  DateTime $inputDateTime The input DateTime.
     * @param  int      $expectedYear  The expected year.
     */
    public function testYearFromDateTime($inputDateTime, $expectedYear)
    {
        $this->doGeneratorMethodTest('day', 'getYearFromDateTime', array($inputDateTime), $expectedYear);
    }

    /**
     * Provide data for the year extraction test.
     *
     * @return array The arguments to use for the extraction test.
     */
    public function yearFromDateTimeProvider()
    {
        return array(
            array(new DateTime('2016-01-01T00:00:00'), 2016),
            array(new DateTime('2016-08-24T12:00:00'), 2016),
            array(new DateTime('2016-12-31T23:59:59'), 2016),
            array(new DateTime('1985-10-26T01:20:00'), 1985),
        );
    }

    /**
     * Tests generation of time period IDs.
     *
     * @dataProvider timePeriodIdProvider
     *
     * @param  int    $inputYear  The input year of the time period.
     * @param  int    $inputIndex The input index of the time period.
     * @param  int    $expectedId The expected time period ID.
     */
    public function testTimePeriodId($inputYear, $inputIndex, $expectedId)
    {
        $this->doGeneratorMethodTest('day', 'getTimePeriodId', array($inputYear, $inputIndex), $expectedId);
    }

    /**
     * Provide data for the time period ID test.
     *
     * @return array The arguments to use for the ID test.
     */
    public function timePeriodIdProvider()
    {
        return array(
            array(2016, 0, 201600000),
            array(2016, 1, 201600001),
            array(2016, 366, 201600366),
            array(1985, 299, 198500299),
        );
    }

    /**
     * Test the calculation of data about a range of time.
     *
     * @dataProvider timestampsAndTotalsProvider
     *
     * @param  DateTime $inputStart      The input start of the range.
     * @param  DateTime $inputEnd        The input end of the range.
     * @param  array    $expectedResults The expected results.
     */
    public function testTimestampsAndTotals($inputStart, $inputEnd, $expectedResults)
    {
        $this->doGeneratorMethodTest('day', 'getTimestampsAndTotals', array($inputStart, $inputEnd), $expectedResults);
    }

    /**
     * Provide data for the time range data test.
     *
     * @return array The arguments to use for the time range data test.
     */
    public function timestampsAndTotalsProvider()
    {
        $utc = new DateTimeZone('UTC');
        return array(
            array(
                new DateTime('2016-01-01T00:00:00', $utc),
                new DateTime('2016-01-01T00:00:00', $utc),
                array(
                    'start_ts' => 1451606400,
                    'middle_ts' => 1451606400,
                    'end_ts' => 1451606400,
                    'total_hours' => 0.00027777777777778,
                    'total_seconds' => 1,
                ),
            ),
            array(
                new DateTime('2016-01-01T00:00:00', $utc),
                new DateTime('2016-01-01T00:05:00', $utc),
                array(
                    'start_ts' => 1451606400,
                    'middle_ts' => 1451606550,
                    'end_ts' => 1451606700,
                    'total_hours' => 0.083611111111111,
                    'total_seconds' => 301,
                ),
            ),
            array(
                new DateTime('2016-01-01T00:00:00', $utc),
                new DateTime('2016-12-31T23:59:59', $utc),
                array(
                    'start_ts' => 1451606400,
                    'middle_ts' => 1467417599.5,
                    'end_ts' => 1483228799,
                    'total_hours' => 8784,
                    'total_seconds' => 31622400,
                ),
            ),
        );
    }
}

<?php
/**
 * This various aspects of the Query class as well as parts of the Realm, GroupBy, and Statistics
 * classes that require database access.
 */

namespace ComponentTests\Query;

use CCR\Log as Logger;
use DataWarehouse\Query\AggregateQuery;

class TimeseriesQueryTest extends \PHPUnit\Framework\TestCase
{

    protected static $logger = null;

    public static function setupBeforeClass(): void
    {
        // Set up a logger so we can get warnings and error messages

        $conf = array(
            'file' => false,
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Logger::EMERG
        );
        self::$logger = Logger::factory('PHPUnit', $conf);

        // In order to use a non-standard location for datawarehouse.json we must manually
        // initialize the Realm class.

        $options = (object) array(
            'config_file_name' => 'datawarehouse.json',
            'config_base_dir'  => realpath('../artifacts/xdmod/realm')
        );

        \Realm\Realm::initialize(self::$logger, $options);
    }

    /**
     * Simulate execution of a TimeseriesQuery. TimeseriesChart creates a timeseries query and
     * then passes it into a SimpleTimeseriesDataset which executes an aggregate query to get all
     * dimension values. The timeseries query is then executed using the values of the aggregate
     * query in the HAVING clause via the SimpleTimeseriesDataIterator.
     */

    public function testTimeseriesQuery()
    {
        $query = new \DataWarehouse\Query\TimeseriesQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            null,
            null,
            array(),
            self::$logger
        );

        // Simulate TimeseriesChart configure

        $data_description = (object) array(
            'sort_type' => 'value_desc',
            'group_by' => 'person',
            'metric' => 'job_count'
        );

        $query->addGroupBy($data_description->group_by);
        $query->addStat($data_description->metric);
        $query->addOrderByAndSetSortInfo($data_description);

        $generated = $query->getQueryString(10, 0, 'person_id = 82');
        $expected  =<<<SQL
SELECT
  duration.id as 'day_id',
  DATE(duration.day_start) as 'day_short_name',
  DATE(duration.day_start) as 'day_name',
  duration.day_start_ts as 'day_start_ts',
  person.id as 'person_id',
  person.short_name as 'person_short_name',
  person.long_name as 'person_name',
  person.order_id as 'person_order_id',
  COALESCE(SUM(agg.ended_job_count), 0) AS job_count
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days duration,
  modw.person person
WHERE
  duration.id = agg.day_id
  AND agg.day_id between 201600357 and 201700001
  AND person.id = agg.person_id
GROUP BY duration.id,
  person.id
HAVING person_id = 82
ORDER BY duration.id ASC,
  person.order_id ASC
LIMIT 10 OFFSET 0
SQL;
        $this->assertEquals($expected, $generated, 'Timeseries query');

        $aggQuery = $query->getAggregateQuery();

        $generatedAgg = $aggQuery->getQueryString(10, 0);

        $expectedAgg =<<<SQL
SELECT
  person.id as 'person_id',
  person.short_name as 'person_short_name',
  person.long_name as 'person_name',
  person.order_id as 'person_order_id',
  COALESCE(SUM(agg.ended_job_count), 0) AS job_count
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days duration,
  modw.person person
WHERE
  duration.id = agg.day_id
  AND agg.day_id between 201600357 and 201700001
  AND person.id = agg.person_id
GROUP BY person.id
ORDER BY job_count desc,
  person.order_id ASC
LIMIT 10 OFFSET 0
SQL;
        $this->assertEquals($expectedAgg, $generatedAgg, 'Timeseries associated Aggregate query');
    }
}

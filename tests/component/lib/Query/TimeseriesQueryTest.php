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
     * Simulate execution of a TimeseriesQuery.
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
  day_short_name,
  day_name,
  duration.day_start_ts,
  person.id,
  person.short_name,
  person.long_name,
  person.order_id
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
GROUP BY person.id,
  person.short_name,
  person.long_name,
  person.order_id
ORDER BY job_count desc,
  person.order_id ASC
LIMIT 10 OFFSET 0
SQL;
        $this->assertEquals($expectedAgg, $generatedAgg, 'Timeseries associated Aggregate query');
    }

    /**
     * Test a timeseries query containing a statistic with derived fields.
     */

    public function testTimeseriesQueryDerivedStatistic()
    {
        $query = new \DataWarehouse\Query\TimeseriesQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            'person',
            'job_count'
        );
        $query->addStatField($this->getUtilizationStatistic());
        $query->addOrderBy('utilization', 'desc');

        $generated = $query->getQueryString(10, 0, 'person_id = 82');
        $expected =<<<SQL
SELECT
  derived_stats.`person_id` AS 'person_id',
  derived_stats.`person_short_name` AS 'person_short_name',
  derived_stats.`person_name` AS 'person_name',
  derived_stats.`person_order_id` AS 'person_order_id',
  derived_stats.`day_id` AS 'day_id',
  derived_stats.`day_short_name` AS 'day_short_name',
  derived_stats.`day_name` AS 'day_name',
  derived_stats.`day_start_ts` AS 'day_start_ts',
  derived_stats.running_job_count AS running_job_count,
  derived_stats.job_count AS job_count,
  COALESCE((derived_stats.utilization__numerator / NULLIF((SELECT SUM(rsa.allocated_cpu_core_time) FROM modw_aggregates.resourcespecsfact_by_day AS rsa WHERE rsa.day_id = derived_stats.utilization__period_id AND FIND_IN_SET(rsa.record_resource_id, derived_stats.utilization__resource_ids) <> 0), 0)) * 100, 0) AS utilization
FROM (
SELECT
  person.id as 'person_id',
  person.short_name as 'person_short_name',
  person.long_name as 'person_name',
  person.order_id as 'person_order_id',
  duration.id as 'day_id',
  DATE(duration.day_start) as 'day_short_name',
  DATE(duration.day_start) as 'day_name',
  duration.day_start_ts as 'day_start_ts',
  SUM(agg.running_job_count) AS running_job_count,
  COALESCE(SUM(agg.ended_job_count), 0) AS job_count,
  SUM(agg.cpu_time) AS utilization__numerator,
  GROUP_CONCAT(DISTINCT agg.task_resource_id) AS utilization__resource_ids,
  MIN(agg.day_id) AS utilization__period_id,
  MIN(person.order_id) AS _order_1,
  MIN(duration.id) AS _order_2
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days duration,
  modw.person person
WHERE
  duration.id = agg.day_id
  AND agg.day_id between 201600357 and 201700001
  AND person.id = agg.person_id
GROUP BY person.id,
  person.short_name,
  person.long_name,
  person.order_id,
  duration.id,
  duration.day_start,
  duration.day_start_ts
) AS derived_stats
HAVING person_id = 82
ORDER BY utilization desc,
  derived_stats._order_1 ASC,
  derived_stats._order_2 ASC
LIMIT 10 OFFSET 0
SQL;
        $this->assertEquals($expected, $generated, 'Timeseries query with derived statistic');
    }

    /**
     * Load the real 'utilization' statistic from the Jobs realm's configuration. It is evaluated
     * against a derived table because its formula needs the per-group set of resources and their
     * total CPU time inside a correlated subquery, which ONLY_FULL_GROUP_BY otherwise rejects
     * unless the grouping is done in a derived table.
     *
     * @return \Realm\Statistic
     */

    private function getUtilizationStatistic()
    {
        $config = json_decode(file_get_contents(
            realpath('../../configuration/datawarehouse.d/ref/Jobs-statistics.json')
        ))->utilization;

        return \Realm\Statistic::factory('utilization', $config, \Realm\Realm::factory('Jobs', self::$logger), self::$logger);
    }
}

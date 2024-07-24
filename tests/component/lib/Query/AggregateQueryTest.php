<?php
/**
 * This various aspects of the Query class as well as parts of the Realm, GroupBy, and Statistics
 * classes that require database access.
 */

namespace ComponentTests\Query;

use CCR\Log as Logger;
use DataWarehouse\Query\AggregateQuery;
use Psr\Log\LoggerInterface;

class AggregateQueryTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var LoggerInterface|null
     */
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
     * Create an aggregate query with no group by or statistic. We will not be able to generate the
     * query string with no fields for the SELECT clause.
     *
     *
     */

    public function testAggregateQueryNoStatisticNoGroupBy()
    {
        $this->expectException(\Exception::class);
        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31'
        );
        $query->getQueryString();
    }

    /**
     * Create an aggregate query with a group by but no statistic. The select field will contain the
     * group by id, short name, and name.
     */

    public function testAggregateQueryNoStatistic()
    {
        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            'person'
        );

        $generated = $query->getQueryString();
        $expected  =<<<SQL
SELECT
  person.id as 'person_id',
  person.short_name as 'person_short_name',
  person.long_name as 'person_name',
  person.order_id as 'person_order_id'
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days duration,
  modw.person person
WHERE
  duration.id = agg.day_id
  AND agg.day_id between 201600357 and 201700001
  AND person.id = agg.person_id
GROUP BY person.id
ORDER BY person.order_id ASC
SQL;
        $this->assertEquals($expected, $generated, 'Aggregate query no statistic');
    }

    /**
     * Create an aggregate query with a group by but no statistic. The select field will contain the
     * group by id, short name, and name.
     */

    public function testAggregateQueryWithAlternateGroupByColumn()
    {
        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            'username'
        );

        $generated = $query->getQueryString();
        $expected  =<<<SQL
SELECT
  systemaccount.username as 'username_id',
  systemaccount.username as 'username_short_name',
  systemaccount.username as 'username_name',
  systemaccount.username as 'username_order_id'
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days duration,
  modw.systemaccount systemaccount
WHERE
  duration.id = agg.day_id
  AND agg.day_id between 201600357 and 201700001
  AND systemaccount.id = agg.systemaccount_id
GROUP BY systemaccount.username
ORDER BY systemaccount.username ASC
SQL;
        $this->assertEquals($expected, $generated, 'Aggregate query with alternate group by column');
    }

    /**
     * Create an aggregate query with a group by and statistic. This adds all available statistics
     * to the query.
     */

    public function testAggregateQueryGroupByAndStatistic()
    {
        // Specifying the statistic in the constructor calls Query::setStat() which adds all
        // available statistics. Note that the order of the statistics in the query are dependent on
        // the oder specified in the config file.

        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            'person',
            'job_count'
        );

        $generated = $query->getQueryString();
        $expected  =<<<SQL
SELECT
  person.id as 'person_id',
  person.short_name as 'person_short_name',
  person.long_name as 'person_name',
  person.order_id as 'person_order_id',
  COALESCE(SUM(CASE duration.id WHEN 201600357 THEN agg.running_job_count ELSE agg.started_job_count END), 0) AS running_job_count,
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
ORDER BY person.order_id ASC
SQL;
        $this->assertEquals($expected, $generated, 'Aggregate query group by and main statistic');
    }

    /**
     * Create an aggregate query with a statistic but no group by. This is essentially a group by None.
     */

    public function testAggregateQueryStatisticNoGroupBy()
    {
        // Specifying the statistic in the constructor calls Query::setStat() which adds all
        // available statistics. Note that the order of the statistics in the query are dependent on
        // the oder specified in the config file.

        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            null,
            'job_count'
        );

        $generated = $query->getQueryString();

        // The query have have optional group by, order by, and limit fields. Due to the way it is
        // constructed, the blank line at the end of the query is required if none of these are
        // present.

        $expected  =<<<SQL
SELECT
  COALESCE(SUM(CASE duration.id WHEN 201600357 THEN agg.running_job_count ELSE agg.started_job_count END), 0) AS running_job_count,
  COALESCE(SUM(agg.ended_job_count), 0) AS job_count
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days duration
WHERE
  duration.id = agg.day_id
  AND agg.day_id between 201600357 and 201700001

SQL;
        $this->assertEquals($expected, $generated, 'Aggregate query main statistic no group by');
    }

    /**
     * Create an aggregate query and then add a group by and a statistic.
     */

    public function testAggregateQueryAddGroupByAndStatistic()
    {
        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31'
        );
        $query->addGroupBy('person');
        $query->addStat('job_count');

        $generated = $query->getQueryString();
        $expected  =<<<SQL
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
ORDER BY person.order_id ASC
SQL;
        $this->assertEquals($expected, $generated, 'Aggregate query add group by and statistic');
    }

    /**
     * Create an aggregate query with a group by and then add a statistic.
     */

    public function testAggregateQueryWithGroupByAddStatistic()
    {
        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            'person'
        );
        $query->addStat('job_count');

        // addOrderByAndSetSortInfo() is called from ComplexDataset and TimeseriesChart and
        // prepends the metric to the ORDER BY clause. Simulate that here.

        $data_description = (object) array(
            'sort_type' => 'value_desc',
            'group_by'  => 'person',
            'metric'    => 'job_count'
        );
        $query->addOrderByAndSetSortInfo($data_description);

        $generated = $query->getQueryString(10, 0); // Also test limit=10 and offset=0
        $expected  =<<<SQL
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
        $this->assertEquals($expected, $generated, 'Aggregate query with group by add statistic');

        $generated = $query->getCountQueryString();
        $expected =<<<SQL
SELECT
  COUNT(*) AS row_count
FROM (
  SELECT
  SUM(1) AS total
  FROM
    modw_aggregates.jobfact_by_day agg,
    modw.days duration,
    modw.person person
  WHERE
    duration.id = agg.day_id
    AND agg.day_id between 201600357 and 201700001
    AND person.id = agg.person_id
  GROUP BY
    person.id
) AS a WHERE a.total IS NOT NULL
SQL;
        $this->assertEquals($expected, $generated, 'Aggregate query count with group by add statistic ');
    }

    /**
     * Test adding an additional JOIN constraint to the query, as is used in GroupByQueue.
     */

    public function testAggregateQueryAdditionalJoinConstraint()
    {
        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31'
        );
        $query->addGroupBy('queue');
        $query->addStat('job_count');

        // addOrderByAndSetSortInfo() is called from ComplexDataset and TimeseriesChart and
        // prepends the metric to the ORDER BY clause. Simulate that here.

        $data_description = (object) array(
            'sort_type' => 'value_desc',
            'group_by'  => 'queue',
            'metric'    => 'job_count'
        );
        $query->addOrderByAndSetSortInfo($data_description);


        $generated = $query->getQueryString();
        $expected  =<<<SQL
SELECT
  queue.id as 'queue_id',
  queue.id as 'queue_short_name',
  queue.id as 'queue_name',
  queue.id as 'queue_order_id',
  COALESCE(SUM(agg.ended_job_count), 0) AS job_count
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days duration,
  modw.queue queue
WHERE
  duration.id = agg.day_id
  AND agg.day_id between 201600357 and 201700001
  AND queue.id = agg.queue
  AND queue.resource_id = agg.task_resource_id
GROUP BY queue.id
ORDER BY job_count desc,
  queue.id ASC
SQL;
        $this->assertEquals($expected, $generated, 'Additional join constraint');
    }

    /**
     * Test using group by none, which is a special case of aggregation unit group-by.
     */

    public function testAggregateQueryGroupByNone()
    {
        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31'
        );
        $query->addGroupBy('none');
        $query->addStat('job_count');

        // addOrderByAndSetSortInfo() is called from ComplexDataset and TimeseriesChart and
        // prepends the metric to the ORDER BY clause. Simulate that here.

        $data_description = (object) array(
            'sort_type' => 'value_desc',
            'group_by'  => 'none',
            'metric'    => 'job_count'
        );
        $query->addOrderByAndSetSortInfo($data_description);
        $generated = $query->getQueryString();
        $expected =<<<SQL
SELECT
  -9999 as 'none_id',
  'Screwdriver' as 'none_short_name',
  'Screwdriver' as 'none_name',
  'Screwdriver' as 'none_order_id',
  COALESCE(SUM(agg.ended_job_count), 0) AS job_count
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days duration
WHERE
  duration.id = agg.day_id
  AND agg.day_id between 201600357 and 201700001

ORDER BY job_count desc
SQL;
        $this->assertEquals($expected, $generated, 'Aggregate query group by none');
    }

    /**
     * Test the results of getQueryString() with the group by specified in the constructor and
     * separately by calling addGroupBy(). The results should be the same.
     */

    public function testAggregateQuerySpecifyGroupBy()
    {
        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            'person'
        );
        $generated1 = $query->getQueryString();

        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31'
        );
        $query->addGroupBy('person');
        $generated2 = $query->getQueryString();
        $this->assertEquals($generated1, $generated2, 'AggregateQuery getQueryString() depending on how group by is applied');
    }

    /**
     * Test the addWhereJoin() method.
     */

    public function testAddWhereJoin()
    {
        $expected =<<<SQL
SELECT
  person.id as 'person_id',
  person.short_name as 'person_short_name',
  person.long_name as 'person_name',
  person.order_id as 'person_order_id'
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days duration,
  modw.person person
WHERE
  duration.id = agg.day_id
  AND agg.day_id between 201600357 and 201700001
  AND person.id = agg.person_id
  AND person.id > ('constraint')
GROUP BY person.id
ORDER BY person.order_id ASC
SQL;

        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            'person'
        );
        $query->addWhereAndJoin('person', '>', 'constraint');

        $generated = $query->getQueryString();
        $this->assertEquals($expected, $generated, 'AggregateQuery::addWhereJoin()');
    }

    /**
     * Test the Statistic::getFormula() method where macros in the formula are replaced with values from
     * the Query class.
     */

    public function testGetFormula()
    {
        $query = new \DataWarehouse\Query\AggregateQuery(
            'Cloud',
            'day',
            '2017-12-01',
            '2019-01-31',
            'configuration'
        );

        $realm = \Realm\Realm::factory('Cloud', self::$logger);
        $statistic = $realm->getStatisticObject('cloud_num_sessions_running');
        $generated = $statistic->getFormula($query);
        $expected = 'COALESCE(SUM(CASE duration.id WHEN 201800108 THEN agg.num_sessions_running ELSE agg.num_sessions_started END), 0) AS cloud_num_sessions_running';
        $this->assertEquals($expected, $generated, 'getFormula()');
    }

    /**
     * Test the count query.
     */

    public function testAggregateQueryCount()
    {
        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            'person'
        );
        $query->addStat('job_count');

        $generated = $query->getCountQueryString();
        $expected =<<<SQL
SELECT
  COUNT(*) AS row_count
FROM (
  SELECT
  SUM(1) AS total
  FROM
    modw_aggregates.jobfact_by_day agg,
    modw.days duration,
    modw.person person
  WHERE
    duration.id = agg.day_id
    AND agg.day_id between 201600357 and 201700001
    AND person.id = agg.person_id
  GROUP BY
    person.id
) AS a WHERE a.total IS NOT NULL
SQL;
        $this->assertEquals($expected, $generated, 'Aggregate query count');
    }

    /**
     * Test the dimension values query. Note that the dimension values query does not prefix id,
     * name, or short_name with the group by id.
     */

    public function testAggregateQueryDimensionValues()
    {
        // Note that calling getDimensionValuesQuery() on a query class that specifies start and end
        // dates will fail due to a hard-coded array index reference.

        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            null,
            null,
            'person'
        );
        $query->addStat('job_count');

        $generated = $query->getDimensionValuesQuery();
        $expected =<<<SQL
SELECT
  person.id AS id,
  person.long_name AS name,
  person.short_name AS short_name,
  person.order_id AS _dimensionOrderValue
FROM modw.person person
WHERE person.id IN ( SELECT modw_filters.Jobs_person.person FROM modw_filters.Jobs_person )
GROUP BY person.id
ORDER BY person.order_id ASC
SQL;
        $this->assertEquals($expected, $generated, 'Aggregate query dimension values');
    }
}

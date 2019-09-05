<?php
/**
 * This various aspects of the Query class as well as parts of the Realm, GroupBy, and Statistics
 * classes that require database access.
 */

namespace ComponentTests\Query;

use CCR\Log as Logger;
use DataWarehouse\Query\AggregateQuery;

class AggregateQueryTest extends \PHPUnit_Framework_TestCase
{

    protected static $logger = null;

    public static function setupBeforeClass()
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
     * @expectedException Exception
     */

    public function testAggregateQueryNoStatisticNoGroupBy()
    {
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

    public function testAggregateQueryNoGroupBy()
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
  person.short_name as person_short_name,
  person.long_name as person_name,
  person.order_id as person_order_id
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
     * Create an aggregate query with a group by and statistic. This adds all available statistics
     * to the query.
     */

    public function testAggregateQueryGroupByAndStatistic()
    {
        // Specifying the statistic in the constructor calls Query::setStat() which adds all
        // available statistics.

        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            'person',
            'Jobs_job_count'
        );

        $generated = $query->getQueryString();
        $expected  =<<<SQL
SELECT
  person.id as 'person_id',
  person.short_name as person_short_name,
  person.long_name as person_name,
  person.order_id as person_order_id,
  COALESCE(SUM(agg.ended_job_count), 0) AS Jobs_job_count,
  SUM(agg.running_job_count) AS Jobs_running_job_count
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
        // available statistics.

        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            null,
            'Jobs_job_count'
        );

        $generated = $query->getQueryString();

        // The query have have optional group by, order by, and limit fields. Due to the way it is
        // constructed, the blank line at the end of the query is required if none of these are
        // present.

        $expected  =<<<SQL
SELECT
  COALESCE(SUM(agg.ended_job_count), 0) AS Jobs_job_count,
  SUM(agg.running_job_count) AS Jobs_running_job_count
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
        $query->addStat('Jobs_job_count');

        $generated = $query->getQueryString();
        $expected  =<<<SQL
SELECT
  person.id as 'person_id',
  person.short_name as person_short_name,
  person.long_name as person_name,
  person.order_id as person_order_id,
  COALESCE(SUM(agg.ended_job_count), 0) AS Jobs_job_count
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
        $query->addStat('Jobs_job_count');

        // addOrderByAndSetSortInfo() is called from ComplexDataset and HighChartTimeseries2 and
        // prepends the metric to the ORDER BY clause. Simulate that here.

        $data_description = (object) array(
            'sort_type' => 'value_desc',
            'group_by'  => 'person',
            'metric'    => 'Jobs_job_count'
        );
        $query->addOrderByAndSetSortInfo($data_description);

        $generated = $query->getQueryString(10, 0); // Also test limit=10 and offset=0
        $expected  =<<<SQL
SELECT
  person.id as 'person_id',
  person.short_name as person_short_name,
  person.long_name as person_name,
  person.order_id as person_order_id,
  COALESCE(SUM(agg.ended_job_count), 0) AS Jobs_job_count
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days duration,
  modw.person person
WHERE
  duration.id = agg.day_id
  AND agg.day_id between 201600357 and 201700001
  AND person.id = agg.person_id
GROUP BY person.id
ORDER BY Jobs_job_count desc,
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
     * Create an aggregate query and then add a statistic and a group by that has a multi-column key.
     */

    public function testAggregateQueryAddStatisticAddMultiKeyGroupBy()
    {
        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31'
        );
        $query->addGroupBy('resource');
        $query->addStat('Jobs_job_count');

        $generated = $query->getQueryString();
        $expected  =<<<SQL
SELECT
  resourcefact.id as 'resource_id',
  resourcefact.code as 'resource_code',
  resourcefact.code as resource_short_name,
  CONCAT(resourcefact.name, '-', resourcefact.code) as resource_name,
  COALESCE(SUM(agg.ended_job_count), 0) AS Jobs_job_count
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days duration,
  modw.resourcefact resourcefact
WHERE
  duration.id = agg.day_id
  AND agg.day_id between 201600357 and 201700001
  AND resourcefact.id = agg.resource_id
  AND resourcefact.code = agg.resource_code
GROUP BY resourcefact.id,
  resourcefact.code
ORDER BY resourcefact.code ASC,
  resourcefact.name ASC
SQL;
        $this->assertEquals($expected, $generated, 'Aggregate query add multi-column key group by and statistic');
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
        $query->addStat('Jobs_job_count');

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
        $query->addStat('Jobs_job_count');

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

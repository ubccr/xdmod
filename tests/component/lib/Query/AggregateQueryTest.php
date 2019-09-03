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
  person.id as 'id',
  person.short_name as short_name,
  person.long_name as name
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days d,
  modw.person person
WHERE
  d.id = agg.day_id
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
  person.id as 'id',
  person.short_name as short_name,
  person.long_name as name,
  COALESCE(SUM(agg.ended_job_count), 0) AS Jobs_job_count,
  SUM(agg.running_job_count) AS Jobs_running_job_count
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days d,
  modw.person person
WHERE
  d.id = agg.day_id
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
  modw.days d
WHERE
  d.id = agg.day_id
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
  person.id as 'id',
  person.short_name as short_name,
  person.long_name as name,
  COALESCE(SUM(agg.ended_job_count), 0) AS Jobs_job_count
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days d,
  modw.person person
WHERE
  d.id = agg.day_id
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

        $generated = $query->getQueryString();
        $expected  =<<<SQL
SELECT
  person.id as 'id',
  person.short_name as short_name,
  person.long_name as name,
  COALESCE(SUM(agg.ended_job_count), 0) AS Jobs_job_count
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days d,
  modw.person person
WHERE
  d.id = agg.day_id
  AND agg.day_id between 201600357 and 201700001
  AND person.id = agg.person_id
GROUP BY person.id
ORDER BY person.order_id ASC
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
    modw.days d,
    modw.person person
  WHERE
    d.id = agg.day_id
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
  resourcefact.id as 'id',
  resourcefact.code as 'code',
  resourcefact.code as short_name,
  CONCAT(resourcefact.name, '-', resourcefact.code) as name,
  COALESCE(SUM(agg.ended_job_count), 0) AS Jobs_job_count
FROM
  modw_aggregates.jobfact_by_day agg,
  modw.days d,
  modw.resourcefact resourcefact
WHERE
  d.id = agg.day_id
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
    modw.days d,
    modw.person person
  WHERE
    d.id = agg.day_id
    AND agg.day_id between 201600357 and 201700001
    AND person.id = agg.person_id
  GROUP BY
    person.id
) AS a WHERE a.total IS NOT NULL
SQL;
        $this->assertEquals($expected, $generated, 'Aggregate query count');
    }

    /**
     * Test the dimension values query.
     */

    public function testAggregateQueryDimensionValues()
    {
        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
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

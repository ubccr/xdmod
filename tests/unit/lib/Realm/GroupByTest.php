<?php
/**
 * Unit tests for GroupBy class.
 *
 * Note that methods involving database queries will be tested using component tests. These include:
 * - generateQueryParameterLabelsFromRequest()
 * - getAttributeValues()
 */

namespace UnitTesting\Realm;

use CCR\Log as Logger;
use \Realm\Realm;

class GroupByTest extends \PHPUnit_Framework_TestCase
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

        $options = (object) array(
            'config_file_name' => 'datawarehouse.json',
            'config_base_dir'  => realpath('../artifacts/xdmod/realm')
        );

        \Realm\Realm::initialize(self::$logger, $options);
    }

    /**
     * (1) Invalid realm name.
     * 
     * @expectedException Exception
     */

    public function testInvalidGroupBy()
    {
        $realm = Realm::factory('Jobs', self::$logger);
        $realm->getGroupByObject('DoesNotExist');
    }

    /**
     * (2) Test checking to see if a group by exists.
     */

    public function testGroupByExists()
    {
        $realm = Realm::factory('Jobs', self::$logger);

        $generated = $realm->groupByExists('resource');
        $this->assertTrue($generated, "groupByExists('resource')");

        $generated = $realm->groupByExists('does_not_exist');
        $this->assertFalse($generated, "groupByExists('does_not_exist')");
    }

    /**
     * (3) Test various sorting methods on the group by objects.
     */

    public function testGetGroupByObjectList()
    {
        $realm = Realm::factory('Jobs', self::$logger);
        $objectList = $realm->getGroupByObjects();
        $generated = array();
        foreach ( $objectList as $id => $obj ) {
            $generated[$id] = $obj->getName();
        }
        $expected = array(
            'resource' => 'Resource',
            'person' => 'User'
        );
        $this->assertEquals($generated, $expected, "getGroupByObjects('Jobs')");

        $realm = Realm::factory('Cloud', self::$logger);
        $objectList = $realm->getGroupByObjects(Realm::SORT_ON_NAME);
        $generated = array();
        foreach ( $objectList as $id => $obj ) {
            $generated[$id] = $obj->getName();
        }
        $expected = array(
            'configuration' => 'Instance Type',
            'quarter' => 'Quarter',
            'username' => 'System Username'
        );
        $this->assertEquals($generated, $expected, "getGroupByObjects('Cloud'), SORT_ON_NAME");
    }

    /**
     * (4) Test retrieval of the group by object.
     */

    public function testGetGroupByObject()
    {
        $realm = Realm::factory('Cloud', self::$logger);
        $obj = $realm->getGroupByObject('username');

        $this->assertEquals($obj->getName(), 'System Username', 'getName()');
    }

    /**
     * (5) Test generating query filters from a web request.
     */

    public function testGenerateQueryFiltersFromRequest()
    {
        $realm = Realm::factory('Jobs', self::$logger);

        // GroupBy with a single columm key

        $obj = $realm->getGroupByObject('person');
        $simulatedRequest = array(
            'person' => '10',
            'person_filter' => '20,30'
        );
        $parameters = $obj->generateQueryFiltersFromRequest($simulatedRequest);

        $generated = array_shift($parameters);
        $expected = "person_id IN ('20','30','10')";
        $this->assertEquals($generated, $expected, 'generateQueryFiltersFromRequest()');

        // GroupBy with a multi-column key (2 columns in attribute table and 2 in aggregate table).
        // Multi-column keys use a carat (^) to separate the keys in filters.

        $obj = $realm->getGroupByObject('resource');
        $simulatedRequest = array(
            'resource' => '10^15',
            'resource_filter' => '20^25,30^35'
        );
        $parameters = $obj->generateQueryFiltersFromRequest($simulatedRequest);

        $generated = array_shift($parameters);
        $expected = "resource_id IN ('20','30','10')";
        $this->assertEquals($generated, $expected, 'generateQueryFiltersFromRequest()');

        $generated = array_shift($parameters);
        $expected = "resource_code IN ('25','35','15')";
        $this->assertEquals($generated, $expected, 'generateQueryFiltersFromRequest()');
    }

    /**
     * (6) Test the applyTo() method for applying a group by to a query. Note that this is done via
     * Query and not directly on GroupBy as query must be passed to addWhereJoin(). Note that the
     * Query classes themselves are tested elsewhere.
     */

    public function testApplyTo()
    {
        $expected =<<<SQL
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

        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31',
            'person'
        );
        $generated = $query->getQueryString();
        $this->assertEquals($generated, $expected, 'AggregateQuery applyTo()');

        $query = new \DataWarehouse\Query\AggregateQuery(
            'Jobs',
            'day',
            '2016-12-01',
            '2017-01-31'
        );
        $query->addGroupBy('person');
        $generated = $query->getQueryString();
        $this->assertEquals($generated, $expected, 'AggregateQuery::addGroupBy()');
    }

    /**
     * (7) Test the addWhereJoin() method. Note that this is done via Query and not directly on
     * GroupBy as query must be passed to addWhereJoin(). Note that the Query classes themselves are
     * tested elsewhere.
     */

    public function testAddWhereJoin()
    {
        $expected =<<<SQL
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
  AND person.id > (constraint)
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
        $this->assertEquals($generated, $expected, 'AggregateQuery::addWhereJoin()');
    }

    /**
     * (8) Test group by metadata.
     */

    public function testRealmMetadata()
    {
        $realm = Realm::factory('Cloud', self::$logger);
        $obj = $realm->getGroupByObject('username');

        $generated = $obj->getRealm()->getId();
        $expected = 'Cloud';
        $this->assertEquals($generated, $expected, 'getRealm()->getId()');

        $generated = $obj->getId();
        $expected = 'username';
        $this->assertEquals($generated, $expected, 'getId()');

        $generated = $obj->getName();
        $expected = 'System Username';
        $this->assertEquals($generated, $expected, 'getName()');

        $generated = $obj->getHtmlDescription();
        $expected = 'The specific system username associated with a running session of a virtual machine.';
        $this->assertEquals($generated, $expected, 'getHtmlDescription()');

        $generated = $obj->getHtmlNameAndDescription();
        $expected = '<b>System Username</b>: The specific system username associated with a running session of a virtual machine.';
        $this->assertEquals($generated, $expected, 'getHtmlNameAndDescription()');

        $generated = $obj->getAttributeTable();
        $expected = 'modw.systemaccount';
        $this->assertEquals($generated, $expected, 'getAttributeTable()');

        $generated = $obj->getAttributeTable(false);
        $expected = 'systemaccount';
        $this->assertEquals($generated, $expected, 'getAttributeTable(false)');

        $generated = $obj->getAttributeKeys();
        $expected = array(
            'id'
        );
        $this->assertEquals($generated, $expected, 'getAttributeKeys()');
        $generated = $obj->getAggregateKeys();
        $expected = array(
            'systemaccount_id'
        );
        $this->assertEquals($generated, $expected, 'getAggregateKeys()');

        $generated = $obj->getModuleName();
        $expected = 'cloud';
        $this->assertEquals($generated, $expected, 'getModuleName()');

        $generated = $obj->getOrder();
        $expected = 0;
        $this->assertEquals($generated, $expected, 'getOrder()');

        $generated = $obj->getSortOrder();
        $expected = SORT_DESC;
        $this->assertEquals($generated, $expected, 'getSortOrder()');

        $generated = $obj->isAvailableForDrilldown();
        $this->assertTrue($generated, 'isAvailableForDrilldown()');

        $generated = $obj->getAttributeValuesQuery()->getSql();
        $expected =<<<SQL
SELECT
username AS `id`,
username AS `short_name`,
username AS `name`,
username AS `order_id`
FROM `systemaccount`
ORDER BY username
SQL;
        $this->assertEquals($generated, $expected, 'getAttributeValuesQuery()->getSql()');

        $generated = $obj->getDefaultDatasetType();
        $this->assertEquals($generated, 'aggregate', 'getDefaultDatasetType()');

        $generated = $obj->getDefaultDisplayType();
        $this->assertEquals($generated, 'line', 'getDefaultDisplayType()');

        $generated = $obj->getDefaultDisplayType('aggregate');
        $this->assertEquals($generated, 'h_bar', 'getDefaultDisplayType()');

        $generated = $obj->getDefaultCombineMethod();
        $this->assertEquals($generated, 'stack', 'getDefaultCombineMethod()');

        $generated = $obj->getDefaultShowLegend();
        $this->assertEquals($generated, 'y', 'getDefaultShowLegend()');

        $generated = $obj->getDefaultLimit();
        $this->assertEquals($generated, 10, 'getDefaultLimit()');

        $generated = $obj->getDefaultLimit(true);
        $this->assertEquals($generated, 3, 'getDefaultLimit()');

        $generated = $obj->getDefaultOffset();
        $this->assertEquals($generated, 0, 'getDefaultOffset()');

        $generated = $obj->getDefaultLogScale();
        $this->assertEquals($generated, 'n', 'getDefaultLogScale()');

        $generated = $obj->getDefaultShowTrendLine();
        $this->assertEquals($generated, 'n', 'getDefaultShowTrendLine()');

        $generated = $obj->getDefaultShowErrorBars();
        $this->assertEquals($generated, 'n', 'getDefaultShowErrorBars()');

        $generated = $obj->getDefaultShowGuideLines();
        $this->assertEquals($generated, 'y', 'getDefaultShowGuideLines()');

        $generated = $obj->getDefaultShowAggregateLabels();
        $this->assertEquals($generated, 'n', 'getDefaultShowAggregateLabels()');

        $generated = $obj->getDefaultShowErrorLabels();
        $this->assertEquals($generated, 'n', 'getDefaultShowErrorLabels()');

        $generated = $obj->getDefaultEnableErrors();
        $this->assertEquals($generated, 'y', 'getDefaultEnableErrors()');

        $generated = $obj->getDefaultEnableTrendLine();
        $this->assertEquals($generated, 'y', 'getDefaultEnableTrendLine()');

        $generated = $obj->getCategory();
        $this->assertEquals($generated, 'uncategorized', 'getCategory()');
    }
}

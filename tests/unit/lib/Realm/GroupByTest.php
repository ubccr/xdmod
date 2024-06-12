<?php
/**
 * Unit tests for GroupBy class.
 *
 * Note that methods involving database queries will be tested using component tests. These include:
 * - generateQueryParameterLabelsFromRequest()
 * - getAttributeValues()
 */

namespace UnitTests\Realm;

use CCR\Log as Logger;
use Exception;
use Psr\Log\LoggerInterface;
use \Realm\Realm;

class GroupByTest extends \PHPUnit\Framework\TestCase
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
     * (1) Invalid realm name.
     *
     *
     */

    public function testInvalidGroupBy()
    {
        $this->expectException(Exception::class);
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
            'person' => 'User',
            'none' => 'None',
            'day' => 'Day',
            'month' => 'Month',
            'username' => 'System Username',
            'queue' => 'Queue'
        );
        $this->assertEquals($expected, $generated, "getGroupByObjects('Jobs')");

        $realm = Realm::factory('Cloud', self::$logger);
        $objectList = $realm->getGroupByObjects(Realm::SORT_ON_NAME);
        $generated = array();
        foreach ( $objectList as $id => $obj ) {
            $generated[$id] = $obj->getName();
        }
        $expected = array(
            'alternate_groupby_class' => 'Alternate GroupBy Class Example',
            'configuration' => 'Instance Type',
            'username' => 'System Username',
            'day' => 'Day',
            'month' => 'Month',
            'none' => 'None'
        );
        $this->assertEquals($expected, $generated, "getGroupByObjects('Cloud'), SORT_ON_NAME");
    }

    /**
     * (4) Test retrieval of the group by object.
     */

    public function testGetGroupByObject()
    {
        $realm = Realm::factory('Cloud', self::$logger);
        $obj = $realm->getGroupByObject('username');

        $this->assertEquals('System Username', $obj->getName(), 'getName()');
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
        $this->assertEquals($expected, $generated, 'generateQueryFiltersFromRequest()');

        // GroupBy with a multi-column key (2 columns in attribute table and 2 in aggregate table).
        // Multi-column keys use a carat (^) to separate the keys in filters.

        $obj = $realm->getGroupByObject('resource');
        $simulatedRequest = array(
            'resource' => '10^15',
            'resource_filter' => '20^25,30^35'
        );
        $parameters = $obj->generateQueryFiltersFromRequest($simulatedRequest);

        $generated = array_shift($parameters);
        $expected = "record_resource_id IN ('20','30','10')";
        $this->assertEquals($expected, $generated, 'generateQueryFiltersFromRequest()');

        $generated = array_shift($parameters);
        $expected = "resource_code IN ('25','35','15')";
        $this->assertEquals($expected, $generated, 'generateQueryFiltersFromRequest()');
    }

    /**
     * (6) Test group by metadata.
     */

    public function testGroupByMetadata()
    {
        $realm = Realm::factory('Cloud', self::$logger);
        $obj = $realm->getGroupByObject('username');

        $generated = $obj->getRealm()->getId();
        $expected = 'Cloud';
        $this->assertEquals($expected, $generated, 'getRealm()->getId()');

        $generated = $obj->getId();
        $expected = 'username';
        $this->assertEquals($expected, $generated, 'getId()');

        $generated = $obj->getName();
        $expected = 'System Username';
        $this->assertEquals($expected, $generated, 'getName()');

        $generated = $obj->getHtmlDescription();
        $expected = 'The specific system username associated with a running session of a virtual machine.';
        $this->assertEquals($expected, $generated, 'getHtmlDescription()');

        $generated = $obj->getHtmlNameAndDescription();
        $expected = '<b>System Username</b>: The specific system username associated with a running session of a virtual machine.';
        $this->assertEquals($expected, $generated, 'getHtmlNameAndDescription()');

        $generated = $obj->getAttributeTable();
        $expected = 'modw.systemaccount';
        $this->assertEquals($expected, $generated, 'getAttributeTable()');

        $generated = $obj->getAttributeTable(false);
        $expected = 'systemaccount';
        $this->assertEquals($expected, $generated, 'getAttributeTable(false)');

        $generated = $obj->getAttributeKeys();
        $expected = array(
            'id'
        );
        $this->assertEquals($expected, $generated, 'getAttributeKeys()');
        $generated = $obj->getAggregateKeys();
        $expected = array(
            'systemaccount_id'
        );
        $this->assertEquals($expected, $generated, 'getAggregateKeys()');

        $generated = $obj->getModuleName();
        $expected = 'cloud';
        $this->assertEquals($expected, $generated, 'getModuleName()');

        $generated = $obj->getOrder();
        $expected = 5;
        $this->assertEquals($expected, $generated, 'getOrder()');

        $generated = $obj->getSortOrder();
        $expected = SORT_DESC;
        $this->assertEquals($expected, $generated, 'getSortOrder()');

        $generated = $obj->showInMetricCatalog();
        $this->assertTrue($generated, 'showInMetricCatalog()');

        $generated = $obj->getAttributeValuesQuery()->getSql();
        $expected =<<<SQL
SELECT
username AS `id`,
username AS `short_name`,
username AS `name`,
username AS `order_id`
FROM `modw`.`systemaccount`
ORDER BY username
SQL;
        $this->assertEquals($expected, $generated, 'getAttributeValuesQuery()->getSql()');

        $generated = $obj->getDefaultDatasetType();
        $this->assertEquals('aggregate', $generated, 'getDefaultDatasetType()');

        $generated = $obj->getDefaultDisplayType();
        $this->assertEquals('line', $generated, 'getDefaultDisplayType()');

        $generated = $obj->getDefaultDisplayType('aggregate');
        $this->assertEquals('h_bar', $generated, 'getDefaultDisplayType()');

        $generated = $obj->getDefaultCombineMethod();
        $this->assertEquals('stack', $generated, 'getDefaultCombineMethod()');

        $generated = $obj->getDefaultShowLegend();
        $this->assertEquals('y', $generated, 'getDefaultShowLegend()');

        $generated = $obj->getDefaultLimit();
        $this->assertEquals(10, $generated, 'getDefaultLimit()');

        $generated = $obj->getDefaultLimit(true);
        $this->assertEquals(3, $generated, 'getDefaultLimit()');

        $generated = $obj->getDefaultOffset();
        $this->assertEquals(0, $generated, 'getDefaultOffset()');

        $generated = $obj->getDefaultLogScale();
        $this->assertEquals('n', $generated, 'getDefaultLogScale()');

        $generated = $obj->getDefaultShowTrendLine();
        $this->assertEquals('n', $generated, 'getDefaultShowTrendLine()');

        $generated = $obj->getDefaultShowErrorBars();
        $this->assertEquals('n', $generated, 'getDefaultShowErrorBars()');

        $generated = $obj->getDefaultShowGuideLines();
        $this->assertEquals('y', $generated, 'getDefaultShowGuideLines()');

        $generated = $obj->getDefaultShowAggregateLabels();
        $this->assertEquals('n', $generated, 'getDefaultShowAggregateLabels()');

        $generated = $obj->getDefaultShowErrorLabels();
        $this->assertEquals('n', $generated, 'getDefaultShowErrorLabels()');

        $generated = $obj->getDefaultEnableErrors();
        $this->assertEquals('y', $generated, 'getDefaultEnableErrors()');

        $generated = $obj->getDefaultEnableTrendLine();
        $this->assertEquals('y', $generated, 'getDefaultEnableTrendLine()');

        $generated = $obj->getCategory();
        $this->assertEquals('uncategorized', $generated, 'getCategory()');
    }

    /**
     * (9) Test using an alternate GroupBy class specified in the configuration. At the moment this
     * simply tests that the infrastructure attempts to instantiate the specified class.
     */

    public function testAlternateGroupByClass()
    {
        $realm = Realm::factory('Cloud', self::$logger);
        try {
            $realm->getGroupByObject('alternate_groupby_class');
            $this->assertTrue(false, 'Alternate GroupBy class returned object');
        } catch ( \Exception $e ) {
            $message = $e->getMessage();
            $expected = '\Realm\GroupBy\AlternateGroupBy.php';
            $length = strlen($expected);
            $generated = null;
            $position = strpos($message, $expected);
            if ( false !== $position ) {
                $generated = substr($message, $position, $length);
            }
            $this->assertEquals($expected, $generated, sprintf('Alternate GroupBy class does not match: %s', $message));
        }
    }

    /**
     * (10) Test setting custom chart display types for datasets.
     */

    public function testCustomChartTypes()
    {
        $realm = Realm::factory('Cloud', self::$logger);
        $obj = $realm->getGroupByObject('configuration');

        $generated = $obj->getDefaultDisplayType();
        $this->assertEquals('area', $generated, 'getDefaultDisplayType()');

        $generated = $obj->getDefaultDisplayType('timeseries');
        $this->assertEquals('area', $generated, 'getDefaultDisplayType(timeseries)');

        $generated = $obj->getDefaultDisplayType('aggregate');
        $this->assertEquals('bar', $generated, 'getDefaultDisplayType(aggregate)');
    }

    /**
     * (11) Test an unknown dataset type when querying the default chart display type for that
     *      dataset.
     *
     *
     */

    public function testUnknownDatasetType()
    {
        $this->expectException(Exception::class);
        $realm = Realm::factory('Cloud', self::$logger);
        $obj = $realm->getGroupByObject('configuration');

        $obj->getDefaultDisplayType('unknown_dataset_type');
    }

    /**
     * (12) Test custom group by chart options by testing one of each type of value.
     */

    public function testCustomChartOptions()
    {
        $realm = Realm::factory('Cloud', self::$logger);
        $obj = $realm->getGroupByObject('configuration');

        // String
        $generated = $obj->getDefaultDatasetType();
        $this->assertEquals('custom_dataset_type', $generated, 'getDefaultDatasetType()');

        // Object
        $generated = $obj->getDefaultDisplayType('aggregate');
        $this->assertEquals('bar', $generated, 'getDefaultDisplayType()');

        // Integer
        $generated = $obj->getDefaultOffset();
        $this->assertEquals(5, $generated, 'getDefaultCombineMethod()');

        // Boolean
        $generated = $obj->getDefaultShowLegend();
        $this->assertEquals('n', $generated, 'getDefaultShowLegend()');
    }
}

<?php
/**
 * Unit tests for Realm class.
 */

namespace UnitTesting\Realm;

use CCR\Log as Logger;
use \Realm\Realm;

class RealmTest extends \PHPUnit_Framework_TestCase
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
     * (1) Invalid realm name.
     *
     * @expectedException Exception
     */

    public function testInvalidRealmName()
    {
        Realm::factory('RealmDoesNotExist', self::$logger);
    }

    /**
     * (2) Test various sorting methods on the realm names. Note tht the "Disabled" realm will not be
     *     included.
     */

    public function testGetRealmNames()
    {
        $generated = Realm::getRealmNames(Realm::SORT_ON_ORDER);
        $expected = array(
            'Jobs' => 'Jobs',
            'Cloud' => 'Cloud'
        );
        $this->assertEquals($expected, $generated, "Sort realm names on order");

        $generated = Realm::getRealmNames(Realm::SORT_ON_SHORT_ID);
        $expected = array(
            'Cloud' => 'Cloud',
            'Jobs' => 'Jobs'
        );
        $this->assertEquals($expected, $generated, "Sort realm names on short id");

        $generated = Realm::getRealmNames(Realm::SORT_ON_NAME);
        $expected = array(
            'Cloud' => 'Cloud',
            'Jobs' => 'Jobs'
        );
        $this->assertEquals($expected, $generated, "Sort realm names on name");
    }

    /**
     * (3) Test retrieval of the group by names.
     */

    public function testGetGroupByNames()
    {
        $realm = Realm::factory('Jobs', self::$logger);

        $generated = $realm->getGroupByNames(); // Default sort order is SORT_ON_ORDER
        $expected = array(
            'none' => 'None',
            'day' => 'Day',
            'resource' => 'Resource',
            'person' => 'User',
            'month' => 'Month'
        );
        $this->assertEquals($expected, $generated, "getGroupByNames(SORT_ON_ORDER)");

        $generated = $realm->getGroupByNames(Realm::SORT_ON_SHORT_ID);
        $expected = array(
            'day' => 'Day',
            'none' => 'None',
            'person' => 'User',
            'resource' => 'Resource',
            'month' => 'Month'
        );
        $this->assertEquals($expected, $generated, "getGroupByNames(SORT_ON_SHORT_ID)");

        $generated = $realm->getGroupByNames(Realm::SORT_ON_NAME);
        $expected = array(
            'day' => 'Day',
            'month' => 'Month',
            'none' => 'None',
            'resource' => 'Resource',
            'person' => 'User'
        );
        $this->assertEquals($expected, $generated, "getGroupByNames(SORT_ON_NAME)");
    }

    /**
     * (4) Test retrieval of the statistic names.
     */

    public function testGetStatisticNames()
    {
        $realm = Realm::factory('Cloud', self::$logger);

        $generated = $realm->getStatisticNames(); // Default sort order is SORT_ON_ORDER
        $expected = array(
            'Cloud_num_sessions_running' => '${ORGANIZATION_NAME} Number of Active Sessions',
            'Cloud_core_time' => 'Core Hours: Total',
            'Cloud_alternate_statistic_class' => 'Alternate Statistic Class Example'
        );
        $this->assertEquals($expected, $generated, "getStatisticNames(SORT_ON_ORDER)");

        $generated = $realm->getStatisticNames(Realm::SORT_ON_SHORT_ID);
        $expected = array(
            'Cloud_alternate_statistic_class' => 'Alternate Statistic Class Example',
            'Cloud_core_time' => 'Core Hours: Total',
            'Cloud_num_sessions_running' => '${ORGANIZATION_NAME} Number of Active Sessions'
        );
        $this->assertEquals($expected, $generated, "getStatisticNames(SORT_ON_SHORT_ID)");

        $generated = $realm->getStatisticNames(Realm::SORT_ON_NAME);
        $expected = array(
            'Cloud_num_sessions_running' => '${ORGANIZATION_NAME} Number of Active Sessions',
            'Cloud_alternate_statistic_class' => 'Alternate Statistic Class Example',
            'Cloud_core_time' => 'Core Hours: Total'
        );
        $this->assertEquals($expected, $generated, "getStatisticNames(SORT_ON_NAME)");
    }

    /**
     * (5) Test getDrillTargets()
     */

    public function testGetDrillTargets()
    {
        $realm = Realm::factory('Jobs', self::$logger);
        $generated = $realm->getDrillTargets('person');
        $expected = array(
            'none-None',
            'day-Day',
            'month-Month',
            'resource-Resource'
        );
        $this->assertEquals($expected, $generated, "getDrillTargets('person')");

        $realm = Realm::factory('Cloud', self::$logger);
        $generated = $realm->getDrillTargets('none');  // Will be returned using SORT_ON_ORDER
        $expected = array(
            'day-Day',
            'month-Month',
            'alternate_groupby_class-Alternate GroupBy Class Example',
            'configuration-Instance Type',
            'username-System Username'
        );
        $this->assertEquals($expected, $generated, "getDrillTargets('none')");
    }

    /**
     * (6) Test realm metadata.
     */

    public function testRealmMetadata()
    {
        $realm = Realm::factory('Jobs', self::$logger);

        $generated = $realm->getId();
        $expected = 'Jobs';
        $this->assertEquals($expected, $generated, "getId()");

        $generated = $realm->getName();
        $expected = 'Jobs';
        $this->assertEquals($expected, $generated, "getName()");

        $generated = $realm->getAggregateTableSchema();
        $expected = 'modw_aggregates';
        $this->assertEquals($expected, $generated, "getAggregateTableSchema()");

        $generated = $realm->getAggregateTablePrefix();
        $expected = 'modw_aggregates.jobfact_by_';
        $this->assertEquals($expected, $generated, "getAggregateTablePrefix()");

        $generated = $realm->getAggregateTablePrefix(false);
        $expected = 'jobfact_by_';
        $this->assertEquals($expected, $generated, "getAggregateTablePrefix(false)");

        $generated = $realm->getAggregateTableAlias();
        $expected = 'agg';
        $this->assertEquals($expected, $generated, "getAggregateTableAlias()");

        $generated = $realm->getDatasource();
        $expected = 'Slurm';
        $this->assertEquals($expected, $generated, "getDatasource()");

        $generated = $realm->getModuleName();
        $expected = 'xdmod';
        $this->assertEquals($expected, $generated, "getModuleName()");

        $generated = $realm->getOrder();
        $expected = 1;
        $this->assertEquals($expected, $generated, "getOrder()");

        $generated = $realm->getDefaultWeighgtStatName();
        $expected = 'weight';
        $this->assertEquals($expected, $generated, "getDefaultWeighgtStatName()");

        $generated = $realm->getMinimumAggregationUnit();
        $this->assertNull($generated, "getMinimumAggregationUnit()");
    }
}

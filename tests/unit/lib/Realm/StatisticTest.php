<?php
/**
 * Unit tests for Statistic class.
 */

namespace UnitTesting\Realm;

use CCR\Log as Logger;
use \Realm\Realm;

class StatisticTest extends \PHPUnit_Framework_TestCase
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

    public function testInvalidStatistic()
    {
        $realm = Realm::factory('Jobs', self::$logger);
        $realm->getStatisticObject('DoesNotExist');
    }

    /**
     * (2) Test checking to see if a statistic exists.
     */

    public function testStatisticExists()
    {
        $realm = Realm::factory('Jobs', self::$logger);

        $generated = $realm->statisticExists('Jobs_job_count');
        $this->assertTrue($generated, "statisticExists('Jobs_job_count')");

        $generated = $realm->statisticExists('does_not_exist');
        $this->assertFalse($generated, "statistiExists('does_not_exist')");
    }

    /**
     * (3) Test various sorting methods on the statistic objects.
     */

    public function testGetStatisticObjectList()
    {
        $realm = Realm::factory('Jobs', self::$logger);
        $objectList = $realm->getStatisticObjects();
        $generated = array();
        foreach ( $objectList as $id => $obj ) {
            $generated[$id] = $obj->getName(false);
        }
        $expected = array(
            'Jobs_job_count' => '${ORGANIZATION_NAME} Number of Jobs Ended',
            'Jobs_running_job_count' => '${ORGANIZATION_NAME} Number of Running Jobs'
        );
        $this->assertEquals($generated, $expected, "getStatisticObjects('Jobs')");

        $realm = Realm::factory('Cloud', self::$logger);
        $objectList = $realm->getStatisticObjects(Realm::SORT_ON_SHORT_ID);
        $generated = array();
        foreach ( $objectList as $id => $obj ) {
            $generated[$id] = $obj->getName(false);
        }
        $expected = array(
            'Cloud_alternate_statistic_class' => 'Alternate Statistic Class Example',
            'Cloud_core_time' => 'Core Hours: Total',
            'Cloud_num_sessions_running' => '${ORGANIZATION_NAME} Number of Active Sessions'
        );
        $this->assertEquals($generated, $expected, "getStatisticObjects('Cloud'), SORT_ON_SHORT_ID");
    }

    /**
     * (4) Test retrieval of the statistic object.
     */

    public function testGetStatisticObject()
    {
        $realm = Realm::factory('Cloud', self::$logger);
        $obj = $realm->getStatisticObject('Cloud_num_sessions_running');

        $this->assertEquals($obj->getName(), '${ORGANIZATION_NAME} Number of Active Sessions', 'getName()');
    }

    /**
     * (5) Test retrieval of a disabled statistic.
     *
     * @expectedException Exception
     */

    public function testGetDisabledStatisticObject()
    {
        $realm = Realm::factory('Cloud', self::$logger);
        $obj = $realm->getStatisticObject('disabled_core_time');
    }

    /**
     * (6) Test the getFormula() method where macros in the formula are repalced with values from
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

        $realm = Realm::factory('Cloud', self::$logger);
        $statistic = $realm->getStatisticObject('Cloud_num_sessions_running');
        $generated = $statistic->getFormula($query);
        $expected = 'COALESCE(SUM(CASE days.id WHEN 201800108 THEN agg.num_sessions_running ELSE agg.num_sessions_started END), 0) AS Cloud_num_sessions_running';
        $this->assertEquals($generated, $expected, 'getFormula()');
    }

    /**
     * (7) Test statistic metadata.
     */

    public function testStatisticMetadata()
    {
        $realm = Realm::factory('Jobs', self::$logger);
        $obj = $realm->getStatisticObject('Jobs_job_count');

        $generated = $obj->getRealm()->getId();
        $expected = 'Jobs';
        $this->assertEquals($generated, $expected, 'getRealm()->getId()');

        $generated = $obj->getId();
        $expected = 'Jobs_job_count';
        $this->assertEquals($generated, $expected, "getId()");

        $generated = $obj->getId(false);
        $expected = 'Jobs_job_count';
        $this->assertEquals($generated, $expected, "getId(false)");

        $generated = $obj->getName();
        $expected = '${ORGANIZATION_NAME} Number of Jobs Ended (Number of Jobs)';
        $this->assertEquals($generated, $expected, "getName()");

        $generated = $obj->getName(false);
        $expected = '${ORGANIZATION_NAME} Number of Jobs Ended';
        $this->assertEquals($generated, $expected, "getName(false)");

        $generated = $obj->getHtmlDescription();
        $expected = 'The total number of ${ORGANIZATION_NAME} jobs that ended.';
        $this->assertEquals($generated, $expected, "getHtmlDescription()");

        $generated = $obj->getHtmlNameAndDescription();
        $expected = '<b>${ORGANIZATION_NAME} Number of Jobs Ended</b>: The total number of ${ORGANIZATION_NAME} jobs that ended.';
        $this->assertEquals($generated, $expected, "getHtmlNameAndDescription()");

        $generated = $obj->getUnit();
        $expected = 'Number of Jobs';
        $this->assertEquals($generated, $expected, "getUnit()");

        $generated = $obj->getPrecision();
        $expected = 2;
        $this->assertEquals($generated, $expected, "getPrecision()");

        $generated = $obj->getSortOrder();
        $expected = SORT_DESC;
        $this->assertEquals($generated, $expected, "getSortOrder()");

        $generated = $obj->getModuleName();
        $expected = 'xdmod';
        $this->assertEquals($generated, $expected, "getModuleName()");

        $generated = $obj->getOrder();
        $expected = 1;
        $this->assertEquals($generated, $expected, "getOrder()");

        $generated = $obj->getAdditionalWhereCondition();
        $this->assertNull($generated, "getAdditionalWhereCondition()");

        $generated = $obj->getWeightStatName();
        $expected = 'weight_is_not_used';
        $this->assertEquals($generated, $expected, "getWeightStatName()");

        $generated = $obj->usesTimePeriodTablesForAggregate();
        $this->assertTrue($generated, "usesTimePeriodTablesForAggregate()");
    }

    /**
     * (8) Test convienece of adding realm to statistic id.
     */

    public function testAddRealmToStatisticId()
    {
        $realm = Realm::factory('Jobs', self::$logger);

        $generated = $realm->statisticExists('job_count');
        $this->assertTrue($generated, "statisticExists(job_count)");

        $obj = $realm->getStatisticObject('job_count');
    }

    /**
     * (9) Test using an alternate Statistic class specified in the configuration. At the moment this
     * simply tests that the infrastructure attempts to instantiate the specified class.
     */

    public function testAlternateStatisticClass()
    {
        $realm = Realm::factory('Cloud', self::$logger);
        try {
            $obj = $realm->getStatisticObject('alternate_statistic_class');
            $this->assertTrue(false, 'Alternate Statistic class returned object');
        } catch ( \Exception $e ) {
            $message = $e->getMessage();
            $expected = '\Realm\Statistic\AlternateStatistic.php';
            $length = strlen($expected);
            $generated = null;
            $position = strpos($message, $expected);
            if ( false !== $position ) {
                $generated = substr($message, $position, $length);
            }
            $this->assertEquals($expected, $generated, sprintf('Alternate Statistic class does not match: %s', $message));
        }
    }
}

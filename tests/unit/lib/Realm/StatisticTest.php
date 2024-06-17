<?php
/**
 * Unit tests for Statistic class.
 */

namespace UnitTests\Realm;

use CCR\Log as Logger;
use Exception;
use Realm\Realm;

class StatisticTest extends \PHPUnit\Framework\TestCase
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
     * (1) Invalid realm name.
     *
     *
     */

    public function testInvalidStatistic()
    {
        $this->expectException(Exception::class);
        $realm = Realm::factory('Jobs', self::$logger);
        $realm->getStatisticObject('DoesNotExist');
    }

    /**
     * (2) Test checking to see if a statistic exists.
     */

    public function testStatisticExists()
    {
        $realm = Realm::factory('Jobs', self::$logger);

        $generated = $realm->statisticExists('job_count');
        $this->assertTrue($generated, "statisticExists('job_count')");

        // Test convenience function adding realm id to statistic id

        $generated = $realm->statisticExists('job_count');
        $this->assertTrue($generated, "statisticExists('job_count')");

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
            'job_count' => sprintf('%s Number of Jobs Ended', ORGANIZATION_NAME),
            'running_job_count' => sprintf('%s Number of Running Jobs', ORGANIZATION_NAME)
        );
        $this->assertEquals($expected, $generated, "getStatisticObjects('Jobs')");

        $realm = Realm::factory('Cloud', self::$logger);
        $objectList = $realm->getStatisticObjects(Realm::SORT_ON_SHORT_ID);
        $generated = array();
        foreach ( $objectList as $id => $obj ) {
            $generated[$id] = $obj->getName(false);
        }
        $expected = array(
            'alternate_statistic_class' => 'Alternate Statistic Class Example',
            'core_time' => 'CPU Hours: Total',
            'cloud_num_sessions_running' => sprintf('%s Number of Active Sessions', ORGANIZATION_NAME)
        );
        $this->assertEquals($expected, $generated, "getStatisticObjects('Cloud'), SORT_ON_SHORT_ID");
    }

    /**
     * (4) Test retrieval of the statistic object.
     */

    public function testGetStatisticObject()
    {
        $realm = Realm::factory('Cloud', self::$logger);
        $obj = $realm->getStatisticObject('cloud_num_sessions_running');

        $this->assertEquals(
            sprintf('%s Number of Active Sessions (Number of Sessions)', ORGANIZATION_NAME),
            $obj->getName(),
            'getStatisticObject(cloud_num_sessions_running)'
        );

        // Test convenience function adding realm id to statistic id

        $obj = $realm->getStatisticObject('cloud_num_sessions_running');

        $this->assertEquals(
            sprintf('%s Number of Active Sessions (Number of Sessions)', ORGANIZATION_NAME),
            $obj->getName(),
            'getStatisticObject(cloud_num_sessions_running)'
        );
    }

    /**
     * (5) Test retrieval of a disabled statistic.
     *
     *
     */

    public function testGetDisabledStatisticObject()
    {
        $this->expectException(Exception::class);
        $realm = Realm::factory('Cloud', self::$logger);
        $realm->getStatisticObject('disabled_core_time');
    }

    /**
     * (7) Test statistic metadata.
     */

    public function testStatisticMetadata()
    {
        $realm = Realm::factory('Jobs', self::$logger);
        $obj = $realm->getStatisticObject('job_count');

        $generated = $obj->getRealm()->getId();
        $expected = 'Jobs';
        $this->assertEquals($expected, $generated, 'getRealm()->getId()');

        $generated = $obj->getId();
        $expected = 'job_count';
        $this->assertEquals($expected, $generated, "getId()");

        $generated = $obj->getId(false);
        $expected = 'job_count';
        $this->assertEquals($expected, $generated, "getId(false)");

        // Note that the unit "Number of Jobs" is found in the statistic name so it will not be
        // appended.
        $generated = $obj->getName();
        $expected = sprintf('%s Number of Jobs Ended', ORGANIZATION_NAME);
        $this->assertEquals($expected, $generated, "getName()");

        $generated = $obj->getName(false);
        $expected = sprintf('%s Number of Jobs Ended', ORGANIZATION_NAME);
        $this->assertEquals($expected, $generated, "getName(false)");

        $generated = $obj->getHtmlDescription();
        $expected = sprintf('The total number of %s jobs that ended.', ORGANIZATION_NAME);
        $this->assertEquals($expected, $generated, "getHtmlDescription()");

        $generated = $obj->getHtmlNameAndDescription();
        $expected = sprintf('<b>%s Number of Jobs Ended</b>: The total number of %s jobs that ended.', ORGANIZATION_NAME, ORGANIZATION_NAME);
        $this->assertEquals($expected, $generated, "getHtmlNameAndDescription()");

        $generated = $obj->getUnit();
        $expected = 'Number of Jobs';
        $this->assertEquals($expected, $generated, "getUnit()");

        $generated = $obj->getPrecision();
        $expected = 2;
        $this->assertEquals($expected, $generated, "getPrecision()");

        $generated = $obj->getSortOrder();
        $expected = SORT_DESC;
        $this->assertEquals($expected, $generated, "getSortOrder()");

        $generated = $obj->getModuleName();
        $expected = 'xdmod';
        $this->assertEquals($expected, $generated, "getModuleName()");

        $generated = $obj->getOrder();
        $expected = 2;
        $this->assertEquals($expected, $generated, "getOrder()");

        $generated = $obj->getAdditionalWhereCondition();
        $this->assertNull($generated, "getAdditionalWhereCondition()");

        $generated = $obj->getWeightStatisticId();
        $expected = 'weight_is_not_used';
        $this->assertEquals($expected, $generated, "getWeightStatisticId()");

        $generated = $obj->usesTimePeriodTablesForAggregate();
        $this->assertTrue($generated, "usesTimePeriodTablesForAggregate()");

        $generated = $obj->showInMetricCatalog();
        $this->assertTrue($generated, "showInMetricCatalog() == true");

        $realm = realm::factory('HiddenRealm', self::$logger);
        $obj = $realm->getstatisticobject('hidden_statistic');

        $generated = $obj->showInMetricCatalog();
        $this->assertFalse($generated, "showInMetricCatalog() == false");
    }

    /**
     * (8) Test convienece of adding realm to statistic id.
     */

    public function testAddRealmToStatisticId()
    {
        $realm = Realm::factory('Jobs', self::$logger);

        $generated = $realm->statisticExists('job_count');
        $this->assertTrue($generated, "statisticExists(job_count)");

        $realm->getStatisticObject('job_count');
    }

    /**
     * (9) Test using an alternate Statistic class specified in the configuration. At the moment this
     * simply tests that the infrastructure attempts to instantiate the specified class.
     */

    public function testAlternateStatisticClass()
    {
        $realm = Realm::factory('Cloud', self::$logger);
        try {
            $realm->getStatisticObject('alternate_statistic_class');
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

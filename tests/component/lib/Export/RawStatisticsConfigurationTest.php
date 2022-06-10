<?php

namespace ComponentTests\Export;

use CCR\Json;
use DataWarehouse\Data\RawStatisticsConfiguration;
use Exception;

/**
 * Test data warehouse export raw statistics configuration.
 *
 * @coversDefaultClass \DataWarehouse\Data\RawStatisticsConfiguration
 */
class RawStatisticsConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \DataWarehouse\Data\RawStatisticsConfiguration
     */
    private static $configuration;

    /**
     * @var array Enabled realm names before adding test realms.
     */
    private static $realmNames = [];

    /**
     * @var array Realms used for testing.
     */
    private static $testRealms = [
        [
            'name' => 'foo',
            'enabled' => true
        ],
        [
            'name' => 'bar',
            'enabled' => false
        ]
    ];

    /**
     * @var array Configuration files created before running tests.
     */
    private static $testConfigFiles = [];

    /**
     * Store batch export realms before adding new test configuration files
     * and then add test configuration files.
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        static::$configuration = RawStatisticsConfiguration::factory();

        static::$realmNames = array_map(
            function ($realm) {
                return $realm['name'];
            },
            static::$configuration->getBatchExportRealms()
        );

        $dir = CONFIG_DIR . '/rawstatistics.d';

        foreach (static::$testRealms as $realm) {
            $name = $realm['name'];
            $file = sprintf('%s/99_%s.json', $dir, $name);

            Json::saveFile(
                $file,
                [
                    '+realms' => [
                        [
                            'export_enabled' => $realm['enabled'],
                            'name' => $name,
                            'display' => $name
                        ]
                    ]
                ]
            );

            static::$testConfigFiles[] = $file;
        }

        // Must clear cache after changing configuration.
        apcu_clear_cache();
    }

    /**
     * Remove test configuration files.
     */
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        foreach (static::$testConfigFiles as $file) {
            if (!unlink($file)) {
                throw new Exception("Failed to remove file '$file'");
            }
        }

        // Must clear cache after changing configuration.
        apcu_clear_cache();
    }

    /**
     * Test which realms are enabled.
     *
     * @covers ::getBatchExportRealms
     */
    public function testEnabledRealms()
    {
        $realmNames = array_map(
            function ($realm) {
                return $realm['name'];
            },
            static::$configuration->getBatchExportRealms()
        );

        // Make sure all the realms enabled before configuration changes are
        // still enabled.
        foreach (static::$realmNames as $realmName) {
            $this->assertTrue(in_array($realmName, $realmNames), "$realmName is enabled");
        }

        foreach (static::$testRealms as $realm) {
            $realmName = $realm['name'];
            if ($realm['enabled']) {
                $this->assertTrue(in_array($realmName, $realmNames), "$realmName is enabled");
            } else {
                $this->assertTrue(!in_array($realmName, $realmNames), "$realmName is not enabled");
            }
        }
    }
}

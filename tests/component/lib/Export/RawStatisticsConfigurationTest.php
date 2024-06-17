<?php

namespace ComponentTests\Export;

use CCR\Json;
use DataWarehouse\Data\RawStatisticsConfiguration;
use Exception;
use \PHPUnit\Framework\TestCase;

/**
 * Test data warehouse export raw statistics configuration.
 *
 * @coversDefaultClass \DataWarehouse\Data\RawStatisticsConfiguration
 */
class RawStatisticsConfigurationTest extends \PHPUnit\Framework\TestCase
{
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
     * @var array Enabled realm names before adding test realms.
     */
    private static $realmNames = [];

    /**
     * @var array Configuration files created before running tests.
     */
    private static $testConfigFiles = [];

    /**
     * Store enabled batch export realm names before adding new test
     * configuration files and then add test configuration files.
     */
    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();

        static::$realmNames = array_map(
            function ($realm) {
                return $realm['name'];
            },
            RawStatisticsConfiguration::factory()->getBatchExportRealms()
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
    }

    /**
     * Remove test configuration files.
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();

        foreach (static::$testConfigFiles as $file) {
            // The file may not exist because this function is run twice due to
            // the use of `@runInSeparateProcess`.
            if (file_exists($file) && !unlink($file)) {
                throw new Exception("Failed to remove file '$file'");
            }
        }
    }

    /**
     * Test which realms are enabled.
     *
     * This test must run in a separate process because the
     * `RawStatisticsConfiguration` singleton caches configuration data.  Using
     * a separate process ensures that the configuration files are read again
     * during the test.
     *
     * @runInSeparateProcess
     * @covers ::getBatchExportRealms
     */
    public function testEnabledRealms()
    {
        // Names of all currently enabled realms.
        $realmNames = array_map(
            function ($realm) {
                return $realm['name'];
            },
            RawStatisticsConfiguration::factory()->getBatchExportRealms()
        );

        // Check that all the realms enabled before configuration changes are
        // still enabled.
        foreach (static::$realmNames as $realmName) {
            $this->assertTrue(in_array($realmName, $realmNames), "$realmName is enabled");
        }

        // Collected enabled test realm names for use in later assertions.
        $enabledTestRealmNames = [];

        // Check that test realms are included or excluded according to
        // enabled/disabled configuration.
        foreach (static::$testRealms as $realm) {
            $realmName = $realm['name'];
            if ($realm['enabled']) {
                $enabledTestRealmNames[] = $realmName;
                $this->assertTrue(in_array($realmName, $realmNames), "$realmName is enabled");
            } else {
                $this->assertTrue(!in_array($realmName, $realmNames), "$realmName is not enabled");
            }
        }

        // Check that all currently enabled realms should be enabled.  This
        // will fail if a realm is returned from `getBatchExportRealms` that
        // should not be enabled.
        foreach ($realmNames as $realmName) {
            $isEnabledRealm = in_array($realmName, static::$realmNames)
                || in_array($realmName, $enabledTestRealmNames);
            $this->assertTrue($isEnabledRealm, "$realmName is enabled and was before the test or is a test realm");
        }
    }
}

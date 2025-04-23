<?php
/**
 * Test various aspects of the EtlOverseer class. We use an EtlConfiguration with the DummyIngestor
 * and DummyAggregator to allow us to pipeline infrastructure outside of the actions themselves.
 */

namespace ComponentTests\ETL;

use Exception;
use CCR\DB;
use ETL\EtlOverseer;
use ETL\Configuration\EtlConfiguration;
use ETL\EtlOverseerOptions;
use PHPUnit\Framework\TestCase;

/**
 * Various tests for the EtlOverseer class.
 */

class EtlOverseerTest extends TestCase
{
    private static $etlConfig = null;
    private static $testArtifactInputPath = null;
    private static $overseerOptions = null;

    /**
     * Set up machinery that we will need for these tests.
     *
     * @return Nothing
     */

    public static function setupBeforeClass(): void
    {
        self::$testArtifactInputPath = realpath(BASE_DIR . '/tests/artifacts/xdmod/etlv2/configuration/input/');

        // Use a pipeline with DummyIngestor and/or DummyAggregator so we can test infrastructure
        $configFile = self::$testArtifactInputPath . "/xdmod_etl_config_dummy_actions.json";
        self::$etlConfig = EtlConfiguration::factory(
            $configFile,
            self::$testArtifactInputPath,
            null,
            array('default_module_name' => 'xdmod')
        );

        // Explicitly set the resource code map so we don't need to query the database
        self::$overseerOptions = new EtlOverseerOptions(
            array(
                'default-module-name' => 'xdmod',
                'process-sections' => array('dummy-actions'),
                'resource-code-map' => array(
                    'resource1' => 1,
                    'resource2' => 2
                )
            )
        );

    }

    /**
     * Reset values in shared classes.
     */

    public function setUp(): void
    {
        self::$overseerOptions->setIncludeOnlyResourceCodes(null);
        self::$overseerOptions->setIncludeOnlyResourceCodes(null);
    }


    /**
     * Test various cases of valid include and exclude resource codes
     */

    public function testValidResourceCodes() {

        $this->expectNotToPerformAssertions();

        // Single valid resource codes to include
        try {
            self::$overseerOptions->setIncludeOnlyResourceCodes('resource1');
            $overseer = new EtlOverseer(self::$overseerOptions);
            $overseer->execute(self::$etlConfig);
        } catch ( Exception $e ) {
            $this->fail($e->getMessage());
        }

        // Array of valid resource codes to include

        try {
            self::$overseerOptions->setIncludeOnlyResourceCodes(array('resource1', 'resource2'));
            $overseer = new EtlOverseer(self::$overseerOptions);
            $overseer->execute(self::$etlConfig);
        } catch ( Exception $e ) {
            $this->fail($e->getMessage());
        }

        // Single valid resource code to exclude

        try {
            self::$overseerOptions->setExcludeResourceCodes('resource1');
            $overseer = new EtlOverseer(self::$overseerOptions);
            $overseer->execute(self::$etlConfig);
        } catch ( Exception $e ) {
            $this->fail($e->getMessage());
        }

        // Array of valid resource codes to exclude

        try {
            self::$overseerOptions->setExcludeResourceCodes(array('resource1', 'resource2'));
            $overseer = new EtlOverseer(self::$overseerOptions);
            $overseer->execute(self::$etlConfig);
        } catch ( Exception $e ) {
            $this->fail($e->getMessage());
        }
    }

    /**
     * Test various cases of invali include and exclude resource codes
     */

    public function testInvalidResourceCodes() {

        // Single invalid resource code to include

        $unknownCode = 'unknown101';
        $exceptionThrown = true;
        try {
            self::$overseerOptions->setIncludeOnlyResourceCodes($unknownCode);
            $overseer = new EtlOverseer(self::$overseerOptions);
            $overseer->execute(self::$etlConfig);
            $exceptionThrown = false;
        } catch ( Exception $e ) {
            $this->assertStringContainsString($unknownCode, $e->getMessage(), "Unknown resource code but did not find expected code '$unknownCode'");
        }
        $this->assertTrue($exceptionThrown, "Expected exception to be thrown for unknown resource code '$unknownCode'");


        // Array with one invalid resource code to include

        $unknownCode = 'unknown102';
        $exceptionThrown = true;
        try {
            self::$overseerOptions->setIncludeOnlyResourceCodes(array('resource1',$unknownCode));
            $overseer = new EtlOverseer(self::$overseerOptions);
            $overseer->execute(self::$etlConfig);
            $exceptionThrown = false;
        } catch ( Exception $e ) {
            $this->assertStringContainsString($unknownCode, $e->getMessage(), "Unknown resource code but did not find expected code '$unknownCode'");
        }
        $this->assertTrue($exceptionThrown, "Expected exception to be thrown for unknown resource code '$unknownCode'");

        // Single invalid resource code to exclude

        $unknownCode = 'unknown101';
        $exceptionThrown = true;
        try {
            self::$overseerOptions->setExcludeResourceCodes($unknownCode);
            $overseer = new EtlOverseer(self::$overseerOptions);
            $overseer->execute(self::$etlConfig);
            $exceptionThrown = false;
        } catch ( Exception $e ) {
            $this->assertStringContainsString($unknownCode, $e->getMessage(), "Unknown resource code but did not find expected code '$unknownCode'");
        }
        $this->assertTrue($exceptionThrown, "Expected exception to be thrown for unknown resource code '$unknownCode'");

        // Array with one invalid resource code to exclude

        $unknownCode = 'unknown102';
        $exceptionThrown = true;
        try {
            self::$overseerOptions->setExcludeResourceCodes(array('resource1',$unknownCode));
            $overseer = new EtlOverseer(self::$overseerOptions);
            $overseer->execute(self::$etlConfig);
            $exceptionThrown = false;
        } catch ( Exception $e ) {
            $this->assertStringContainsString($unknownCode, $e->getMessage(), "Unknown resource code but did not find expected code '$unknownCode'");
        }
        $this->assertTrue($exceptionThrown, "Expected exception to be thrown for unknown resource code '$unknownCode'");
    }
}

<?php
/* ------------------------------------------------------------------------------------------
 * Component tests for ETL JSON configuration files
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-21
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTesting\ETL\Configuration;

use ETL\Configuration\EtlConfiguration;

class EtlConfigurationTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/output";
    const TMPDIR = '/tmp/xdmod-etl-configuration-test';

    /**
     * Test parsing of an XDMoD JSON ETL configuration file including local files.
     */

    public function testConfiguration()
    {
        // The test files need to be in the same location that the expected results were
        // generated from or paths stored in the expected result will not match!

        @mkdir(self::TMPDIR . '/etl_8.0.0.d', 0755, true);
        copy(self::TEST_ARTIFACT_INPUT_PATH . '/xdmod_etl_config_8.0.0.json', self::TMPDIR . '/xdmod_etl_config_8.0.0.json');
        copy(self::TEST_ARTIFACT_INPUT_PATH . '/etl_8.0.0.d/maintenance.json', self::TMPDIR . '/etl_8.0.0.d/maintenance.json');
        copy(self::TEST_ARTIFACT_INPUT_PATH . '/etl_8.0.0.d/jobs_cloud.json', self::TMPDIR . '/etl_8.0.0.d/jobs_cloud.json');

        $configObj = new EtlConfiguration(self::TMPDIR . '/xdmod_etl_config_8.0.0.json', self::TMPDIR);
        $configObj->initialize();
        $generated = json_decode($configObj->toJson());
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/xdmod_etl_config_8.0.0.json'));
        file_put_contents("/tmp/xdmod_etl_config_8.0.0.json", $configObj->toJson());

        // Cleanup

        unlink(self::TMPDIR . '/xdmod_etl_config_8.0.0.json');
        unlink(self::TMPDIR . '/etl_8.0.0.d/maintenance.json');
        unlink(self::TMPDIR . '/etl_8.0.0.d/jobs_cloud.json');
        rmdir(self::TMPDIR . '/etl_8.0.0.d');
        rmdir(self::TMPDIR );

        $this->assertEquals($generated, $expected);
    }
}  // class EtlConfigurationTest

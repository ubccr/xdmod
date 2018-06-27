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
use ETL\EtlOverseerOptions;

class EtlConfigurationTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/output";
    const TMPDIR = '/tmp/xdmod-etl-configuration-test';
    private static $defaultModuleName = null;

    public static function setUpBeforeClass()
    {
        // Query the configuration file for the default module name

        try {
            $etlConfigOptions = \xd_utilities\getConfigurationSection("etl");
            if (isset($etlConfigOptions['default_module_name'])) {
                self::$defaultModuleName = $etlConfigOptions['default_module_name'];
            }
        } catch ( Exception $e ) {
            // Simply ignore the exception if there is no [etl] section in the config file
        }
    }

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

        $configObj = new EtlConfiguration(
            self::TMPDIR . '/xdmod_etl_config_8.0.0.json',
            self::TMPDIR,
            null,
            array('default_module_name' => self::$defaultModuleName)
        );
        $configObj->initialize();
        $generated = json_decode($configObj->toJson());
        $file = self::TEST_ARTIFACT_OUTPUT_PATH . '/xdmod_etl_config_8.0.0.json';
        $expected = json_decode(file_get_contents($file));

        // Cleanup

        unlink(self::TMPDIR . '/xdmod_etl_config_8.0.0.json');
        unlink(self::TMPDIR . '/etl_8.0.0.d/maintenance.json');
        unlink(self::TMPDIR . '/etl_8.0.0.d/jobs_cloud.json');
        rmdir(self::TMPDIR . '/etl_8.0.0.d');
        rmdir(self::TMPDIR);

        $this->assertEquals($expected, $generated, $file);
    }

    /**
     * Test application of default values and creating/overriding variables on the command line.
     */

    public function testConfigurationVariables()
    {
        // The test files need to be in the same location that the expected results were
        // generated from or paths stored in the expected result will not match!

        @mkdir(self::TMPDIR . '/etl_8.0.0.d', 0755, true);
        copy(
            self::TEST_ARTIFACT_INPUT_PATH . '/xdmod_etl_config_with_variables_8.0.0.json',
            self::TMPDIR . '/xdmod_etl_config_with_variables_8.0.0.json'
        );
        copy(
            self::TEST_ARTIFACT_INPUT_PATH . '/etl_8.0.0.d/local_config_with_variables.json',
            self::TMPDIR . '/etl_8.0.0.d/local_config_with_variables.json'
        );

        $options = array(
            'config_variables' => array(
                // Override an existing variable's value
                'CLI_OVERRIDE'   => 'CommandLineOverride',
                // Define a variable by substituting this for the value
                'CLI_SUBSTITUTE' => 'VariableInConfig',
                // Define a completely new variable
                'CLI_NEW'        => 'NewCommandLineVariable',
                'CLOUD_COMMON_DIR' => 'cloud_common'
            ),
            'default_module_name' => self::$defaultModuleName
        );
        $configObj = new EtlConfiguration(
            self::TMPDIR . '/xdmod_etl_config_with_variables_8.0.0.json',
            self::TMPDIR,
            null,
            $options
        );
        $configObj->initialize();
        $generated = json_decode($configObj->toJson());
        $file = self::TEST_ARTIFACT_OUTPUT_PATH . '/xdmod_etl_config_with_variables.json';
        $expected = json_decode(file_get_contents($file));

        // Cleanup

        unlink(self::TMPDIR . '/xdmod_etl_config_with_variables_8.0.0.json');
        unlink(self::TMPDIR . '/etl_8.0.0.d/local_config_with_variables.json');
        rmdir(self::TMPDIR . '/etl_8.0.0.d');
        rmdir(self::TMPDIR);

        $this->assertEquals($expected, $generated, $file);
    }
}  // class EtlConfigurationTest

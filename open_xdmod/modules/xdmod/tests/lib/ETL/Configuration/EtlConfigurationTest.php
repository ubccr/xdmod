<?php
/* ------------------------------------------------------------------------------------------
 * Component tests for ETL JSON configuration files
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-21
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTesting\ETL\Configuration;

use Configuration\ModuleConfiguration;
use Configuration\XdmodConfiguration;
use ETL\Configuration\EtlConfiguration;
use CCR\Json;

use TestHarness\TestFiles;

class EtlConfigurationTest extends \UnitTesting\BaseTest
{
    const TEST_ARTIFACT_INPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/output";
    const TMPDIR = '/tmp/xdmod-etl-configuration-test';
    private static $defaultModuleName = null;

    private $testFiles;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->testFiles = new TestFiles(__DIR__ . '/../../../');
    }


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
        $this->filterKeysRecursive(array('key'), $generated);
        $file = self::TEST_ARTIFACT_OUTPUT_PATH . '/xdmod_etl_config_with_variables.json';
        $expected = json_decode(file_get_contents($file));

        // Cleanup

        unlink(self::TMPDIR . '/xdmod_etl_config_with_variables_8.0.0.json');
        unlink(self::TMPDIR . '/etl_8.0.0.d/local_config_with_variables.json');
        rmdir(self::TMPDIR . '/etl_8.0.0.d');
        rmdir(self::TMPDIR);

        $this->assertEquals($expected, $generated, $file);
    }

    /**
     * Tests if `Configuration\Configuration` can produce the same basic output as `Config`. This is
     * part of the Configuration Code Consolidation.
     *
     * NOTE ========================================================================================
     * This Test should be moved to ConfigurationTest.php and the `@depend` removed once
     * https://app.asana.com/0/807629084565719/1101232922862525/f has been addressed.
     * =============================================================================================
     * @depends testConfigurationVariables
     *
     * @dataProvider provideTestXdmodConfiguration
     *
     * @param array $options options that control how the test is to be conducted. Required
     * key / values are:
     *   - section : The section that will be requested from `Config` to generate the expected
     *               output
     *   - expected: The filename to use when generating or retrieving the expected output.
     * @throws \Exception
     */
    public function testXdmodConfiguration(array $options)
    {
        $baseDir = dirname($this->testFiles->getFile('configuration', '.', 'input'));
        $baseFile = $this->testFiles->getFile('configuration', $options['base_file'], 'input');

        $expectedFilePath = $this->testFiles->getFile('configuration', $options['expected']);

        if (isset($options['local_dir'])) {
            $localDir = $this->interpretDirOption($options['local_dir']);
            $localConfigDir = dirname(
                $this->testFiles->getfile(
                    'configuration',
                    implode(
                        DIRECTORY_SEPARATOR,
                        array(
                            '.',
                            $localDir,
                            '.')
                    ),
                    'input'
                )
            );
            $config = new XdmodConfiguration(
                $baseFile,
                $baseDir,
                null,
                array(
                    'local_config_dir' => $localConfigDir
                )
            );
        } else {
            $config = new XdmodConfiguration(
                $baseFile,
                $baseDir
            );
        }

        $config->initialize();

        $actual = sprintf("%s\n", $config->toJson());

        if (!is_file($expectedFilePath)) {
            @file_put_contents($expectedFilePath, $actual);
            echo "\nGenerated Expected Output for: $expectedFilePath\n";
        } else {
            $expected = @file_get_contents($expectedFilePath);

            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * Provide test data for `testXdmodConfigurationEquivalence`
     *
     * @return array|object
     * @throws \Exception
     */
    public function provideTestXdmodConfiguration()
    {
        return JSON::loadFile(
            $this->testFiles->getFile('configuration', 'xdmod_config', 'input')
        );
    }

    /**
     * @depends      testConfigurationVariables
     *
     * @dataProvider provideTestModuleConfiguration
     *
     * @param array $options
     * @throws \Exception
     */
    public function testModuleConfiguration(array $options)
    {
        $baseDir = dirname($this->testFiles->getFile('configuration', '.', 'input'));
        $baseFile = $this->testFiles->getFile('configuration', $options['base_file'], 'input');

        $localDir = $this->interpretDirOption($options['local_dir']);
        $localConfigDir = dirname(
            $this->testFiles->getfile(
                'configuration',
                implode(
                    DIRECTORY_SEPARATOR,
                    array(
                        '.',
                        $localDir,
                        '.')
                ),
                'input'
            )
        );

        $config = new ModuleConfiguration(
            $baseFile,
            $baseDir,
            null,
            array(
                'local_config_dir' => $localConfigDir
            )
        );
        $config->initialize();

        $modules = $options['modules'];
        foreach($modules as $module) {
            $expectedFileName = sprintf("%s-%s", $options['expected'], $module);
            $expectedFilePath = $this->testFiles->getFile('configuration', $expectedFileName);

            $actual = sprintf("%s\n", json_encode($config->filterByModule($module)));

            if (!is_file($expectedFilePath)) {
                @file_put_contents($expectedFilePath, $actual);
                echo "\nGenerated Expected Output for: $expectedFilePath\n";
            } else {

                $expected = @file_get_contents($expectedFilePath);

                $this->assertEquals($expected, $actual);
            }
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideTestModuleConfiguration()
    {
        return JSON::loadFile(
            $this->testFiles->getFile(
                'configuration',
                'module_configuration',
                'input'
            )
        );
    }

    /**
     * @dataProvider  provideTestToAssocArray
     *
     * @param array $options
     * @throws \Exception
     */
    public function testToAssocArray(array $options)
    {
        $baseDir = dirname($this->testFiles->getFile('configuration', '.', 'input'));
        $baseFile = $this->testFiles->getFile('configuration', $options['base_file'], 'input');

        $actual = XdmodConfiguration::assocArrayFactory(
            $baseFile,
            $baseDir,
            null,
            $options['options']
        );

        $expectedFile = $this->testFiles->getFile('configuration', $options['expected']);
        if (!is_file($expectedFile)) {
            @file_put_contents($expectedFile, sprintf("%s\n", json_encode($actual, JSON_PRETTY_PRINT)));
            echo "\nGenerated output for $expectedFile\n";
        } else {
            $expected = Json::loadFile($expectedFile);

            $this->assertEquals(
                $expected,
                $actual,
                sprintf(
                    "For [%s]\nExpected: %s\nActual: %s\n",
                    $baseFile,
                    json_encode($expected),
                    json_encode($actual)
                )
            );
        }
    }

    public function provideTestToAssocArray()
    {
        return JSON::loadFile(
            $this->testFiles->getFile(
                'configuration',
                'to_assoc_array',
                'input'
            )
        );
    }

    /**
     * Test that checks that the local config files for a `Configuration` are sorted in alphabetical
     * order.
     *
     * @dataProvider provideTestLocalConfigReadOrder
     *
     * @param array $options
     *
     * @throws \Exception
     */
    public function testLocalConfigReadOrder(array $options)
    {
        $baseDir = dirname($this->testFiles->getFile('configuration', '.', 'input'));
        $baseFile = $this->testFiles->getFile('configuration', $options['base_file'], 'input');

        $localDir = $this->interpretDirOption($options['local_dir']);
        $localConfigDir = dirname(
            $this->testFiles->getfile(
                'configuration',
                implode(
                    DIRECTORY_SEPARATOR,
                    array(
                        '.',
                        $localDir,
                        '.')
                ),
                'input'
            )
        );

        $config = new XdmodConfiguration(
            $baseFile,
            $baseDir,
            null,
            array(
                'local_config_dir' => $localConfigDir
            )
        );
        $config->initialize();

        // Make sure that the actual is pretty-printed for ease of reading.


        $expectedFilePath = $this->testFiles->getFile('configuration', $options['expected']);
        if (!is_file($expectedFilePath)) {
            $actual = sprintf("%s\n", json_encode(json_decode($config->toJson()), JSON_PRETTY_PRINT));
            @file_put_contents($expectedFilePath, $actual);
            echo "\nGenerated expected output for $expectedFilePath\n";
        } else {
            $actual = json_decode($config->toJson());

            $expected = json_decode(@file_get_contents($expectedFilePath));

            $this->assertEquals($expected, $actual, sprintf(
                "Expected: %s\nActual: %s\n",
                json_encode($expected, JSON_PRETTY_PRINT),
                json_encode($actual, JSON_PRETTY_PRINT)
            ));
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideTestLocalConfigReadOrder()
    {
        return JSON::loadFile(
            $this->testFiles->getFile(
                'configuration',
                'read_order',
                'input'
            )
        );
    }

    protected function interpretDirOption($dir)
    {
        if (is_array($dir)) {
            return implode(
                DIRECTORY_SEPARATOR,
                $dir
            );
        }
        return $dir;
    }
}  // class EtlConfigurationTest

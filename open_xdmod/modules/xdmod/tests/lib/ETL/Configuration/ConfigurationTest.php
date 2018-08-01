<?php
/* ------------------------------------------------------------------------------------------
 * Component tests for ETL JSON configuration files
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-21
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTesting\ETL\Configuration;


use Configuration\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/output";

    /**
     * Test JSON parse errors
     *
     * @expectedException Exception
     */

    public function testJsonParseError()
    {
        $configObj = new Configuration(self::TEST_ARTIFACT_INPUT_PATH . '/parse_error.json');
        $configObj->initialize();
    }

    /**
     * Test basic parsing of a JSON file
     */

    public function testConfiguration()
    {
        $configObj = new Configuration(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config.json');
        $configObj->initialize();
        $generated = json_decode($configObj->toJson());
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config.json'));
        $this->assertEquals($generated, $expected);
    }

    /**
     * Test removal of comments from a JSON file
     */

    public function testComments()
    {
        $configObj = new Configuration(self::TEST_ARTIFACT_INPUT_PATH . '/comments.json');
        $configObj->initialize();
        $generated = json_decode($configObj->toJson());
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/comments.json'));
        $this->assertEquals($generated, $expected);
    }

    /**
     * Test inclusion of a reference with fully qualified path names.
     */

    public function testFullPathReference()
    {
        copy(self::TEST_ARTIFACT_INPUT_PATH . '/reference_target.json', '/tmp/reference_target.json');
        $configObj = new Configuration(self::TEST_ARTIFACT_INPUT_PATH . '/rfc6901_full_reference.json');
        $configObj->initialize();
        $generated = json_decode($configObj->toJson());
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/rfc6901_full_reference.json'));
        unlink('/tmp/reference_target.json');
        $this->assertEquals($generated, $expected);
    }

    /**
     * Test inclusion of a reference with a relative path name (base directory will be used)
     */

    public function testRelativePathReference()
    {
        copy(self::TEST_ARTIFACT_INPUT_PATH . '/reference_target.json', '/tmp/reference_target.json');
        $configObj = new Configuration(self::TEST_ARTIFACT_INPUT_PATH . '/rfc6901_relative_reference.json');
        $configObj->initialize();
        $generated = json_decode($configObj->toJson());
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/rfc6901_full_reference.json'));
        unlink('/tmp/reference_target.json');
        $this->assertEquals($generated, $expected);
    }

    /**
     * Test variables in the configuration file.
     */

    public function testConfigurationVariables()
    {
        $configObj = new Configuration(
            self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_variables.json',
            null,
            null,
            array('config_variables' => array('TABLE_NAME' => 'resource_allocations', 'WIDTH' => 40))
        );
        $configObj->initialize();
        $generated = json_decode($configObj->toJson());
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/sample_config.expected'));
        $this->assertEquals($generated, $expected);
    }
} // class ConfigurationTest

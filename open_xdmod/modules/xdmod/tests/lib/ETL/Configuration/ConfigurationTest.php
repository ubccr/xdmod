<?php
/* ------------------------------------------------------------------------------------------
 * Component tests for ETL JSON configuration files
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-21
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTesting\ETL\Configuration;

use CCR\Log;
use Configuration\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/configuration/output";

    protected static $logger = null;

    public static function setupBeforeClass()
    {
      // Set up a logger so we can get warnings and error messages from the ETL infrastructure
        $conf = array(
            'file' => false,
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Log::DEBUG
        );
        self::$logger = Log::factory('PHPUnit', $conf);
    }

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
     * Test inclusion of a reference with fully qualified path names.
     *
     * @expectedException Exception
     */

    public function testBadFragment()
    {
        $configObj = new Configuration(self::TEST_ARTIFACT_INPUT_PATH . '/rfc6901_bad_fragment.json');
        $configObj->initialize();
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

    /**
     * Test inclusion of a the following with:
     * - A JSON reference with variables in the referenced JSON
     * - A JSON-encoded include file with variables in the include path. Note that a comment is
     *   included in the reference object to ensure comments are removed before transformers are
     *   processed.
     * - A nested JSON reference
     */

    public function testJsonReferenceAndIncludeWithVariables()
    {
        @copy(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_variables.json', '/tmp/sample_config_with_variables.json');
        @copy(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_reference.json', '/tmp/sample_config_with_reference.json');
        $configObj = new Configuration(
            self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_transformer_keys.json',
            null,
            self::$logger,
            array(
                'config_variables' => array(
                    'TABLE_NAME' => 'resource_allocations',
                    'WIDTH' => 40,
                    'TMPDIR' => '/tmp',
                    'SQLDIR'  => 'etl_sql.d',
                    'SOURCE_SCHEMA' => 'modw'
                )
            )
        );
        $configObj->initialize();
        $generated = json_decode($configObj->toJson());
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/sample_config_with_transformer_keys.expected'));
        @unlink('/tmp/sample_config_with_variables.json');
        @unlink('/tmp/sample_config_with_reference.json');
        $this->assertEquals($generated, $expected, "Test multiple transformer directives");
    }
} // class ConfigurationTest

<?php
/* ------------------------------------------------------------------------------------------
 * Component tests for ETL JSON configuration files
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-21
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTests\ETL\Configuration;

use CCR\Log;
use Configuration\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/output";

    protected static $logger = null;

    public static function setupBeforeClass(): void
    {
      // Set up a logger so we can get warnings and error messages from the ETL infrastructure
        $conf = array(
            'file' => false,
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Log::EMERG
        );
        self::$logger = Log::factory('PHPUnit', $conf);
    }

    /**
     * Test JSON parse errors
     *
     *
     */

    public function testJsonParseError()
    {
        $this->expectException(\Exception::class);
        Configuration::factory(self::TEST_ARTIFACT_INPUT_PATH . '/parse_error.json');
    }

    /**
     * Test basic parsing of a JSON file
     */

    public function testConfiguration()
    {
        $configObj = Configuration::factory(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config.json');
        $generated = json_decode($configObj->toJson());
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config.json'));
        $this->assertEquals($generated, $expected);
    }

    /**
     * Test removal of comments from a JSON file
     */

    public function testComments()
    {
        $configObj = Configuration::factory(self::TEST_ARTIFACT_INPUT_PATH . '/comments.json');
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
        $configObj = Configuration::factory(self::TEST_ARTIFACT_INPUT_PATH . '/rfc6901_full_reference.json');
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
        $configObj = Configuration::factory(self::TEST_ARTIFACT_INPUT_PATH . '/rfc6901_relative_reference.json');
        $generated = json_decode($configObj->toJson());
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/rfc6901_full_reference.json'));
        unlink('/tmp/reference_target.json');
        $this->assertEquals($generated, $expected);
    }

    /**
     * Test inclusion of a reference with fully qualified path names.
     *
     *
     */

    public function testBadFragment()
    {
        $this->expectException(\Exception::class);
        Configuration::factory(self::TEST_ARTIFACT_INPUT_PATH . '/rfc6901_bad_fragment.json');
    }

    /**
     * Test variables in the configuration file.
     */

    public function testConfigurationVariables()
    {
        $configObj = Configuration::factory(
            self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_variables.json',
            null,
            null,
            array('config_variables' => array('TABLE_NAME' => 'resource_allocations', 'WIDTH' => 40))
        );
        $generated = json_decode($configObj->toJson());
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/sample_config.expected'));
        $this->assertEquals($generated, $expected);
    }

    /**
     * Test the following scenarios:
     * - A JSON reference with variables in the referenced JSON
     * - A JSON-encoded include file with variables in the include path. Note that a comment is
     *   included in the reference object to ensure comments are removed before transformers are
     *   processed.
     * - A nested JSON reference
     * - A double JSON reference (reference to a reference)
     */

    public function testJsonReferenceAndIncludeWithVariables()
    {
        @copy(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_variables.json', '/tmp/sample_config_with_variables.json');
        @copy(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_reference.json', '/tmp/sample_config_with_reference.json');
        @copy(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_double_reference.json', '/tmp/sample_config_with_double_reference.json');
        $configObj = Configuration::factory(
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
        $generated = json_decode($configObj->toJson());
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/sample_config_with_transformer_keys.expected'));
        @unlink('/tmp/sample_config_with_variables.json');
        @unlink('/tmp/sample_config_with_reference.json');
        $this->assertEquals($generated, $expected, "Test multiple transformer directives");
    }

    /**
     * Test the Configuration class local object cache. This is an array-based key-value store in
     * the Configuration object.
     */

    public function testLocalConfigurationObjectCache()
    {
        $tmpFile = sprintf('%s/sample_config_with_variables.json', sys_get_temp_dir());
        @copy(
            self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_variables.json',
            $tmpFile
        );

        // Force the system to use the local object cache

        Configuration::forceLocalObjectCache();

        // The object cache is enabled by default so objects 1 and 2 will be the same

        $configObj1 = Configuration::factory(
            $tmpFile,
            null,
            null,
            array('config_variables' => array('TABLE_NAME' => 'resource_allocations', 'WIDTH' => 40))
        );

        $configObj2 = Configuration::factory(
            $tmpFile,
            null,
            null,
            array('config_variables' => array('TABLE_NAME' => 'resource_allocations', 'WIDTH' => 40))
        );

        // Using the local object cache we can compare objects directly

        $this->assertTrue($configObj1 === $configObj2, "Local object cache");

        // Modify the file and ensure that the cache was update with a new object. Note that stat
        // has single second granularity so we must sleep(1) during the test or adjust the timestamp
        // accordingly.

        $currentTimestamp = time();
        touch($tmpFile, $currentTimestamp + 2);

        $configObj3 = Configuration::factory(
            $tmpFile,
            null,
            null,
            array('config_variables' => array('TABLE_NAME' => 'resource_allocations', 'WIDTH' => 40))
        );

        $this->assertTrue($configObj1 !== $configObj3, "Updating stale cache, newer file");

        // Test the case where an older file was copied over a newer file.

        touch($tmpFile, $currentTimestamp - 2);

        $configObj4 = Configuration::factory(
            $tmpFile,
            null,
            null,
            array('config_variables' => array('TABLE_NAME' => 'resource_allocations', 'WIDTH' => 40))
        );

        $this->assertTrue($configObj3 !== $configObj4, "Updating stale cache, older file");

        // Disable the cache and expect a new object

        Configuration::disableObjectCache();

        $configObj5 = Configuration::factory(
            $tmpFile,
            null,
            null,
            array('config_variables' => array('TABLE_NAME' => 'resource_allocations', 'WIDTH' => 40))
        );

        $this->assertTrue($configObj3 !== $configObj5, "Object cache disabled");

        @unlink($tmpFile);
    }

    /**
     * Test the APCu object cache. Objects retrieved from the cache are expected to be different
     * because they are serialized and then unserialzed but their JSON representation are expected
     * to be the same.
     */

    public function testApcuObjectCache()
    {
        // Copy the configuration file to a temporary directory so this test does not affect others.

        $tmpFile = sprintf('%s/sample_config_with_variables.json', sys_get_temp_dir());
        @copy(
            self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_variables.json',
            $tmpFile
        );

        // The object cache is enabled by default so objects 1 and 2 will be the same

        $configObj1 = Configuration::factory(
            $tmpFile,
            null,
            null,
            array('config_variables' => array('TABLE_NAME' => 'resource_allocations', 'WIDTH' => 40))
        );

        $configObj2 = Configuration::factory(
            $tmpFile,
            null,
            null,
            array('config_variables' => array('TABLE_NAME' => 'resource_allocations', 'WIDTH' => 40))
        );

        // We cannot compare the objects directly because those in the APCu cache have been
        // serialized and unserialized so expect 2 different objects with the same JSON
        // representation.

        $this->assertTrue($configObj1 !== $configObj2, "APCu object cache enabled, different objects");
        $this->assertJsonStringEqualsJsonString(
            $configObj1->toJson(),
            $configObj2->toJson(),
            "APCu object cache enabled, same JSON representation"
        );

        @unlink($tmpFile);
    }

    /**
     * Test calling Configuration::__construct() directly, which is not allowed.
     *
     *
     */

    public function testCallConfigurationConstructor()
    {
        $this->expectException(\Exception::class);
        new Configuration(
            self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_variables.json'
        );
    }

    /**
     * Test the JSON reference with overwrite in the following scenarios:
     * - Reference that overwrites scalars and objects
     * - Reference that does not generate an object (key_two in sample_config_with_variables)
     * - No '$overwrite' key
     */

    public function testJsonReferenceWithOverwriteWithVariables()
    {
        @copy(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_variables.json', '/tmp/sample_config_with_variables.json');
        $configObj = Configuration::factory(
            self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_json_overwrite_transformer_keys.json',
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
        $generated = json_decode($configObj->toJson());
        $expected = json_decode(file_get_contents(self::TEST_ARTIFACT_OUTPUT_PATH . '/sample_config_with_json_overwrite_transformer_keys.expected'));
        @unlink('/tmp/sample_config_with_variables.json');
        $this->assertEquals($expected, $generated, 'Test JSON reference with overwrite');
    }

    /**
     * Test the JSON reference with overwrite in the following scenarios:
     * - No '$overwrite' key present
     *
     *
     */

    public function testJsonReferenceWithOverwriteWithNoOverwriteDirective()
    {
        $this->expectException(\Exception::class);
        Configuration::factory(
            self::TEST_ARTIFACT_INPUT_PATH . '/sample_config_with_missing_json_overwrite_key.json',
            null,
            self::$logger,
            array(
                'config_variables' => array(
                    'TMPDIR' => '/tmp'
                )
            )
        );
    }
}

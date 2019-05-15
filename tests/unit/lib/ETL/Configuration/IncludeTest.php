<?php
/* ------------------------------------------------------------------------------------------
 * Test various values for an RFC-6901 pointer.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-21
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTesting\ETL\Configuration;

use CCR\Log;
use Configuration\Configuration;
use Configuration\IncludeTransformer;

class IncludeTest extends \PHPUnit_Framework_TestCase
{

    const TEST_ARTIFACT_INPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/output";

    protected static $transformer = null;
    protected static $config = null;

    public static function setupBeforeClass()
    {
      // Set up a logger so we can get warnings and error messages from the ETL infrastructure
        $conf = array(
            'file' => false,
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Log::WARNING
        );
        $logger = Log::factory('PHPUnit', $conf);
        
        // Configuration is used in the transformer to qualify relative paths
        self::$config = new Configuration(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config.json');
        self::$transformer = new IncludeTransformer($logger);
    }

    /**
     * Test invalid file
     *
     * @expectedException Exception
     */

    public function testIncludeInvalidFile()
    {
        $key = '$include';
        $value = 'file_does_not_exist.txt';
        $obj = (object) array($key => $value);
        self::$transformer->transform($key, $value, $obj, self::$config);
    }

    /**
     * Badly formed URL
     *
     * @expectedException Exception
     */

    public function testBadUrl()
    {
        $key = '$include';
        $value = 'badscheme://string';
        $obj = (object) array($key => $value);
        self::$transformer->transform($key, $value, $obj, self::$config);
    }

    /**
     * Include a document (e.g., SQL query)
     */

    public function testIncludeFile()
    {
        $key = '$include';
        $value = 'etl_sql.d/query.sql';
        $obj = (object) array($key => $value);
        $expected = json_encode(file_get_contents(self::TEST_ARTIFACT_INPUT_PATH . '/' . $value));
        self::$transformer->transform($key, $value, $obj, self::$config);

        // A null key means replace the entire value object with the transformed value
        $this->assertNull($key);
        $this->assertEquals($expected, $value, "JSON-encoded value");
    }
    
    /**
     * Test variables in the include URL.
     */

    public function testIncludeFileWithVariable()
    {
        self::$config->getVariableStore()->FILENAME = 'query';
        self::$config->getVariableStore()->SUBDIR = 'etl_sql.d';

        $key = '$include';
        $value = '${SUBDIR}/${FILENAME}.sql';
        $obj = (object) array($key => $value);
        self::$transformer->transform($key, $value, $obj, self::$config);

        $expected = json_encode(file_get_contents(self::TEST_ARTIFACT_INPUT_PATH . '/etl_sql.d/query.sql'));
        $this->assertNull($key);
        $this->assertEquals($expected, $value, "JSON-encoded value");
    }
}  // class IncludeTest

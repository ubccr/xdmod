<?php
/* ------------------------------------------------------------------------------------------
 * Test various values for an RFC-6901 pointer.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-21
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTests\ETL\Configuration;

use CCR\Log;
use Configuration\Configuration;
use Configuration\IncludeTransformer;
use PHPUnit\Framework\TestCase;
use Exception;

class IncludeTest extends TestCase
{

    const TEST_ARTIFACT_INPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/output";

    protected static $transformer = null;
    protected static $config = null;

    public static function setupBeforeClass(): void
    {
      // Set up a logger so we can get warnings and error messages from the ETL infrastructure
        $conf = array(
            'file' => false,
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Log::EMERG
        );
        $logger = Log::factory('PHPUnit', $conf);

        // Configuration is used in the transformer to qualify relative paths
        self::$config = Configuration::factory(self::TEST_ARTIFACT_INPUT_PATH . '/sample_config.json');
        self::$transformer = new IncludeTransformer($logger);
    }

    /**
     * Test invalid file
     *
     *
     */

    public function testIncludeInvalidFile()
    {
        $this->expectException(Exception::class);
        $key = '$include';
        $value = 'file_does_not_exist.txt';
        $obj = (object) array($key => $value);
        self::$transformer->transform($key, $value, $obj, self::$config, Log::ERR);
    }

    /**
     * Badly formed URL
     *
     *
     */

    public function testBadUrl()
    {
        $this->expectException(Exception::class);
        $key = '$include';
        $value = 'badscheme://string';
        $obj = (object) array($key => $value);
        self::$transformer->transform($key, $value, $obj, self::$config, Log::ERR);
    }

    /**
     * Include a document (e.g., SQL query)
     */

    public function testIncludeFile()
    {
        $key = '$include';
        $value = 'etl_sql.d/query.sql';
        $obj = (object) array($key => $value);
        $expected = file_get_contents(self::TEST_ARTIFACT_INPUT_PATH . '/' . $value);
        self::$transformer->transform($key, $value, $obj, self::$config, Log::ERR);

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
        self::$transformer->transform($key, $value, $obj, self::$config, Log::ERR);

        $expected = file_get_contents(self::TEST_ARTIFACT_INPUT_PATH . '/etl_sql.d/query.sql');
        $this->assertNull($key);
        $this->assertEquals($expected, $value, "JSON-encoded value");
    }
}  // class IncludeTest

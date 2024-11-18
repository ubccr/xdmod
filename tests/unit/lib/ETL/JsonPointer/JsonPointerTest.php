<?php
/* ------------------------------------------------------------------------------------------
 * Component tests for ETL JSON configuration files
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-04-21
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTests\ETL\JsonPointer;

use CCR\Log;
use ETL\JsonPointer;
use CCR\Loggable;

class JsonPointerTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./../artifacts/xdmod/etlv2/configuration/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./../artifacts/xdmod/etlv2/dbmodel/output";

    /**
     * @var \Monolog\Logger|\Psr\Log\LoggerInterface|null
     */
    private $logger = null;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        // Set up a logger so we can get warnings and error messages from the ETL
        // infrastructure
        $conf = array(
            'file' => false,
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Log::EMERG
        );
        $this->logger = Log::factory('PHPUnit', $conf);
        parent::__construct($name, $data, $dataName);
    }

    /**
     * Test various JSON pointers.
     */

    public function testJsonPointer()
    {
        $file = self::TEST_ARTIFACT_INPUT_PATH . DIRECTORY_SEPARATOR . 'sample_config.json';
        $fileContents = file_get_contents($file);
        $json = json_decode($fileContents);

        // Whole document
        $pointer = '';
        $generated = JsonPointer::extractFragment($fileContents, $pointer);
        $this->assertEquals($json, $generated);

        // Scalar value
        $pointer = '/key_one/name';
        $generated = JsonPointer::extractFragment($fileContents, $pointer);
        $this->assertEquals($json->key_one->name, $generated);

        // Object value
        $pointer = '/key_one';
        $generated = JsonPointer::extractFragment($fileContents, $pointer);
        $this->assertEquals($json->key_one, $generated);

        // Array value
        $pointer = '/key_two';
        $generated = JsonPointer::extractFragment($fileContents, $pointer);
        $this->assertEquals($json->key_two, $generated);

        // First element of an array
        $pointer = '/key_two/0';
        $generated = JsonPointer::extractFragment($fileContents, $pointer);
        $this->assertEquals($json->key_two[0], $generated);

        // Last element of an array
        $pointer = '/key_two/-';
        $generated = JsonPointer::extractFragment($fileContents, $pointer);
        $this->assertEquals(end($json->key_two), $generated);

    }  // testJsonPointer()
} // class JsonPointerTest

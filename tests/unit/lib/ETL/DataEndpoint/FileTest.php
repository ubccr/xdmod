<?php
/* ------------------------------------------------------------------------------------------
 * Component tests for ETL File DataEndpoints.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-05-15
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTesting\ETL\DataEndpoint;

use Exception;
use CCR\Log;
// use ETL\DataEndpoint\File;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;

class FileTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/dataendpoint/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./artifacts/xdmod-test-artifacts/xdmod/etlv2/dataendpoint/output";
    private $logger = null;

    public function __construct()
    {
        // Set up a logger so we can get warnings and error messages from the ETL
        // infrastructure
        $conf = array(
            'file' => false,
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Log::WARNING
        );

        $this->logger = Log::factory('PHPUnit', $conf);
    }  // __construct()

    /**
     * Test trying to read a directory instead of a file.
     *
     * @expectedException Exception
     */

    public function testNotFile()
    {
        $config = array(
            'name' => 'Not a file',
            'path' => sys_get_temp_dir(),
            'type' => 'file'
        );
        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);
        $file->verify();
    }  // testFileNotReadable()

    /**
     * Test trying to open a file with an invalid mode.
     *
     * @expectedException Exception
     */

    public function testBadFileMode()
    {
        $path = tempnam(sys_get_temp_dir(), 'xdmod_test');

        $config = array(
            'name' => 'Bad file mode',
            'path' => $path,
            'mode' => 'junk-mode',
            'type' => 'file'
        );

        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);

        try {
            $file->verify();
        } catch ( Exception $e ) {
            unlink($path);
            throw $e;
        }

        unlink($path);

    }  // testBadFileMode()

    /**
     * Test reading a simple string from a file.
     */

    public function testReadFile()
    {
        $path = tempnam(sys_get_temp_dir(), 'xdmod_test');
        $expected = "Random string to a file";
        file_put_contents($path, $expected);

        $config = array(
            'name' => 'Read test',
            'path' => $path,
            'type' => 'file'
        );

        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);
        $file->verify();
        $fh = $file->connect();

        $generated = fgets($fh);
        $file->disconnect();

        $this->assertEquals($expected, $generated);

        @unlink($path);

    }  // testReadFile()
}  // class FileTest

<?php
/* ------------------------------------------------------------------------------------------
 * Component tests for ETL File DataEndpoints.
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-05-15
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTests\ETL\DataEndpoint;

use Exception;
use CCR\Log;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;
use Psr\Log\LoggerInterface;

class FileTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ARTIFACT_INPUT_PATH = "./../artifacts/xdmod/etlv2/dataendpoint/input";
    const TEST_ARTIFACT_OUTPUT_PATH = "./../artifacts/xdmod/etlv2/dataendpoint/output";

    /**
     * @var LoggerInterface
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
    }  // __construct()

    /**
     * Test trying to read a directory instead of a file.
     *
     */

    public function testNotFile()
    {
        $this->expectException(Exception::class);
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
     */

    public function testBadFileMode()
    {
        $this->expectException(Exception::class);
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

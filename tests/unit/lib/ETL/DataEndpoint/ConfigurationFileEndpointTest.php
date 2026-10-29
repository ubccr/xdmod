<?php
/** -----------------------------------------------------------------------------------------
 * Unit tests for the ETL ConfigurationFileEndpoint. The following tests are implemented:
 *
 * #1: Parsing after verify() closes the file returns the configuration file records.
 * #2: Parsing after verify() leaves the file handle open (as the EtlOverseer does when not
 *     in dryrun mode) returns the merged configuration rather than data read from the
 *     handle opened during verification.
 * #3: parse() restores the original path and can be called more than once.
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTests\ETL\DataEndpoint;

use CCR\Log;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;
use Psr\Log\LoggerInterface;

class ConfigurationFileEndpointTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LoggerInterface
     */
    private $logger = null;

    /**
     * @var string Temporary directory holding the configuration files for a test.
     */
    private $tmpDir = null;

    /**
     * @var string Path to the configuration file parsed by the endpoint.
     */
    private $path = null;

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
     * Create a uniquely named configuration file for each test. The unique name ensures that
     * the Configuration object cache cannot return data from another test and that there is no
     * matching local configuration (.d) directory in CONFIG_DIR.
     */

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/' . uniqid('xdmod_configuration_file_endpoint_test_');
        mkdir($this->tmpDir);
        $this->path = sprintf('%s/%s.json', $this->tmpDir, basename($this->tmpDir));
        $this->writeResourceTypes($this->path, array('HPC', 'HTC'));
    }

    protected function tearDown(): void
    {
        foreach ( glob($this->tmpDir . '/*') as $file ) {
            unlink($file);
        }
        rmdir($this->tmpDir);
    }

    /**
     * Test #1: Parsing after verify() closes the file returns the configuration file records.
     */

    public function testParseAfterVerify()
    {
        $endpoint = $this->createEndpoint();
        $endpoint->verify();
        $endpoint->parse();

        $this->assertEquals(array('HPC', 'HTC'), $this->getAbbrevs($endpoint));
    }  // testParseAfterVerify()

    /**
     * Test #2: Parsing after verify() leaves the file handle open returns the merged
     * configuration rather than data read from the handle opened during verification.
     *
     * After verification the configuration file is atomically replaced with one containing an
     * additional resource type. The handle opened during verification still references the
     * original file, so reading from it would return only the original resource types.
     */

    public function testParseAfterVerifyLeaveConnected()
    {
        $endpoint = $this->createEndpoint();
        $endpoint->verify(false, true);

        $replacement = $this->path . '.new';
        $this->writeResourceTypes($replacement, array('HPC', 'HTC', 'Kubernetes'));
        rename($replacement, $this->path);

        $endpoint->parse();

        $this->assertEquals(array('HPC', 'HTC', 'Kubernetes'), $this->getAbbrevs($endpoint));
    }  // testParseAfterVerifyLeaveConnected()

    /**
     * Test #3: parse() restores the original path and can be called more than once.
     */

    public function testParseRestoresPathAndIsRepeatable()
    {
        $endpoint = $this->createEndpoint();
        $endpoint->verify(false, true);

        $endpoint->parse();
        $this->assertEquals($this->path, $endpoint->getPath());
        $this->assertEquals(array('HPC', 'HTC'), $this->getAbbrevs($endpoint));

        $endpoint->parse();
        $this->assertEquals($this->path, $endpoint->getPath());
        $this->assertEquals(array('HPC', 'HTC'), $this->getAbbrevs($endpoint));
    }  // testParseRestoresPathAndIsRepeatable()

    /**
     * Create a configurationfile endpoint using the same filter as the resource types ETL
     * actions.
     *
     * @return DataEndpoint\ConfigurationFileEndpoint
     */

    private function createEndpoint()
    {
        $config = array(
            'name' => 'Resource types',
            'path' => $this->path,
            'type' => 'configurationfile',
            'filters' => array(
                (object) array(
                    'type' => 'external',
                    'name' => 'jq',
                    'path' => 'jq',
                    'arguments' => "'[ .resource_types | to_entries[]| {abbrev: .key, description:.value.description} ] '"
                )
            )
        );

        $options = new DataEndpointOptions($config);
        return DataEndpoint::factory($options, $this->logger);
    }

    /**
     * Write a resource types configuration file.
     *
     * @param string $path The file to write.
     * @param array $abbrevs The resource type abbreviations to include in the file.
     */

    private function writeResourceTypes($path, array $abbrevs)
    {
        $resourceTypes = array();
        foreach ( $abbrevs as $abbrev ) {
            $resourceTypes[$abbrev] = array(
                'description' => "$abbrev resource",
                'realms' => array('Jobs')
            );
        }

        file_put_contents($path, json_encode(array('resource_types' => $resourceTypes)));
    }

    /**
     * @param DataEndpoint\ConfigurationFileEndpoint $endpoint
     *
     * @return array The resource type abbreviations parsed by the endpoint.
     */

    private function getAbbrevs($endpoint)
    {
        $abbrevs = array();
        foreach ( $endpoint as $record ) {
            $abbrevs[] = $record->abbrev;
        }
        return $abbrevs;
    }
}

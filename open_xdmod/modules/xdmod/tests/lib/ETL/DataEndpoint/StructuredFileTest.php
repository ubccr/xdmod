<?php
/* ------------------------------------------------------------------------------------------
 * Component tests for ETL StructuredFile DataEndpoints
 *
 * @author Steve Gallo <smgallo@buffalo.edu>
 * @date 2017-05-15
 * ------------------------------------------------------------------------------------------
 */

namespace UnitTesting\ETL\DataEndpoint;

use CCR\Log;
use ETL\DataEndpoint;
use ETL\DataEndpoint\DataEndpointOptions;

class StructuredFileTest extends \PHPUnit_Framework_TestCase
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
     * Test parsing a simple JSON file containing an array of objects.
     */

    public function testParseJsonFileArray()
    {
        $expected = array(
            (object) array(
                'organizations' => array(
                    (object) array(
                        'division' => 'IN-OPTH',
                        'appointment_type' => 'Faculty',
                        'name' => 'Indiana University',
                        'id' => 'helegreen'
                    )
                ),
                'first_name' => 'Helen',
                'last_name' => 'Green',
                'groups' => array(
                    'BUS-KDFACULTY',
                    'AssociateProfessors-Tenured',
                    'CHEM-HiringCommitteeOne'
                )
            ),
            (object) array(
                'organizations' => array(
                    (object) array(
                        'division' => 'IN-HEMO',
                        'appointment_type' => 'Faculty',
                        'name' => 'Indiana University',
                        'id' => 'dorogreen'
                    )
                ),
                'first_name' => 'Dorothy',
                'last_name' => 'Green',
                'groups' => array(
                    'MDEP-Gastroenterology',
                    'Chemlearn-C484'
                )
            ),
             (object) array(
                 'organizations' => array(
                     (object) array(
                         'division' => 'IN-UROL',
                         'appointment_type' => 'Faculty',
                         'name' => 'Indiana University',
                         'id' => 'majohnson'
                     )
                 ),
                'first_name' => 'Mario',
                'last_name' => 'Johnson',
                'groups' => array(
                    'PSYC-CHFAC',
                    'IUCC-Newsletter',
                    'ET_STU03'
                )
            )
        );

        $path = self::TEST_ARTIFACT_INPUT_PATH . '/xdmod_va_users.json';
        $config = array(
            'name' => 'xdmod_va_users.json',
            'path' => $path,
            'type' => 'jsonfile'
        );

        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);
        $file->verify();
        $file->parse();

        foreach ($file as $index => $record) {
            $this->assertEquals($expected[$index], $record);
        }
    }  // testParseJsonFileArray()

    /**
     * Test parsing a simple JSON file containing multiple objects, each on a single line,
     * separated by a newline.
     */

    public function testParseJsonFileRecords()
    {
        $expected = array(
            (object) array(
                'node_controller' => null,
                'public_ip' => null,
                'account' => '000048934329',
                'event_type' => 'STATE_REPORT',
                'event_time' => '2017-05-16T03:55:04Z',
                'instance_type' => (object) array(
                    'name' => 'c1.medium',
                    'cpu' => '4',
                    'memory' => '16384',
                    'disk' => '40',
                    'networkInterfaces' => '2'
                ),
                'image_type' => 'emi-521695e8',
                'instance_id' => 'i-cb13943e',
                'record_type' => 'ADMINISTRATIVE',
                'block_devices' => array(
                    (object) array(
                        'account' => 'big',
                        'attach_time' => '2017-04-19T13:47:38.609Z',
                        'backing' => 'ebs',
                        'create_time' => '2017-04-19T13:47:38.550Z',
                        'user' => 'tyearke',
                        'id' => 'vol-6a9b5bc2',
                        'size' => '40'
                    )
                ),
                'private_ip' => null,
                'root_type' => 'ebs'
            ),
            (object) array(
                'node_controller' => '172.17.0.31',
                'public_ip' => '199.109.192.61',
                'account' => '000669660540',
                'event_type' => 'STATE_REPORT',
                'event_time' => '2017-05-16T03:55:04Z',
                'instance_type' => (object) array(
                    'name' => 'm1.medium',
                    'cpu' => '2',
                    'memory' => '4096',
                    'disk' => '20',
                    'networkInterfaces' => '2'
                ),
                'image_type' => 'emi-3f83abf8',
                'instance_id' => 'i-dd04e6bf',
                'record_type' => 'ADMINISTRATIVE',
                'block_devices' => array(
                    (object) array(
                        'account' => 'redfly',
                        'attach_time' => '2017-03-21T16:57:45.376Z',
                        'backing' => 'ebs',
                        'create_time' => '2017-03-21T16:57:45.330Z',
                        'user' => 'riveraj',
                        'id' => 'vol-dae393e0',
                        'size' => '10'
                    )
                ),
                'private_ip' => '172.17.47.126',
                'root_type' => 'ebs'
            )
        );

        $path = self::TEST_ARTIFACT_INPUT_PATH . '/euca_acct.json';
        $config = array(
            'name' => 'euca_acct.json',
            'path' => $path,
            'type' => 'jsonfile',
            'record_separator' => "\n"
        );

        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);
        $file->verify();
        $file->parse();

        foreach ($file as $index => $record) {
            $this->assertEquals($expected[$index], $record);
        }
    }

    /**
     * Test error reporting when config is not valid.
     *
     * @expectedException Exception
     */

    public function testInvalidFilterConfig()
    {
        $path = self::TEST_ARTIFACT_INPUT_PATH . '/xdmod_va_users.json';
        $config = array(
            'name' => 'xdmod_va_users.json',
            'path' => $path,
            'type' => 'jsonfile',
            // Filters should be an array
            'filters' => (object) array(
                'jq' => (object) array(
                    'path' => 'jq',
                    'arguments' => "'map({ name: .organizations[].name})|unique'"
                )
            )
        );

        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);

    }  // testInvalidFilterConfig()

    /**
     * Test error reporting when a filter type is not provided.
     *
     * @expectedException Exception
     */

    public function testMissingFilterType()
    {
        $path = self::TEST_ARTIFACT_INPUT_PATH . '/xdmod_va_users.json';
        $config = array(
            'name' => 'xdmod_va_users.json',
            'path' => $path,
            'type' => 'jsonfile',
            'filters' => array(
                (object) array(
                    // Need a filter type 'type' => 'external'
                    'name' => 'jq',
                    'path' => 'jq',
                    'arguments' => "'map({ name: .organizations[].name}) | unique'"
                )
            )
        );

        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);
        $file->verify();
        // Filters are not crearted until parse() is called
        $file->parse();

    }  // testMissingFilterType()

    /**
     * Test filter syntax error.
     *
     * @expectedException Exception
     */

    public function testFilterSyntaxError()
    {
        $path = self::TEST_ARTIFACT_INPUT_PATH . '/xdmod_va_users.json';
        $config = array(
            'name' => 'xdmod_va_users.json',
            'path' => $path,
            'type' => 'jsonfile',
            'filters' => array(
                (object) array(
                    'type' => 'external',
                    'name' => 'jq',
                    'path' => 'jq',
                    // The single quotes should be included IN the string, an exception will be thrown
                    'arguments' => 'map({ name: .organizations[].name})|unique'
                )
            )
        );

        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);
        $file->verify();
        $file->parse();
    }  // testFilterSyntaxError()

    /**
     * Test unknown filter executable.
     *
     * @expectedException Exception
     */

    public function testInvalidFilter()
    {
        $path = self::TEST_ARTIFACT_INPUT_PATH . '/empty.json';
        $config = array(
            'name' => 'empty.json',
            'path' => $path,
            'type' => 'jsonfile',
            'filters' => array(
                (object) array(
                    'type' => 'external',
                    'name' => 'unknown',
                    'path' => 'gobbledygook'
                )
            )
        );

        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);
        $file->verify();
        $file->parse();

    }  // testInvalidFilter()

    /**
     * Test parsing of an empty file.
     */

    public function testEmptyFile()
    {
        $path = self::TEST_ARTIFACT_INPUT_PATH . '/empty.json';
        $config = array(
            'name' => 'empty.json',
            'path' => $path,
            'type' => 'jsonfile',
            'filters' => array(
                (object) array(
                    'type' => 'external',
                    'name' => 'jq',
                    'path' => 'jq',
                    'arguments' => "'.'"
                )
            )
        );

        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);
        $file->verify();
        $this->assertFalse($file->parse());

    }  // testEmptyFile()

    /**
     * Test parsing a simple JSON file containing an array of objects filtered through an
     * external process.
     */

    public function testParseJsonFileFilteredArray()
    {
        $expected = (object) array(
            'name' => 'Indiana University'
        );

        $path = self::TEST_ARTIFACT_INPUT_PATH . '/xdmod_va_users.json';
        $config = array(
            'name' => 'xdmod_va_users.json',
            'path' => $path,
            'type' => 'jsonfile',
            'filters' => array(
                (object) array(
                    'type' => 'external',
                    'name' => 'jq',
                    'path' => 'jq',
                    // Retrive the list of unique org names as a list of objects
                    'arguments' => "'map({ name: .organizations[].name})|unique'"
                )
            )
        );

        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);
        $file->verify();
        // We are expecting a single unique name
        $generated = $file->parse();

        $this->assertEquals($expected, $generated);
    }  // testParseJsonFileFilteredArray()

    /**
     * Test parsing a simple JSON file containing multiple records separated by a newline
     * and filtered through an external process.
     */

    public function testParseJsonFileFilteredRecords()
    {
        $expected = array(
            (object) array(
                'name' => 'c1.medium',
                'cpu' => '4',
                'memory' => '16384',
                'disk' => '40',
                'networkInterfaces' => '2'
            ),
            (object) array(
                'name' => 'm1.medium',
                'cpu' => '2',
                'memory' => '4096',
                'disk' => '20',
                'networkInterfaces' => '2'
            )
        );

        $path = self::TEST_ARTIFACT_INPUT_PATH . '/euca_acct.json';
        $config = array(
            'name' => 'euca_acct.json',
            'path' => $path,
            'type' => 'jsonfile',
            'record_separator' => "\n",
            'filters' => array(
                (object) array(
                    'type' => 'external',
                    'name' => 'jq',
                    'path' => 'jq',
                    // Retrieve the instance type object from each record. Note the -c to
                    // preserve the newline as record separator
                    'arguments' => "-c '.instance_type'"
                )
            )
        );

        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);
        $file->verify();
        // We are expecting a single unique name
        $file->parse();

        foreach ($file as $index => $record) {
            $this->assertEquals($expected[$index], $record);
        }
    }  // testParseJsonFileFilteredRecords()

    /**
     * Test successful JSON schema validation.
     */

    public function testSchemaValidationSuccess()
    {
        $expected = (object) array(
            'organizations' => array(
                (object) array(
                    'division' => 'IN-OPTH',
                    'appointment_type' => 'Faculty',
                    'name' => 'Indiana University',
                    'id' => 'helegreen'
                )
            ),
            'first_name' => 'Helen',
            'last_name' => 'Green',
            'groups' => array(
                'BUS-KDFACULTY',
                'AssociateProfessors-Tenured',
                'CHEM-HiringCommitteeOne'
            )
        );

        $path = self::TEST_ARTIFACT_INPUT_PATH . '/xdmod_va_users.json';
        $config = array(
            'name' => 'xdmod_va_users.json',
            'path' => $path,
            'type' => 'jsonfile',
            'record_schema_path' => self::TEST_ARTIFACT_INPUT_PATH . '/person.schema.json'
        );
        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);
        $file->verify();
        $generated = $file->parse();

        $this->assertEquals($expected, $generated);

    }  // testSchemaValidationSuccess()

    /**
     * Test failed JSON schema validation.
     *
     * @expectedException Exception
     */

    public function testSchemaValidationFailure()
    {

        $path = self::TEST_ARTIFACT_INPUT_PATH . '/xdmod_va_bad_users.json';
        $config = array(
            'name' => 'xdmod_va_bad_users.json',
            'path' => $path,
            'type' => 'jsonfile',
            'record_schema_path' => self::TEST_ARTIFACT_INPUT_PATH . '/person.schema.json'
        );
        $options = new DataEndpointOptions($config);
        $file = DataEndpoint::factory($options, $this->logger);
        $file->verify();
        $generated = $file->parse();

    }  // testSchemaValidationFailure()
}  // class StructuredFileTest

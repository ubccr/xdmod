<?php
/**
 * Test aspects of the GroupBy class that require connection to a database. These include:
 * - generateQueryParameterLabelsFromRequest()
 * - getAttributeValues()
 */

namespace ComponentTests\GroupBy;

use CCR\Log as Logger;
use Psr\Log\LoggerInterface;
use Realm\Realm;

class GroupByTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @var LoggerInterface|null
     */
    protected static $logger = null;

    public static function setupBeforeClass(): void
    {
        // Set up a logger so we can get warnings and error messages
        $conf = array(
            'file' => false,
            'db' => false,
            'mail' => false,
            'consoleLogLevel' => Logger::EMERG
        );
        self::$logger = Logger::factory('PHPUnit', $conf);

        // In order to use a non-standard location for datawarehouse.json we must manually
        // initialize the Realm class.

        $options = (object) array(
            'config_file_name' => 'datawarehouse.json',
            'config_base_dir'  => realpath('../artifacts/xdmod/realm')
        );

        Realm::initialize(self::$logger, $options);
    }

    /**
     * (1) generateQueryParameterLabelsFromRequest()
     */

    public function testGenerateQueryParameterLabelsFromRequest()
    {
        $realm = Realm::factory('Jobs', self::$logger);

        // GroupBy with a single columm key

        $obj = $realm->getGroupByObject('person');
        $simulatedRequest = array(
            'person' => '10',
            'person_filter' => '20,30'
        );
        $parameters = $obj->generateQueryParameterLabelsFromRequest($simulatedRequest);

        $generated = array_shift($parameters);
        $expected = "User = ( Moorhen,  Shearwater, Great,  Warbler, Cetti's )";
        $this->assertEquals($expected, $generated, 'generateQueryFiltersFromRequest()');

        // GroupBy with a multi-column key (2 columns in attribute table and 2 in aggregate table).
        // Multi-column keys use a carat (^) to separate the keys in filters.

        $obj = $realm->getGroupByObject('resource');
        $simulatedRequest = array(
            'resource' => '1^frearson',
            'resource_filter' => '2^mortorq,3^phillips'
        );
        $parameters = $obj->generateQueryParameterLabelsFromRequest($simulatedRequest);

        $generated = array_shift($parameters);
        $expected = "Resource = ( Frearson-frearson,  Mortorq-mortorq,  Phillips-phillips )";
        $this->assertEquals($expected, $generated, 'generateQueryFiltersFromRequest()');
    }

    /**
     * (2) getAttributeValues()
     */

    public function testGetAttributeValues()
    {
        $realm = Realm::factory('Jobs', self::$logger);

        // GroupBy resource uses a 2 column key
        $obj = $realm->getGroupByObject('resource');

        $values = $obj->getAttributeValues();
        $this->assertCount(9, $values, 'Number of resource attributes returned with no filter');

        $restrictions = array(
            'id' => '1^frearson'
        );
        $values = $obj->getAttributeValues($restrictions);
        $this->assertCount(1, $values, 'Number of resource attributes returned with id = 1^frearson');

        $restrictions = array(
            'name' => 'mortorq'
        );
        $values = $obj->getAttributeValues($restrictions);
        $this->assertCount(1, $values, 'Number of resource attributes returned with name = mortorq');

        $restrictions = array(
            'id'   => '2^mortorq',
            'name' => 'mortorq'
        );
        $values = $obj->getAttributeValues($restrictions);
        $this->assertCount(1, $values, 'Number of resource attributes returned with id = 2^motorq, name = mortorq');

        $restrictions = array(
            'id'   => '1^motorq',
            'name' => 'mortorq'
        );
        $values = $obj->getAttributeValues($restrictions);
        $this->assertCount(0, $values, 'Number of resource attributes returned with id = 1^motorq, name = mortorq');

        $obj = $realm->getGroupByObject('person');

        $restrictions = array(
            'name' => 'Moorhen'
        );
        $values = $obj->getAttributeValues($restrictions);
        $this->assertCount(1, $values, 'Number of person attributes returned with id = 552');
    }
}

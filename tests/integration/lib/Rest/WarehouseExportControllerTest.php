<?php

namespace IntegrationTests\Rest;

use CCR\DB;
use JsonSchema\Validator;
use PHPUnit_Framework_TestCase;
use TestHarness\XdmodTestHelper;
use TestHarness\TestFiles;
use DataWarehouse\Export\QueryHandler;
use XDUser;

/**
 * Test data warehouse export REST endpoints.
 */
class WarehouseExportControllerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test files base path.
     */
    const TEST_GROUP = 'integration/rest/warehouse-export';

    /**
     * User roles and usernames.
     * @var string[]
     */
    private static $userRoles = [
        'pub' => null,
        'usr' => 'normaluser',
        'pi' => 'principal',
        'cs' => 'centerstaff',
        'cd' => 'centerdirector',
        'mgr' => 'admin'
    ];

    /**
     * User for each role.
     * @var XDUser[]
     */
    private static $users = [];

    /**
     * Instances of XdmodTestHelper for each user role.
     * @var XdmodTestHelper[]
     */
    private static $helpers = [];

    /**
     * Database handle.
     * @var iDatabase
     */
    private static $dbh;

    /**
     * Database handle.
     * @var QueryHandler
     */
    private static $queryHandler;

    /**
     * JSON schema validator.
     * @var Validator
     */
    private static $schemaValidator;

    /**
     * JSON schema objects.
     * @var stdClass[]
     */
    private static $schemaCache = [];

    /**
     * @var TestFiles
     */
    private static $testFiles;

    /**
     * @return TestFiles
     */
    private static function getTestFiles()
    {
        if (!isset(self::$testFiles)) {
            self::$testFiles = new TestFiles(__DIR__ . '/../../../');
        }

        return self::$testFiles;
    }

    /**
     * Instantiate fixtures and authenticate helpers.
     */
    public static function setUpBeforeClass()
    {
        foreach (self::$userRoles as $role => $username) {
            self::$helpers[$role] = new XdmodTestHelper();

            if ($role !== 'pub') {
                self::$users[$role] = XDUser::getUserByUserName($username);
                self::$helpers[$role]->authenticate($role);
            }
        }

        self::$dbh = DB::factory('database');
        self::$schemaValidator = new Validator();
        self::$queryHandler = new QueryHandler();
    }

    /**
     * Logout and unset fixtures.
     */
    public static function tearDownAfterClass()
    {
        foreach (self::$helpers as $helper) {
            $helper->logout();
        }

        self::$users = null;
        self::$helpers = null;
        self::$dbh = null;
        self::$schemaValidator = null;
        self::$queryHandler = null;
        self::$testFiles = null;
    }

    /**
     * Load a JSON schema file.
     *
     * @return stdClass
     */
    private static function getSchema($schema)
    {
        if (!array_key_exists($schema, self::$schemaCache)) {
            static::$schemaCache[$schema] = self::getTestFiles()->loadJsonFile(
                'schema',
                $schema . '.schema',
                'warehouse-export',
                false
            );
        }

        return self::$schemaCache[$schema];
    }

    /**
     * Validate content against a JSON schema.
     *
     * Test the results of the validation with an assertion.
     *
     * @param mixed $content The content to validate.
     * @param string $schema The name of the schema file (without ".schema.json").
     * @param string $message The message to use in the assertion.
     */
    public function validateAgainstSchema(
        $content,
        $schema,
        $message = 'Validated against JSON schema'
    ) {
        // The content may have been decoded as an associative array so it needs
        // to be encoded and decoded again as a stdClass before it is validated.
        $normalizedContent = json_decode(json_encode($content));

        self::$schemaValidator->check(
            $normalizedContent,
            self::getSchema($schema)
        );
        $this->assertTrue(self::$schemaValidator->isValid(), $message);

        foreach ( self::$schemaValidator->getErrors() as $error) {
            $this->assertTrue(
                false,
                sprintf("[%s] %s\n", $error['property'], $error['message'])
            );
        }
    }

    /**
     * Test getting the list of exportable realms.
     *
     * @dataProvider getRealmsProvider
     */
    public function testGetRealms($role, $httpCode, $schema, $realms)
    {
        list($content, $info, $headers) = self::$helpers[$role]->get('rest/warehouse/export/realms');
        $this->assertEquals($httpCode, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, $schema);
    }

    /**
     * Test creating a new export request.
     *
     * @dataProvider createRequestProvider
     */
    public function testCreateRequest($role, $params, $httpCode, $schema)
    {
        list($content, $info, $headers) = self::$helpers[$role]->post('rest/warehouse/export/request', $params);
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test getting the list of export requests.
     *
     * @depends testCreateRequest
     * @dataProvider getRequestsProvider
     */
    public function testGetRequests()
    {
        list($content, $info, $headers) = self::$helpers[$role]->get('rest/warehouse/export/requests');
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test getting the exported data.
     *
     * @depends testCreateRequest
     * #dataProvider getRequestProvider
     */
    public function testGetRequest()
    {
        list($content, $info, $headers) = self::$helpers[$role]->get('rest/warehouse/export/request/' . $id);
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test deleting an export request.
     *
     * @depends testCreateRequest
     * #dataProvider deleteRequestProvider
     */
    public function testDeleteRequest()
    {
        list($content, $info, $headers) = self::$helpers[$role]->delete('rest/warehouse/export/request/' . $id);
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * Test deleting multiple export requests at a time.
     */
    public function testDeleteRequests()
    {
        $data = json_encode([]);
        //list($content, $info, $headers) = self::$helpers[$role]->delete('rest/warehouse/export/requests', $data);
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    public function getRealmsProvider()
    {
        return self::getTestFiles()->loadJsonFile(self::TEST_GROUP, 'get-realms', 'input');
    }

    public function createRequestProvider()
    {
        return self::getTestFiles()->loadJsonFile(self::TEST_GROUP, 'create-request', 'input');
    }

    public function getRequestsProvider()
    {
        return self::getTestFiles()->loadJsonFile(self::TEST_GROUP, 'get-requests', 'input');
    }

    public function getRequestProvider()
    {
        return self::getTestFiles()->loadJsonFile(self::TEST_GROUP, 'get-request', 'input');
    }

    public function deleteRequestProvider()
    {
        return self::getTestFiles()->loadJsonFile(self::TEST_GROUP, 'delete-request', 'input');
    }

    public function deleteRequestsProvider()
    {
        return self::getTestFiles()->loadJsonFile(self::TEST_GROUP, 'delete-requests', 'input');
    }
}

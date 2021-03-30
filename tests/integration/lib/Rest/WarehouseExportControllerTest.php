<?php

namespace IntegrationTests\Rest;

use CCR\DB;
use DataWarehouse\Export\FileManager;
use DataWarehouse\Export\QueryHandler;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use PHPUnit_Framework_TestCase;
use TestHarness\TestFiles;
use TestHarness\XdmodTestHelper;
use XDUser;

/**
 * Test data warehouse export REST endpoints.
 *
 * @coversDefaultClass \Rest\Controllers\WarehouseExportControllerProvider
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
     * Data warehouse export file manager.
     * @var FileManager
     */
    private static $fileManager;

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

        list($row) = self::$dbh->query('SELECT COUNT(*) AS count FROM batch_export_requests');
        if ($row['count'] > 0) {
            error_log(sprintf('Expected 0 rows in moddb.batch_export_requests, found %d', $row['count']));
        }

        self::$schemaValidator = new Validator();
        self::$queryHandler = new QueryHandler();
        self::$fileManager = new FileManager();
    }

    /**
     * Logout and unset fixtures.
     */
    public static function tearDownAfterClass()
    {
        foreach (self::$helpers as $helper) {
            $helper->logout();
        }

        // Delete any requests that weren't already deleted.
        self::$dbh->execute('DELETE FROM batch_export_requests');

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
        $message = 'Validate against JSON schema'
    ) {
        // The content may have been decoded as an associative array so it needs
        // to be encoded and decoded again as a stdClass before it is validated.
        $normalizedContent = json_decode(json_encode($content));

        // Data (numbers, etc.) are returned from MySQL as strings and likewise
        // returned from the REST endpoint as string.  Using
        // CHECK_MODE_COERCE_TYPES to allow these values.
        self::$schemaValidator->validate(
            $normalizedContent,
            self::getSchema($schema),
            Constraint::CHECK_MODE_COERCE_TYPES
        );

        $errors = self::$schemaValidator->getErrors();
        $this->assertCount(
            0,
            $errors,
            $message . "\n" . implode("\n", array_map(
                function ($error) {
                    return sprintf("[%s] %s", $error['property'], $error['message']);
                },
                $errors
            ))
        );
    }

    /**
     * Test getting the list of exportable realms.
     *
     * @param string $role Role to use during test.
     * @param int $httpCode Expected HTTP response code.
     * @param string $schema Name of JSON schema file that will be used
     *   to validate returned data.
     * @param array $realms The name of the realms that are expected to
     *   be in the returned data.
     * @covers ::getRealms
     * @dataProvider getRealmsProvider
     */
    public function testGetRealms($role, $httpCode, $schema, array $realms)
    {
        list($content, $info, $headers) = self::$helpers[$role]->get('rest/warehouse/export/realms');
        $this->assertRegExp('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals($httpCode, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, $schema);

        // Only check data for successful requests.
        if ($httpCode == 200) {
            // Testing each realm individually to avoid putting field
            // definitions in test artifacts.
            $this->assertTrue(is_array($content), 'Content is an array');
            $this->assertArrayHasKey('data', $content, 'Content has a "data" key');
            $this->assertTrue(is_array($content['data']), 'Data is an array');
            $this->assertCount(count($realms), $content['data'], 'Data contains correct number of realms');
            foreach ($content['data'] as $i => $realm) {
                $this->assertArraySubset($realms[$i], $realm, sprintf('Realm %d contains the expected subset', $i + 1));
            }
        }
    }

    /**
     * Test creating a new export request.
     *
     * @param string $role Role to use during test.
     * @param int $httpCode Expected HTTP response code.
     * @param string $schema Name of JSON schema file that will be used
     *   to validate returned data.
     * @covers ::createRequest
     * @dataProvider createRequestProvider
     */
    public function testCreateRequest($role, array $params, $httpCode, $schema)
    {
        list($content, $info, $headers) = self::$helpers[$role]->post('rest/warehouse/export/request', null, $params);
        $this->assertRegExp('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals($httpCode, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, $schema);
    }

    /**
     * Test getting the list of export requests.
     *
     * @param string $role Role to use during test.
     * @param int $httpCode Expected HTTP response code.
     * @param string $schema Name of JSON schema file that will be used
     *   to validate returned data.
     * @param array $requests Export requests expected to exist.
     * @covers ::getRequests
     * @depends testCreateRequest
     * @dataProvider getRequestsProvider
     */
    public function testGetRequests(
        $role,
        $httpCode,
        $schema,
        array $requests
    ) {
        list($content, $info, $headers) = self::$helpers[$role]->get('rest/warehouse/export/requests');
        $this->assertRegExp('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals($httpCode, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, $schema);

        // Only check data for successful requests.
        if ($httpCode == 200) {
            $this->assertArraySubset($requests, $content['data'], 'Data contains requests');
        }
    }

    /**
     * Test getting the exported data.
     *
     * @covers ::getExportedDataFile
     */
    public function testDownloadExportedDataFile()
    {
        $role = 'usr';
        $zipContent = 'Mock Zip File';
        $id = self::$queryHandler->createRequestRecord(self::$users[$role]->getUserID(), 'jobs', '2019-01-01', '2019-01-31', 'CSV');
        self::$queryHandler->submittedToAvailable($id);
        @file_put_contents(self::$fileManager->getExportDataFilePath($id), $zipContent);
        list($content, $info, $headers) = self::$helpers[$role]->get('rest/warehouse/export/download/' . $id);
        $this->assertRegExp('#\bapplication/zip\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals(200, $info['http_code'], 'HTTP response code');
        $this->assertEquals($zipContent, $content, 'Download content');
        self::$fileManager->removeExportFile($id);
        self::$queryHandler->deleteRequest($id, self::$users[$role]->getUserID());
    }

    /**
     * Test deleting an export request.
     *
     * Creates an export and then deletes it.
     *
     * @param string $role Role to use during test.
     * @param array $params Parameters to create an export request.
     * @param int $httpCode Expected HTTP response code.
     * @param string $schema Name of JSON schema file that will be used
     *   to validate returned data.
     * @covers ::deleteRequest
     * @uses ::createRequest
     * @uses ::getRequests
     * @dataProvider deleteRequestProvider
     */
    public function testDeleteRequest($role, array $params, $httpCode, $schema)
    {
        // Get list of requests before deletion.
        list($beforeContent) = self::$helpers[$role]->get('rest/warehouse/export/requests');
        $dataBefore = $beforeContent['data'];

        list($createContent) = self::$helpers[$role]->post('rest/warehouse/export/request', null, $params);
        $id = $createContent['data'][0]['id'];

        list($content, $info, $headers) = self::$helpers[$role]->delete('rest/warehouse/export/request/' . $id);
        $this->assertRegExp('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals($httpCode, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, $schema);
        $this->assertEquals($id, $content['data'][0]['id'], 'Deleted ID is in response');

        // Get list of requests after deletion
        list($afterContent) = self::$helpers[$role]->get('rest/warehouse/export/requests');
        $dataAfter = $afterContent['data'];

        $this->assertEquals($dataBefore, $dataAfter, 'Data before and after creation/deletion are the same.');
    }

    /**
     * Test deleting an export request in cases where it is expected to fail.
     *
     * @covers ::deleteRequest
     */
    public function testDeleteRequestErrors()
    {
        // Public user can't delete anything.
        list($content, $info, $headers) = self::$helpers['pub']->delete('rest/warehouse/export/request/1');
        $this->assertRegExp('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals(401, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, 'error');

        // Non-integer ID.
        list($content, $info, $headers) = self::$helpers['usr']->delete('rest/warehouse/export/request/abc');
        $this->assertRegExp('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals(404, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, 'error');

        // Trying to delete a non-existent request.
        list($row) = self::$dbh->query('SELECT MAX(id) + 1 AS id FROM batch_export_requests');
        list($content, $info, $headers) = self::$helpers['usr']->delete('rest/warehouse/export/request/' . $row['id']);
        $this->assertRegExp('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals(404, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, 'error');

        // Trying to delete another user's request.
        list($row) = self::$dbh->query('SELECT id FROM batch_export_requests WHERE user_id = :user_id LIMIT 1', ['user_id' => self::$users['pi']->getUserId()]);
        list($content, $info, $headers) = self::$helpers['usr']->delete('rest/warehouse/export/request/' . $row['id']);
        $this->assertRegExp('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals(404, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, 'error');
    }

    /**
     * Test deleting multiple export requests at a time.
     *
     * @param string $role Role to use during test.
     * @param int $httpCode Expected HTTP response code.
     * @param string $schema Name of JSON schema file that will be used
     *   to validate returned data.
     * @covers ::deleteRequests
     * @uses ::getRequests
     * @dataProvider deleteRequestsProvider
     */
    public function testDeleteRequests($role, $httpCode, $schema)
    {
        // Get list of requests before deletion.
        list($beforeContent) = self::$helpers[$role]->get('rest/warehouse/export/requests');

        // Gather ID values and also convert to integers for the array
        // comparison done below.
        $ids = [];
        foreach ($beforeContent['data'] as &$datum) {
            $datum['id'] = (int)$datum['id'];
            $ids[] = $datum['id'];
        }
        $data = json_encode($ids);

        // Delete all existing requests.
        list($content, $info, $headers) = self::$helpers[$role]->delete('rest/warehouse/export/requests', null, $data);
        $this->assertRegExp('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals($httpCode, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, $schema);
        $this->assertArraySubset($content['data'], $beforeContent['data'], 'Deleted IDs are in response');

        // Get list of requests after deletion
        list($afterContent) = self::$helpers[$role]->get('rest/warehouse/export/requests');
        $this->assertEquals([], $afterContent['data'], 'Data after deletion is empty.');
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

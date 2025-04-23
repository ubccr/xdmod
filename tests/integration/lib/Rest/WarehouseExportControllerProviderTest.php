<?php

namespace IntegrationTests\Rest;

use CCR\DB;
use DataWarehouse\Export\FileManager;
use DataWarehouse\Export\QueryHandler;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use IntegrationTests\TokenAuthTest;
use JsonSchema\Constraints\Constraint;
use JsonSchema\Validator;
use \PHPUnit\Framework\TestCase;
use IntegrationTests\TestHarness\XdmodTestHelper;
use XDUser;

/**
 * Test data warehouse export REST endpoints.
 *
 * @coversDefaultClass \Rest\Controllers\WarehouseExportControllerProvider
 */
class WarehouseExportControllerProviderTest extends TokenAuthTest
{
    use ArraySubsetAsserts;

    /**
     * Directory containing test artifact files.
     */
    const TEST_GROUP = 'integration/rest/warehouse/export';

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
     * Instantiate fixtures and authenticate helpers.
     */
    public static function setupBeforeClass(): void
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
    public static function tearDownAfterClass(): void
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
            static::$schemaCache[$schema] = parent::getTestFiles()->loadJsonFile(
                'schema',
                $schema . '.schema',
                'warehouse/export',
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
     * @covers ::getRealms
     * @dataProvider provideTokenAuthTestData
     */
    public function testGetRealmsTokenAuth($role, $tokenType) {
        parent::runTokenAuthTest(
            $role,
            $tokenType,
            [
                'path' => 'warehouse/export/realms',
                'method' => 'get',
                'params' => null,
                'data' => null,
                'endpoint_type' => 'rest',
                'authentication_type' => 'token_optional'
            ],
            parent::validateSuccessResponse(function ($body, $assertMessage) {
                $validData = [
                    'Jobs' => ['count' => 28, 'index' => 0],
                    'Cloud' => ['count' => 16, 'index' => 0],
                    'ResourceSpecifications' => ['count' => 16, 'index' => 0]
                ];
                $this->assertSame(3, $body['total'], $assertMessage);
                foreach (array_keys($validData) as $realmName) {
                    // Save off the index for this realm so we can use it later to validate the count.
                    $validData[$realmName]['index'] = $index = array_search($realmName, array_column($body['data'], 'id'));
                    $realm = $body['data'][$index];
                    foreach (['id', 'name'] as $property) {
                        $this->assertSame(
                            $realmName,
                            str_replace(' ', '', $realm[$property]),
                            $assertMessage
                        );
                    }
                    foreach ($realm['fields'] as $field) {
                        foreach ([
                                     'name',
                                     'alias',
                                     'display',
                                     'documentation'
                                 ] as $string) {
                            $this->assertIsString(
                                $field[$string],
                                $assertMessage
                            );
                        }
                        $this->assertIsBool(
                            $field['anonymize'],
                            $assertMessage
                        );
                    }

                    $this->assertCount(
                        $validData[$realmName]['count'],
                        $realm['fields'],
                        $assertMessage
                    );
                }
            })
        );
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
        list($content, $info, $headers) = self::$helpers[$role]->post('warehouse/export/request', null, $params);
        $this->assertMatchesRegularExpression('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals($httpCode, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, $schema);
    }

    /**
     * @dataProvider provideCreateRequestParamValidation
     */
    public function testCreateRequestParamValidation(
        $id,
        $role,
        $input,
        $output
    ) {
        parent::requestAndValidateJson(self::$helpers[$role], $input, $output);
    }

    public function provideCreateRequestParamValidation()
    {
        $validInput = [
            'path' => 'warehouse/export/request',
            'method' => 'post',
            'params' => null,
            'data' => [
                'realm' => 'Jobs',
                'start_date' => '2017-01-01',
                'end_date' => '2017-01-01'
            ]
        ];
        // Run some standard endpoint tests.
        return parent::provideRestEndpointTests(
            $validInput,
            [
                'authentication' => true,
                'string_params' => ['realm', 'format'],
                'date_params' => ['start_date', 'end_date']
            ]
        );
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
        list($content, $info, $headers) = self::$helpers[$role]->get('warehouse/export/requests');
        $this->assertMatchesRegularExpression('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals($httpCode, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, $schema);

        // Only check data for successful requests.
        if ($httpCode == 200) {
            self::assertArraySubset($requests, $content['data']);
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
        list($content, $info, $headers) = self::$helpers[$role]->get('warehouse/export/download/' . $id);
        $this->assertMatchesRegularExpression('#\bapplication/zip\b#', $headers['Content-Type'], 'Content type header');
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
        list($beforeContent) = self::$helpers[$role]->get('warehouse/export/requests');
        $dataBefore = $beforeContent['data'];

        list($createContent) = self::$helpers[$role]->post('warehouse/export/request', null, $params);
        $id = $createContent['data'][0]['id'];

        list($content, $info, $headers) = self::$helpers[$role]->delete('warehouse/export/request/' . $id);
        $this->assertMatchesRegularExpression('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals($httpCode, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, $schema);
        $this->assertEquals($id, $content['data'][0]['id'], 'Deleted ID is in response');

        // Get list of requests after deletion
        list($afterContent) = self::$helpers[$role]->get('warehouse/export/requests');
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
        list($content, $info, $headers) = self::$helpers['pub']->delete('warehouse/export/request/1');
        $this->assertMatchesRegularExpression('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals(401, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, 'error');

        // Non-integer ID.
        list($content, $info, $headers) = self::$helpers['usr']->delete('warehouse/export/request/abc');
        $this->assertMatchesRegularExpression('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        list($content, $info, $headers) = self::$helpers['usr']->delete('rest/warehouse/export/request/abc');
        $this->assertMatchesRegularExpression('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals(404, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, 'error');

        // Trying to delete a non-existent request.
        list($row) = self::$dbh->query('SELECT MAX(id) + 1 AS id FROM batch_export_requests');
        list($content, $info, $headers) = self::$helpers['usr']->delete('warehouse/export/request/' . $row['id']);
        $this->assertMatchesRegularExpression('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals(404, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, 'error');

        // Trying to delete another user's request.
        list($row) = self::$dbh->query('SELECT id FROM batch_export_requests WHERE user_id = :user_id LIMIT 1', ['user_id' => self::$users['pi']->getUserId()]);
        list($content, $info, $headers) = self::$helpers['usr']->delete('warehouse/export/request/' . $row['id']);
        $this->assertMatchesRegularExpression('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
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
        list($beforeContent) = self::$helpers[$role]->get('warehouse/export/requests');

        // Gather ID values and also convert to integers for the array
        // comparison done below.
        $ids = [];
        foreach ($beforeContent['data'] as &$datum) {
            $datum['id'] = (int)$datum['id'];
            $ids[] = $datum['id'];
        }
        $data = ['ids' => $ids];
        // Delete all existing requests.
        list($content, $info, $headers) = self::$helpers[$role]->delete('warehouse/export/requests', $data);

        $this->assertMatchesRegularExpression('#\bapplication/json\b#', $headers['Content-Type'], 'Content type header');
        $this->assertEquals($httpCode, $info['http_code'], 'HTTP response code');
        $this->validateAgainstSchema($content, $schema);
        $this->assertTrue(count($content['data']) === count($beforeContent['data']), 'Deleted IDs are in response');

        // Get list of requests after deletion
        list($afterContent) = self::$helpers[$role]->get('warehouse/export/requests');
        $this->assertEquals([], $afterContent['data'], 'Data after deletion is empty.');
    }

    public function createRequestProvider()
    {
        return parent::getTestFiles()->loadJsonFile(self::TEST_GROUP, 'create-request', 'input');
    }

    public function getRequestsProvider()
    {
        return parent::getTestFiles()->loadJsonFile(self::TEST_GROUP, 'get-requests', 'input');
    }

    public function getRequestProvider()
    {
        return parent::getTestFiles()->loadJsonFile(self::TEST_GROUP, 'get-request', 'input');
    }

    public function deleteRequestProvider()
    {
        return parent::getTestFiles()->loadJsonFile(self::TEST_GROUP, 'delete-request', 'input');
    }

    public function deleteRequestsProvider()
    {
        return parent::getTestFiles()->loadJsonFile(self::TEST_GROUP, 'delete-requests', 'input');
    }
}

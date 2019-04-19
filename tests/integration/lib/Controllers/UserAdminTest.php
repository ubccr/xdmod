<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use TestHarness\TestFiles;

class UserAdminTest extends BaseUserAdminTest
{

    protected $testFiles;

    /**
     * @return TestFiles
     * @throws \Exception
     */
    public function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__ . '/../..');
        }
        return $this->testFiles;
    }

    /**
     * Tests all of the situations in which user creation can fail ( i.e. throw an exception ).
     *
     * @dataProvider provideCreateUserFails
     * @param array $params
     * @param array $expected
     * @throws \Exception
     */
    public function testCreateUserFails(array $params, array $expected)
    {
        $this->helper->authenticateDashboard('mgr');

        $response = $this->helper->post('controllers/user_admin.php', null, $params);
        $this->assertTrue(strpos($response[1]['content_type'], 'application/json') >= 0);
        $this->assertEquals(200, $response[1]['http_code']);

        $actual = $response[0];

        $this->assertEquals($expected, $actual);

        $this->helper->logoutDashboard();
    }

    /**
     * Data Provider function for testCreateUserFails
     *
     * @return array
     */
    public function provideCreateUserFails()
    {
        $params = array(
            'operation' => 'create_user',
            'account_request_id' => '',
            'first_name' => 'Ezekiah',
            'last_name' => 'Jones',
            'email_address' => 'ejones@test.com',
            'username' => 'ejones',
            'acls' => json_encode(
                array(
                    'usr' => array()
                )
            ),
            'assignment' => 29010,
            'institution' => -1,
            'user_type' => 1
        );

        $expected = array(
            "success" => false,
            "count" => 0,
            "total" => 0,
            "totalCount" => 0,
            "results" => [],
            "data" => [],
            "message" => ''
        );

        return array(
            // Username not provided
            array(
                $this->copyAndRemove($params, array('username')),
                $this->copyAndReplace($expected, array('message' => "'username' not specified."))
            ),
            // Username empty
            array(
                $this->copyAndReplace($params, array('username' => '')),
                $this->copyAndReplace($expected, array('message' => "Invalid value specified for 'username'."))
            ),
            // first_name not provided
            array(
                $this->copyAndRemove($params, array('first_name')),
                $this->copyAndReplace($expected, array('message' => "'first_name' not specified."))
            ),
            // first_name empty
            array(
                $this->copyAndReplace($params, array('first_name' => '')),
                $this->copyAndReplace($expected, array('message' => "Invalid value specified for 'first_name'."))
            ),
            // last_name not provided
            array(
                $this->copyAndRemove($params, array('last_name')),
                $this->copyAndReplace($expected, array('message' => "'last_name' not specified."))
            ),
            // last_name empty
            array(
                $this->copyAndReplace($params, array('last_name' => '')),
                $this->copyAndReplace($expected, array('message' => "Invalid value specified for 'last_name'."))
            ),
            // user_type not provided
            array(
                $this->copyAndRemove($params, array('user_type')),
                $this->copyAndReplace($expected, array('message' => "'user_type' not specified."))
            ),
            // user_type empty
            array(
                $this->copyAndReplace($params, array('user_type' => '')),
                $this->copyAndReplace($expected, array('message' => "Invalid value specified for 'user_type'."))
            ),
            // email_address not provided
            array(
                $this->copyAndRemove($params, array('email_address')),
                $this->copyAndReplace($expected, array('message' => "'email_address' not specified."))
            ),
            // email_address empty
            array(
                $this->copyAndReplace($params, array('email_address' => '')),
                $this->copyAndReplace($expected, array('message' => "Failed to assert 'email_address'."))
            ),
            // acls not provided
            array(
                $this->copyAndRemove($params, array('acls')),
                $this->copyAndReplace($expected, array('message' => "Acl information is required"))
            ),
            // acls empty
            array(
                $this->copyAndReplace($params, array('acls' => '')),
                $this->copyAndReplace($expected, array('message' => "Acl information is required"))
            ),
            // acls only contain 'flag' acls ( dev )
            array(
                $this->copyAndReplace($params, array('acls' => '{"dev": []}')),
                $this->copyAndReplace($expected, array('message' => 'Please include a non-feature acl ( i.e. User, PI etc. )'))
            ),
            // acls only contain 'flag' acls ( mgr )
            array(
                $this->copyAndReplace($params, array('acls' => '{"mgr": []}')),
                $this->copyAndReplace($expected, array('message' => 'Please include a non-feature acl ( i.e. User, PI etc. )'))
            ),
            // acls only contain 'flag' acls ( dev, mgr )
            array(
                $this->copyAndReplace($params, array('acls' => '{"dev": [], "mgr": []}')),
                $this->copyAndReplace($expected, array('message' => 'Please include a non-feature acl ( i.e. User, PI etc. )'))
            )
        );
    }

    /**
     * Test that ensures that we can create users with various setups
     * successfully. Those users who have been successfully created can be used
     * in dependent tests.
     *
     * @dataProvider provideCreateUsersSuccess
     * @group UserAdminTest.createUsers
     * @param array $user
     * @throws \Exception
     */
    public function testCreateUsersSuccess(array $user)
    {
        $userId = $this->createUser($user);

        // if we received a userId back then let's go ahead and update the
        // users password so that it can be used to login in future tests.
        if ($userId !== null) {
            $username = array_search($userId, self::$newUsers);
            $this->updateCurrentUser($userId, $username);
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideCreateUsersSuccess()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('user_admin', 'create_users', 'input')
        );
    }

    /**
     * @dataProvider provideThatExistingUsersCanBeRetrieved
     * @param array $user
     */
    public function testThatExistingUsersCanBeRetrieved(array $user)
    {
        $username = $user['username'];
        $userType = $user['user_type'];

        $userId = $this->retrieveUserId($username, $userType);
        $this->assertNotNull(
            $userId,
            "Unable to find User: $username in User Group: $userType"
        );

        // save this user for future use
        self::$existingUsers[$username] = $userId;
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideThatExistingUsersCanBeRetrieved()
    {
        return Json::loadFile(
            $this->getTestFiles()->getFile('user_admin', 'existing_users', 'input')
        );
    }

    /**
     * @dataProvider provideTestUsersQuickFilters
     * @depends      testCreateUsersSuccess
     * @group UserAdminTest.createUsers
     *
     * @param array $user
     */
    public function testUsersQuickFilters(array $user)
    {
        $this->assertArrayHasKey('username', $user);

        $username = $user['username'];

        $this->assertArrayHasKey('dimensionNames', $user);
        $this->assertArrayHasKey('filters', $user);

        $expectedDimensionNames = $user['dimensionNames'];
        $expectedFilters = $user['filters'];

        // Add person ID to person/PI filters where appropriate.
        if (isset($user['long_name'])) {
            $personId = $this->peopleHelper->getPersonIdByLongName($user['long_name']);
            foreach ($expectedFilters as $type => $filters) {
                if (in_array($type, array('person', 'pi'))) {
                    $expectedFilters[$type] = array_map(
                        function ($filter) use ($personId) {
                            $filter['valueId'] = $personId;
                            return $filter;
                        },
                        $filters
                    );
                }
            }
        }

        $this->helper->authenticateDirect($username, $username);

        $response = $this->helper->get('rest/v0.1/warehouse/quick_filters');
        $this->validateResponse($response);

        $this->assertArrayHasKey('results', $response[0]);
        $results = $response[0]['results'];

        $this->assertArrayHasKey('dimensionNames', $results);
        $this->assertArrayHasKey('filters', $results);

        $dimensionNames = $results['dimensionNames'];
        $filters = $results['filters'];

        $this->assertEquals($expectedDimensionNames, $dimensionNames);
        $this->assertEquals($expectedFilters, $filters);
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideTestUsersQuickFilters()
    {
        return Json::loadFile(
            $this->getTestFiles()->getFile('user_admin', 'user_quick_filters-update_enumAllAvailableRoles', 'output')
        );
    }

    /**
     * @depends      testCreateUsersSuccess
     * @dataProvider provideGetMenus
     * @group UserAdminTest.createUsers
     * @param array $user
     * @throws \Exception
     */
    public function testGetMenus(array $user)
    {
        $this->assertArrayHasKey('username', $user);
        $this->assertArrayHasKey('output', $user);

        $username = $user['username'];
        $output = $user['output'];

        if ($username !== self::PUBLIC_USER_NAME) {
            $this->helper->authenticateDirect($username, $username);
        }

        $data = array(
            'operation' => 'get_menus',
            'public_user' => $username === self::PUBLIC_USER_NAME ? 'true' : 'false',
            'query_group' => 'tg_usage',
            'node' => 'category_'
        );

        $response = $this->helper->post('controllers/user_interface.php', null, $data);

        $this->validateResponse($response);

        $actual = $response[0];
        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('user_admin', $output)
        );

        $this->assertEquals($expected, $actual, "[$username] Get Menus - Expected:\n\n" . json_encode($expected) . "\n\nReceived:\n\n" . json_encode($actual));

        if ($username !== self::PUBLIC_USER_NAME) {
            $this->helper->logout();
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideGetMenus()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('user_admin', 'get_menus-1', 'input')
        );
    }

    /**
     * @depends      testCreateUsersSuccess
     * @dataProvider provideGetTabs
     * @group UserAdminTest.createUsers
     * @param array $user
     * @throws \Exception
     */
    public function testGetTabs(array $user)
    {
        $this->assertArrayHasKey('username', $user);
        $this->assertArrayHasKey('output', $user);

        $username = $user['username'];

        $isPublicUser = $username === self::PUBLIC_USER_NAME;

        if (!$isPublicUser) {
            $this->helper->authenticateDirect($username, $username);
        }

        $data = array(
            'operation' => 'get_tabs',
            'public_user' => ($isPublicUser ? 'true' : 'false')
        );

        $response = $this->helper->post('controllers/user_interface.php', null, $data);
        $this->validateResponse($response);

        $actual = $response[0];
        $this->assertArrayHasKey('data', $actual);
        $this->assertArrayHasKey('success', $actual);
        $this->assertArrayHasKey('totalCount', $actual);
        $this->assertArrayHasKey('message', $actual);

        $expectedFileName = $user['output'];
        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('user_admin', $expectedFileName, 'output')
        );

        $this->assertEquals($expected, $actual);

        if (!$isPublicUser) {
            $this->helper->logout();
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideGetTabs()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('user_admin', 'get_tabs', 'input')
        );
    }

    /**
     * @depends      testCreateUsersSuccess
     * @dataProvider provideGetDwDescripters
     * @group UserAdminTest.createUsers
     * @param array $user
     * @throws \Exception
     */
    public function testGetDwDescripters(array $user)
    {
        $this->assertArrayHasKey('username', $user);
        $this->assertArrayHasKey('output', $user);

        $username = $user['username'];

        $isPublicUser = $username === self::PUBLIC_USER_NAME;

        if (!$isPublicUser) {
            $this->helper->authenticateDirect($username, $username);
        }

        $data = array(
            'operation' => 'get_dw_descripter',
            'public_user' => ($isPublicUser ? 'true' : 'false')
        );

        $response = $this->helper->post('controllers/metric_explorer.php', null, $data);
        $this->validateResponse($response);

        $actual = $response[0];
        $this->assertArrayHasKey('data', $actual);
        $this->assertArrayHasKey('totalCount', $actual);

        $expectedFileName = $user['output'];
        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('user_admin', $expectedFileName, 'output')
        );

        $this->assertEquals($expected, $actual, "[$username] Get Data Warehouse Descripters - Expected:\n\n" . json_encode($expected) . "\n\nReceived:\n\n" . json_encode($actual));

        if (!$isPublicUser) {
            $this->helper->logout();
        }

    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideGetDwDescripters()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('user_admin', 'get_dw_descripters-1', 'input')
        );
    }

    /**
     * @dataProvider provideGetUserVisits
     * @param array $options
     * @throws \Exception
     */
    public function testGetUserVisits(array $options)
    {
        $this->assertArrayHasKey('data', $options);
        $this->assertArrayHasKey('output', $options);
        $this->assertArrayHasKey('success', $options);
        $this->assertArrayHasKey('content_type', $options);

        $testData = $options['data'];
        $expectedOutput = $options['output'];
        $expectedContentType = $options['content_type'];
        $helper = $options['helper'];

        $data = array_merge(
            array(
                'operation' => 'enum_user_visits'
            ),
            $testData
        );

        $response = $helper->post("internal_dashboard/controllers/controller.php", null, $data);

        $this->validateResponse($response, 200, $expectedContentType);

        $actual = json_decode($response[0], true);

        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('user_admin', $expectedOutput, 'output')
        );


        $this->assertArrayHasKey('success', $actual);
        $this->assertArrayHasKey('success', $expected);

        $this->assertEquals($expected['success'], $actual['success']);

        // If we're expecting actual data back then...
        if (array_key_exists('stats', $expected)) {
            $actualStats = $actual['stats'];
            $expectedStats = $expected['stats'];

            $expectedDifferences = array('visit_frequency', 'timeframe');
            $actualDifferences = array();

            foreach ($expectedStats as $key => $expectedStat) {
                $this->entryExists(
                    $actualStats,
                    function ($key, $value) use ($expectedStat, $expectedDifferences, $actualDifferences) {
                        $diff = array_diff_assoc($expectedStat, $value);
                        $missingDiff = array_diff(array_keys($diff), $expectedDifferences);
                        if (count($missingDiff) === 0) {
                            return true;
                        }
                        $actualDifferences[] = $missingDiff;
                        return false;
                    }
                );
            }
            $this->assertTrue(
                empty($actualDifferences),
                sprintf(
                    "There were other differences besides the expected.\nExpected: %s\nActual: %s",
                    json_encode($expectedDifferences),
                    json_encode($actualDifferences)
                )
            );
        } elseif (array_key_exists('message', $expected)) {
            $this->assertArrayHasKey('message', $actual);
            $this->assertEquals($expected['message'], $actual['message']);
        } else {
            $this->assertTrue(false, "No idea how to evaluate the data for this test.");
        }

        if (isset($options['last'])) {
            $helper->logoutDashboard();
        }
    }



    /**
     * @depends testGetUserVisits
     *
     * @dataProvider provideGetUserVisits
     *
     * @param array $options
     * @throws \Exception
     */
    public function testGetUserVisitsExport(array $options)
    {
        $this->assertArrayHasKey('data', $options);
        $this->assertArrayHasKey('output', $options);
        $this->assertArrayHasKey('success', $options);

        $testData = $options['data'];
        $expectedOutput = $options['output'];
        $expectedSuccess = $options['success'];
        $helper = $options['helper'];

        $data = array_merge(
            array(
                'operation' => 'enum_user_visits_export'
            ),
            $testData
        );

        $response = $helper->post("internal_dashboard/controllers/controller.php", null, $data);
        $expectedContentType = $expectedSuccess ? 'application/xls' : 'text/html; charset=UTF-8';
        $this->validateResponse($response, 200, $expectedContentType);


        $actual = array();
        if (true === $expectedSuccess) {
            /* If we expected the request to succeed then the returned data should
             * be 'csv', process accordingly to ensure we're comparing apples to
             * apples.
             */
            $actualLines = explode("\n", $response[0]);
            for($i = 0; $i < count($actualLines); $i++) {
                // skip the first line as it's a header
                if ($i === 0) {
                    continue;
                }

                $row = str_getcsv($actualLines[$i]);

                // Make sure to skip empty lines
                if (!empty($row) && $row[0] !== null) {
                    $actual[] = $row;
                }
            }
        } else {
            // we expect the incoming data to be json formatted.
            $actualLines = json_decode($response[0], true);
            foreach($actualLines as $key => $value) {
                $actual[] = array($key, $value);
            }
        }

        $fileType = $expectedSuccess ? '.csv' : '.json';
        $expectedFileName = $this->getTestFiles()->getFile('user_admin', $expectedOutput, 'output', $fileType);

        $rows = 0;
        $length = 1;
        $expected = array();
        $ignoredColumns = array('visit_frequency' => null, 'timeframe' => null) ;

        if (true === $expectedSuccess) {
            /**
             * We need a bit of meta-data from the expected file. Read through the
             * expected file and retrieve:
             *   - the column index of any columns that are to be ignored
             *   - the number of rows we expect.
             *   - the largest number of columns seen for a line
             *   - the expected row itself to be saved for later comparison.
             */
            if (($handle = fopen($expectedFileName, 'r')) !== false) {
                while (($data = fgetcsv($handle))) {
                    if ($rows === 0) {
                        foreach($ignoredColumns as $key => $value) {
                            $index = array_search($key, $data);
                            $ignoredColumns[$key] = $index;
                        }
                    } else {
                        if (!empty($data) && $data[0] !== null) {
                            $expected[] = $data;
                        }
                    }
                    $num = count($data);
                    if ($num > $length) {
                        $length = $num;
                    }
                    $rows++;
                }
                fclose($handle);
            }

            $expectedRows = count($expected);
            $actualRows = count($actual);

            // check that the number of rows are the same
            $this->assertEquals(
                $expectedRows,
                $actualRows,
                sprintf(
                    "Expected # of Lines: [%d] [%s] Received: [%d] [%s]",
                    $expectedRows,
                    json_encode($expected),
                    $actualRows,
                    json_encode($actual)
                )
            );

            /**
             * Do to the structure of the returned data, finding out whether or
             * not a row that was returned was expected is a little convoluted.
             * - We first iterate over all of the returned rows
             * - Then, using the current returned row we search through the
             *   expected rows
             * - Each row is defined as an associative array.
             * - Rows match iff every key ( that is not an ignored column )
             *   and its associated value exactly match an expected rows
             *   keys / values
             */
            foreach($actual as $actualRow) {
                $exists = $this->entryExists(
                    $expected,
                    function ($key, $expectedRow) use ($actualRow, $ignoredColumns) {
                        $found = true;
                        foreach($actualRow as $actualKey => $actualValue) {
                            if (!in_array($actualKey, array_values($ignoredColumns))) {
                                if ($expectedRow[$actualKey] !== $actualValue) {
                                    $found = false;
                                    break;
                                }
                            }
                        }
                        return $found;
                    }
                );

                $this->assertTrue($exists, "Unable to find: " . json_encode($actualRow));
            }
        } else {
            // If this test isn't expected to succeed then it is assumed the output
            // is a json file. Process accordingly.
            $expectedJson = JSON::loadFile($expectedFileName);
            foreach($expectedJson as $key => $value) {
                $expected[] = array($key, $value);
            }

            $this->assertEquals($expected, $actual);
        }

        if (isset($options['last'])) {
            $helper->logoutDashboard();
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideGetUserVisits()
    {
        $data = JSON::loadFile(
            $this->getTestFiles()->getFile('user_admin', 'get_user_visits', 'input')
        );

        $helper = new \TestHarness\XdmodTestHelper();
        $helper->authenticateDashboard('mgr');

        foreach($data as &$datum) {
            $datum[0]['helper'] = $helper;
        }
        $data[count($data) - 1][0]['last'] = true;

        return $data;
    }

    /**
     * @depends testGetUserVisitsExport
     * @dataProvider provideGetUserVisitsIncrements
     *
     * @param array $options
     * @throws \Exception
     */
    public function testGetUserVisitsIncrements(array $options)
    {
        $user = $options['user'];
        $difference = $options['difference'];

        $before = $this->getUserVisits($options);

        $this->helper->authenticate($user);

        $this->helper->logout();

        $after = $this->getUserVisits($options);

        $matches = ($after === ($before + $difference));

        $this->assertTrue(
            $matches,
            sprintf(
                "Before: [%d][%s] After: [%d][%s] Expected Difference [%d][%s] Actual Difference [%d]\n %s === ( %s + %s )",
                $before,
                gettype($before),
                $after,
                gettype($after),
                $difference,
                gettype($difference),
                $after - $before,
                $after,
                $before,
                $difference
            )
        );
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideGetUserVisitsIncrements()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('user_admin', 'get_user_visits_increment', 'input')
        );
    }

    /**
     * Executes a request to the
     * 'internal_dashboard/controllers/controller.php?operation=enum_user_visits'
     * endpoint. Validates the response and returned data structure.
     *
     * @param array $options
     * @return null|int returns null if user is not found else it returns the
     *                  users visit_frequency.
     * @throws \Exception if there is a problem authenticating with the
     *                    dashboard.
     */
    protected function getUserVisits(array $options)
    {
        $username = $options['username'];
        $testData = $options['data'];

        $expectedData = $options['expected'];
        $expectedContentType = $expectedData['content_type'];

        $this->helper->authenticateDashboard('mgr');

        $data = array_merge(
            array(
                'operation' => 'enum_user_visits'
            ),
            $testData
        );

        $response = $this->helper->post("internal_dashboard/controllers/controller.php", null, $data);

        $this->validateResponse($response, 200, $expectedContentType);

        $actual = json_decode($response[0], true);

        $this->assertArrayHasKey('success', $actual);
        $this->assertArrayHasKey('stats', $actual);

        $results = null;
        $userStats = $actual['stats'];
        foreach($userStats as $userStat) {
            $this->assertArrayHasKey('username', $userStat);
            $this->assertArrayHasKey('visit_frequency', $userStat);

            if ($userStat['username'] === $username) {
                $results = $userStat['visit_frequency'];
                break;
            }
        }

        $this->helper->logoutDashboard();

        return (int)$results;
    }

    /**
     * Attempt to determine if an entry exists in the provided $source based on
     * the return value of $predicate. If $predicate returns false for all
     * entries in $source then the function will return false.
     *
     * @param array $source       the array to be searched
     * @param callable $predicate If this method returns true then a match has
     *                            been found.
     * @return bool true if the entry exists, else false.
     */
    protected function entryExists(array $source, callable $predicate)
    {
        foreach ($source as $key => $value) {
            if ($predicate($key, $value) === true) {
                return true;
            }
        }
        return false;
    }
}

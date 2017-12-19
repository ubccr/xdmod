<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use TestHarness\TestFiles;

class UserAdminTest extends BaseUserAdminTest
{

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
                $this->copyAndReplace($expected, array('message' => 'Select another acl other than "Manager" or "Developer"'))
            ),
            // acls only contain 'flag' acls ( mgr )
            array(
                $this->copyAndReplace($params, array('acls' => '{"mgr": []}')),
                $this->copyAndReplace($expected, array('message' => 'Select another acl other than "Manager" or "Developer"'))
            ),
            // acls only contain 'flag' acls ( dev, mgr )
            array(
                $this->copyAndReplace($params, array('acls' => '{"dev": [], "mgr": []}')),
                $this->copyAndReplace($expected, array('message' => 'Select another acl other than "Manager" or "Developer"'))
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
     */
    public function testCreateUsersSuccess(array $user)
    {
        $userId = $this->createUser($user);

        // if we received a userId back then let's go ahead and update the
        // users password so that it can be used to login in future tests.
        if ($userId !== null) {
            $username = array_search($userId, self::$newUsers);
            $this->updateUser($userId, $username);
        }
    }

    public function provideCreateUsersSuccess()
    {
        return JSON::loadFile(
            TestFiles::getFile('user_admin', 'create_users', 'input')
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

    public function provideThatExistingUsersCanBeRetrieved()
    {
        return Json::loadFile(
            TestFiles::getFile('user_admin', 'existing_users', 'input')
        );
    }

    /**
     * @dataProvider provideTestUsersQuickFilters
     * @group UserAdminTest.createUsers
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

    public function provideTestUsersQuickFilters()
    {
        return Json::loadFile(
            TestFiles::getFile('user_admin', 'user_quick_filters-update_enumAllAvailableRoles', 'output')
        );
    }

    /**
     * @depends testCreateUsersSuccess
     * @dataProvider provideGetMenus
     * @group UserAdminTest.createUsers
     * @param array $user
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
            TestFiles::getFile('user_admin', $output)
        );

        $this->assertEquals($expected, $actual, "[$username] Get Menus - Expected [". json_encode($expected) . "] Received [" . json_encode($actual) . "]");

        if ($username !== self::PUBLIC_USER_NAME) {
            $this->helper->logout();
        }
    }

    public function provideGetMenus()
    {
        return JSON::loadFile(
            TestFiles::getFile('user_admin', 'get_menus', 'input')
        );
    }
}

<?php

namespace IntegrationTests\Controllers;

use TestHarness\XdmodTestHelper;
use XDUser;

class UserAdminTest extends \PHPUnit_Framework_TestCase
{

    const MIN_USERS = 1;
    const MAX_USERS = 1000;
    const DEFAULT_TEST_USER_NAME = "test";
    const DEFAULT_EMAIL_ADDRESS_SUFFIX = "@test.com";

    const CENTER_TACC = 476;
    const CENTER_SDSC = 856;

    /**
     * @var XdmodTestHelper
     */
    protected $helper;

    /**
     * Used to keep track of the users created during the process of testing so that they can be removed in the end.
     *
     * @var array
     */
    protected static $users = array();

    protected function setUp()
    {
        $this->helper = new XdmodTestHelper();
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$users as $username => $user) {
            $user->removeUser();
        }
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
     * Test that a user account can be created successfully.
     *
     * @throws \Exception
     */
    public function testCreateCenterDirectorSingleCenterUserSuccess()
    {
        $acls = array('cd' => array(self::CENTER_TACC));
        $this->createUser('test.cd.one_center', $acls);
    }

    /**
     * @param string $username
     * @param array $acls
     * @throws \Exception
     */
    private function createUser($username, array $acls)

    {
        $this->helper->authenticateDashboard('mgr');
        $institution = array_key_exists('cc', $acls) ? 476 : -1;
        $data = array(
            'operation' => 'create_user',
            'account_request_id' => '',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email_address' => $username . self::DEFAULT_EMAIL_ADDRESS_SUFFIX,
            'username' => $username,
            'acls' => json_encode(
                $acls
            ),
            'assignment' => 29010,
            'institution' => $institution,
            'user_type' => 1
        );
        $response = $this->helper->post('controllers/user_admin.php', null, $data);

        $this->assertTrue(strpos($response[1]['content_type'], 'application/json') >= 0);
        $this->assertEquals(200, $response[1]['http_code']);

        $responseData = $response[0];
        $message = $responseData['message'];

        $this->assertNotFalse(strpos($message, $username));
        $this->assertNotFalse(strpos($message, 'successfully'));

        $user = XDUser::getUserByUserName($username);
        $this->assertNotNull($user);

        // set the users password to the username for easy testing
        $user->setPassword(($username));
        $user->saveUser();

        // save the user for later use / removal
        self::$users[$username] = $user;
        $this->helper->logoutDashboard();
    }


    private function copyAndReplace(array $data, array $replace)
    {
        $results = $data;
        foreach ($replace as $key => $value) {
            if (array_key_exists($key, $results)) {
                $results[$key] = $value;
            }
        }
        return $results;
    }


    private function copyAndRemove(array $data, array $remove)
    {
        $results = $data;
        foreach ($remove as $key) {
            if (array_key_exists($key, $results)) {
                unset($results[$key]);
            }
        }
        return $results;

    }

    private static function getUserName($username)
    {
        while (array_key_exists($username, self::$users)) {
            $suffix = rand(self::MIN_USERS, self::MAX_USERS);
            $username = "$username$suffix";
        }
        return $username;
    }
}

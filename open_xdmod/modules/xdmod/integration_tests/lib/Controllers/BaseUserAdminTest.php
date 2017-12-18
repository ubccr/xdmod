<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use TestHarness\TestFiles;
use TestHarness\XdmodTestHelper;

abstract class BaseUserAdminTest extends \PHPUnit_Framework_TestCase
{

    const MIN_USERS = 1;
    const MAX_USERS = 1000;
    const DEFAULT_TEST_USER_NAME = "test";
    const DEFAULT_EMAIL_ADDRESS_SUFFIX = "@test.com";

    /**
     * @var int The id of the default user type, 'Testing'
     */
    const DEFAULT_USER_TYPE = 3;

    const CENTER_TACC = 476;
    const CENTER_SDSC = 856;
    const CENTER_PSC = 848;

    /**
     * @var XdmodTestHelper
     */
    protected $helper;

    /**
     * Used to keep track of the users created during the process of testing so that they can be removed in the end.
     *
     * @var array
     */
    protected static $newUsers = array();

    /**
     * Used to keep track of the users that already exist in the database.
     *
     * @var array
     */
    protected static $existingUsers = array();

    protected function setUp()
    {
        $this->helper = new XdmodTestHelper();
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$newUsers as $username => $userId) {
            self::removeUser($userId, $username);
        }
    }

    /**
     * Attempt to remove the user identified by $userId and $username.
     * This is used at the tearDownAfterClass step so that any dynamically created
     * users are removed from the system.
     *
     * @param int    $userId   of the user that is to be removed.
     * @param string $username of the user that is to be removed.
     * @throws Exception if the remove user request was not successful. Or if
     *                   the user's username could not be found in the response
     *                   message.
     */
    protected static function removeUser($userId, $username)
    {
        $helper = new XdmodTestHelper();

        $helper->authenticateDashboard('mgr');
        $data = array(
            'operation' => 'delete_user',
            'uid' => $userId
        );

        $response = $helper->post('controllers/user_admin.php', null, $data);

        $expectedHttpCode = 200;
        $expectedContentType = 'application/json';

        $actualContentType = $response[1]['content_type'];
        $actualHttpCode = $response[1]['http_code'];
        if (strpos($actualContentType, $expectedContentType) === false) {
            throw new Exception("Expected content-type: $expectedContentType. Received: $actualContentType");
        }
        if ($expectedHttpCode !== $actualHttpCode) {
            throw new Exception("Expected http code: $expectedHttpCode. Received: $actualHttpCode");
        }

        $actualSuccess = $response[0]['success'];
        $actualMessage = $response[0]['message'];
        if ($actualSuccess !== true) {
            throw new Exception("Remove User ['success'] Expected: false, Received: $actualSuccess");
        }
        if (strpos($actualMessage, $username) === false) {
            throw new Exception("Remove User ['message'] did not contain username: $username. Received: " . $actualMessage);
        }

        $helper->logoutDashboard();
    }

    /**
     * Attempt to create a user via issuing a user_admin/create_user request and
     * supplying the provided array of $options as form parameters.
     *
     * @param array $options the array of options that will be used to submit
     *                       a 'create_user' request.
     * @return int the user_id for the newly created user
     * @throws \Exception if there is a problem authenticating with the
     *                    dashboard
     **/
    protected function createUser(array $options)
    {
        $this->helper->authenticateDashboard('mgr');

        // retrieve required arguments
        $username = isset($options['username']) ? $options['username'] : null;
        $acls = isset($options['acls']) ? $options['acls'] : null;

        $this->assertNotNull(
            $username,
            "Invalid creation data. Must supply a 'username' property. Received: " . json_encode($options)
        );
        $this->assertNotNull(
            $acls,
            "Invalid creation data. Must supply an 'acls' property'. Received: " . json_encode($options)
        );

        // retrieve optional arguments
        $person = isset($options['person']) ? $options['person'] : -1;
        $institution = isset($options['institution']) ? $options['institution'] : -1;
        $firstName = isset($options['first_name']) ? $options['first_name'] : 'Test';
        $lastName = isset($options['last_name']) ? $options['last_name'] : 'User';
        $emailAddress = isset($option['email_address']) ? $options['email_address'] : $username . self::DEFAULT_EMAIL_ADDRESS_SUFFIX;
        $userType = isset($options['user_type']) ? $options['user_type'] : self::DEFAULT_USER_TYPE;
        $output = isset($options['output']) ? $options['output'] : 'test.create.user';
        $expectedSuccess = isset($options['expected_success']) ? $options['expected_success'] : true;

        // construct form params for post request to create new user.
        $data = array(
            'operation' => 'create_user',
            'account_request_id' => '',
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email_address' => $emailAddress,
            'username' => $username,
            'acls' => json_encode(
                $acls
            ),
            'assignment' => $person,
            'institution' => $institution,
            'user_type' => $userType
        );

        $response = $this->helper->post('controllers/user_admin.php', null, $data);

        $this->validateResponse($response);

        // retrieve the expected results of submitting the 'create_user' request
        // with the supplied arguments.
        $expected = JSON::loadFile(
            TestFiles::getFile(
                'user_admin',
                $output
            )
        );

        $actual = $response[0];

        // need to replace any references to '$emailAddress' w/ the value of
        // $emailAddress.
        $actual['message'] = strtr($actual['message'], array('$emailAddress' => $emailAddress));

        $this->assertEquals($expected, $actual);
        $userId = null;
        if ($expectedSuccess === true) {
            $userId = $this->retrieveUserId($username);
            self::$newUsers[$username] = $userId;
        }

        // make sure to logout of the current 'mgr' session.
        $this->helper->logoutDashboard();

        return $userId;
    }

    protected function updateUser($userId, $password = null, $firstName = null, $lastName = null, $emailAddress = null)
    {
        $helper = new XdmodTestHelper();
        $helper->authenticateDashboard('mgr');

        $loginAsParams = array(
            'uid' => $userId
        );

        // perform the pseudo-login
        $helper->get('internal_dashboard/controllers/pseudo_login.php', $loginAsParams);

        // build the update user params
        $updateUserData = array();

        if (isset($password)) {
            $updateUserData['password'] = $password;
        }

        if (isset($firstName)) {
            $updateUserData['first_name'] = $firstName;
        }

        if (isset($lastName)) {
            $updateUserData['last_name'] = $lastName;
        }

        if (isset($emailAddress)) {
            $updateUserData['email_address'] = $emailAddress;
        }

        $updateUserResponse = $helper->patch(
            'rest/v0.1/users/current',
            null,
            $updateUserData
        );

        $expected = JSON::loadFile(
            TestFiles::getFile('user_admin', 'test.update_user')
        );

        $this->validateResponse($updateUserResponse);

        $this->assertEquals(
            $expected,
            $updateUserResponse[0],
            "Unable to validate update user response. Expected: " . json_encode($expected). " Received: " . json_encode($updateUserResponse[0])
        );

        $helper->logout();
    }

    /**
     * Attempts to lookup a user_id for the user identified by the provided
     * $userName and $userGroup. This will either return the id or fail the
     * test.
     *
     * @param $userName
     * @param int $userGroup
     * @return int the user id for the provided user name / user group.
     */
    protected function retrieveUserId($userName, $userGroup = 3)
    {
        $this->helper->authenticateDashboard('mgr');

        $listUsersResponse = $this->helper->post(
            'controllers/user_admin.php',
            null,
            array(
                'operation' => 'list_users',
                'group' => $userGroup
            )
        );

        $this->validateResponse($listUsersResponse);

        $userId = null;
        $users = $listUsersResponse[0]['users'];
        foreach ($users as $idx => $user) {
            if (isset($user['username']) && $user['username'] === $userName) {
                $userId = $user['id'];
                break;
            }
        }

        $this->assertNotNull($userId, "Unable to find user: $userName in user group: $userGroup");

        $this->helper->logoutDashboard();

        return $userId;
    }

    /**
     * Helper function that takes care of doing a baseline validation of a
     * response. In particular, it asserts that the http-code and content-type
     * match the provided arguments.
     *
     * @param mixed $response             to be validated.
     * @param int $expectedHttpCode       the http-code that the response is
     *                                    expected to have.
     * @param string $expectedContentType the content-type that the response is
     *                                    expected to have.
     */
    protected function validateResponse($response, $expectedHttpCode = 200, $expectedContentType = 'application/json')
    {
        $actualContentType = $response[1]['content_type'];
        $actualHttpCode = $response[1]['http_code'];
        $this->assertTrue(
            strpos($actualContentType, $expectedContentType) >= 0,
            "Expected content-type: $expectedContentType. Received: $actualContentType"
        );
        $this->assertEquals(
            $expectedHttpCode,
            $actualHttpCode,
            "Expected http code: $expectedHttpCode. Received: $actualHttpCode"
        );
    }


    protected function copyAndReplace(array $data, array $replace)
    {
        $results = $data;
        foreach ($replace as $key => $value) {
            if (array_key_exists($key, $results)) {
                $results[$key] = $value;
            }
        }
        return $results;
    }


    protected function copyAndRemove(array $data, array $remove)
    {
        $results = $data;
        foreach ($remove as $key) {
            if (array_key_exists($key, $results)) {
                unset($results[$key]);
            }
        }
        return $results;

    }
}

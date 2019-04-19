<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use Exception;
use TestHarness\TestFiles;
use TestHarness\XdmodTestHelper;
use TestHarness\PeopleHelper;

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

    const PUBLIC_USER_NAME = 'Public User';

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

    /**
     * @var TestFiles
     */
    protected $testFiles;

    /**
     * @var PeopleHelper
     */
    protected $peopleHelper;

    protected function setUp()
    {
        $this->helper = new XdmodTestHelper();
        $this->testFiles = new TestFiles(__DIR__ . '/../../../');
        $this->peopleHelper = new PeopleHelper();
    }

    public function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__ . '/../../../');
        }
        return $this->testFiles;
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$newUsers as $username => $userId) {
            try {
                self::removeUser($userId, $username);
            } catch (Exception $e) {
                echo sprintf(
                    "Exception removing user [%d, %s] during teardown: [%d] %s: \n%s\n",
                    $userId,
                    $username,
                    $e->getCode(),
                    $e->getMessage(),
                    $e->getTraceAsString()
                );
            }
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

        $actualSuccess = (bool) $response[0]['success'];
        $actualMessage = $response[0]['message'];

        $actualSuccessMessage = $actualMessage ? 'true' : 'false';

        // Begin determining if the response was as expected.
        if (strpos($actualMessage, 'user_does_not_exist') === false) {
            // If the user does exist...

            // then we expect the 'success' property to be true. If not,
            // throw an exception.
            if ($actualSuccess !== true) {
                throw new Exception(
                    sprintf(
                        "Remove User ['success'] Expected: true, Received: %s | %s",
                        $actualSuccessMessage,
                        $actualMessage
                    )
                );
            }

            // then we expect that the users name will be in the returned
            // message. If it's not then throw an exception.
            if (strpos($actualMessage, $username) === false) {
                throw new Exception(
                    sprintf(
                        "Remove User ['message'] did not contain username: %s. Received: %s",
                        $username,
                        $actualMessage
                    )
                );
            }
        } else {
            // If the user is not found

            // And the 'success' property is not false ( as expected ) then throw
            // an exception.
            if ($actualSuccess !== false) {
                throw new Exception(
                    sprintf(
                        "Remove User ['success'] Expected: false, Received: %s | %s",
                        $actualSuccessMessage,
                        $actualMessage
                    )
                );
            }
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
        $person = -1;
        if (isset($options['long_name'])) {
            $person = $this->peopleHelper->getPersonIdByLongName($options['long_name']);
        }
        $institution = isset($options['institution']) ? $options['institution'] : -1;
        $firstName = isset($options['first_name']) ? $options['first_name'] : 'Test';
        $lastName = isset($options['last_name']) ? $options['last_name'] : 'User';
        $emailAddress = isset($options['email_address']) ? $options['email_address'] : $username . self::DEFAULT_EMAIL_ADDRESS_SUFFIX;
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
            $this->getTestFiles()->getFile(
                'user_admin',
                $output
            )
        );

        $actual = $response[0];

        // substitutions that will be used in the event that the actual and
        // expected have keys in common.
        $substitutions = array(
            '$emailAddress' => $emailAddress,
            '$username' => $username,
            '$userType' => $userType,
            '$firstName' => $firstName,
            '$lastName' => $lastName,
            '$assignment' => $person,
            '$institution' => $institution
        );

        // retrieve the keys that the actual / expected have in common.
        $similar = array_intersect(array_keys($actual), array_keys($expected));

        // make sure that we perform substitutions on those keys they have in
        // common ( and whose values are string ).
        foreach ($similar as $key) {
            $expectedValue = $expected[$key];
            if (is_string($expectedValue)) {
                $expected[$key] = strtr($expected[$key], $substitutions);
            }
        }

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

    protected function updateCurrentUser($userId, $password = null, $firstName = null, $lastName = null, $emailAddress = null)
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
            $this->getTestFiles()->getFile('user_admin', 'test.update_user')
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
     * Update the user identified by $userId w/ the values supplied for the rest of the function
     * arguments. Note that this utilizes the user_admin/update_user operation to do the updating
     * as opposed to the `updateCurrentUser` function that utilizes the `users/current` rest path.
     *
     * @param int    $userId
     * @param string $emailAddress
     * @param array  $acls
     * @param int    $assignedPerson
     * @param int    $institution
     * @param int    $user_type
     * @throws Exception
     */
    protected function updateUser($userId, $emailAddress, $acls, $assignedPerson, $institution, $user_type, $sticky = false)
    {
        $data = array(
            'operation' => 'update_user',
            'uid' => $userId,
            'email_address' => $emailAddress,
            'acls' => json_encode(
                $acls
            ),
            'assigned_user' => $assignedPerson,
            'institution' => $institution,
            'user_type' => $user_type,
            'sticky' => $sticky
        );
        $this->helper->authenticateDashboard('mgr');

        $response = $this->helper->post('controllers/user_admin.php', null, $data);

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);


        $data = $response[0];

        $this->assertArrayHasKey('success', $data, "Expected the returned data structure to contain a 'success' property.");
        $this->assertArrayHasKey('status', $data, "Expected the returned data structure to contain a 'status' property.");
        $this->assertArrayHasKey('username', $data, "Expected the returned data structure to contain a 'username' property.");
        $this->assertArrayHasKey('user_type', $data, "Expected the returned data structure to contain a 'user_type' property.");

        $this->assertTrue($data['success'], "Expected the 'success' property to be: true Received: " . $data['success']);

        $this->helper->logoutDashboard();
    }

    /**
     * Attempts to lookup a user_id for the user identified by the provided
     * $userName and $userGroup. This will either return the id or fail the
     * test.
     *
     * @param $userName
     * @param int $userGroup
     * @return int the user id for the provided user name / user group.
     * @throws Exception
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
     * @param string $userId    the `id` of the user whose properties we are retrieving.
     * @param array $properties the set of properties that we want to retrieve from the user.
     * @return mixed|array      An empty array if none of the requested properties are found. If
     *                          only one property is requested / found then return the properties
     *                          value. Else, return the properties that were found in an associative
     *                          array.
     * @throws Exception
     */
    protected function retrieveUserProperties($userId, array $properties)
    {
        $this->helper->authenticateDashboard('mgr');

        $response = $this->helper->post(
            'controllers/user_admin.php',
            null,
            array(
                'operation' => 'get_user_details',
                'uid' => $userId
            )
        );

        $this->validateResponse($response);

        $user = $response[0]['user_information'];
        $keys = array_intersect($properties, array_keys($user));
        $results = array_intersect_key($user, array_flip($keys));

        $this->helper->logoutDashboard();

        return count($results) === 1  && count($properties) === 1 ? array_pop($results) : $results;
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
            strpos($actualContentType, $expectedContentType) !== false,
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

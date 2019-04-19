<?php namespace IntegrationTests\Controllers;

use CCR\Json;
use TestHarness\TestFiles;
use TestHarness\TestParameterHelper;
use TestHarness\XdmodTestHelper;

class ControllerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var XdmodTestHelper
     */
    protected $helper;

    /**
     * @var TestFiles
     */
    protected $testFiles;


    protected function getTestFiles()
    {
        if (!isset($this->testFiles)) {
            $this->testFiles = new TestFiles(__DIR__. '/../../');
        }
        return $this->testFiles;
    }

    protected function setUp()
    {
        $this->helper = new XdmodTestHelper(__DIR__ . '/../../');
    }

    public function testEnumExistingUsers()
    {
        $this->helper->authenticateDashboard('mgr');

        $params = array(
            'operation' => 'enum_existing_users',
            'group_filter' => 'all',
            'role_filter' => 'any'
        );

        $response = $this->helper->get('internal_dashboard/controllers/controller.php', $params, null);

        $this->assertTrue(strpos($response[1]['content_type'], 'text/html') >= 0);
        $this->assertEquals(200, $response[1]['http_code']);

        $actual = json_decode($response[0], true);

        $this->assertArrayHasKey('success', $actual);
        $this->assertArrayHasKey('count', $actual);
        $this->assertArrayHasKey('response', $actual);

        $this->assertEquals(true, $actual['success']);
        $this->assertTrue(
            $actual['count'] > 0,
            "Expected the integer property 'count' to have a value greater than 0."
        );
        $this->assertTrue(
            count($actual['response']) > 0,
            "Expected the array property 'response' to have 1 or more items, found 0"
        );

        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('controllers', 'enum_existing_users')
        );

        $this->assertEquals($expected['success'], $actual['success']);
        $this->assertEquals($expected['count'], $actual['count']);

        $expectedUsers = $expected['response'];
        $actualUsers = $actual['response'];

        $allFound = true;
        foreach ($expectedUsers as $key => $expectedUser) {
            $entryExists = $this->entryExists(
                $actualUsers,
                function ($key, $value) use ($expectedUser) {
                    if ($value['username'] === $expectedUser['username']) {
                        $success = true;
                        $diff = array_diff_assoc($expectedUser, $value);
                        array_walk_recursive(
                            $diff,
                            function ($value, $index, $properties) use (&$success) {
                                if (!in_array($index, $properties)) {
                                    $success = false;
                                }
                            },
                            array('last_logged_in', 'id', 'email_address')
                        );
                        return $success;
                    }
                    return false;
                }
            );
            if (!$entryExists) {
                $allFound = false;
                break;
            }
        }

        $this->assertTrue($allFound, "There were other differences besides the expected 'last_logged_in' | " . json_encode($actualUsers));

        $this->helper->logoutDashboard();
    }

    public function testEnumUserTypes()
    {
        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('controllers', 'enum_user_types-8.0.0')
        );

        $this->helper->authenticateDashboard('mgr');

        $data = array(
            'operation' => 'enum_user_types'
        );

        $response = $this->helper->post('controllers/user_admin.php', null, $data);

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals(200, $response[1]['http_code']);

        $actual = $response[0];

        $this->assertArrayHasKey('success', $actual, "Expected the returned data structure to have a 'success' property.");
        $this->assertArrayHasKey('status', $actual, "Expected the returned data structure to have a 'status' property.");
        $this->assertArrayHasKey('user_types', $actual, "Expected the returned data structure to have a 'user_types' property.");

        $this->assertEquals(true, $actual['success'], "Expected the 'success' property to be true, found: " . $actual['success']);
        $this->assertEquals('success', $actual['status'], "Expected the 'status' property to be 'success', found: " . $actual['status']);
        $this->assertTrue(count($actual['user_types']) > 0, "Expected there to be more than 0 'user_types' returned.");

        $this->assertEquals($expected, $actual, "Expected the actual results to match the expected results");

        $this->helper->logoutDashboard();
    }

    public function testEnumRoles()
    {
        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('controllers', 'enum_roles-add_default_center')
        );

        $this->helper->authenticateDashboard('mgr');

        $data = array(
            'operation' => 'enum_roles'
        );

        $response = $this->helper->post('controllers/user_admin.php', null, $data);

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals(200, $response[1]['http_code']);

        $data = $response[0];

        $this->assertArrayHasKey('success', $data, "Expected the returned data structure to contain a 'success' property");
        $this->assertArrayHasKey('status', $data, "Expected the returned data structure to contain a 'status' property");
        $this->assertArrayHasKey('acls', $data, "Expected the returned data structure to contain a 'roles' property");

        $this->assertTrue(count($data['acls']) > 0, "Expected the 'acls' property to have 1 or more values.");

        $expectedAcls = $expected['acls'];
        $actualAcls = $data['acls'];

        $allFound = true;
        foreach ($expectedAcls as $key => $expectedAcl) {
            $entryExists = $this->entryExists(
                $actualAcls,
                function ($key, $value) use ($expectedAcl) {
                    if ($value['acl_id'] === $expectedAcl['acl_id']) {
                        $success = true;
                        $diff = array_diff_assoc($expectedAcl, $value);
                        array_walk_recursive(
                            $diff,
                            function ($value, $index, $properties) use (&$success) {
                                if (!in_array($index, $properties)) {
                                    $success = false;
                                }
                            },
                            array('acl_id')
                        );
                        return $success;
                    }
                    return false;
                }
            );
            if (!$entryExists) {
                $allFound = false;
                break;
            }
        }

        $this->assertTrue($allFound, "There were other differences besides the expected 'last_logged_in'");

        $this->helper->logoutDashboard();

    }

    /**
     * @dataProvider listUsersGroupProvider
     * @internal param array $options
     */
    public function testListUsers(array $options)
    {
        $this->assertArrayHasKey('user_group', $options);
        $this->assertArrayHasKey('output', $options);

        $group = $options['user_group'];
        $outputFile = $options['output'];

        $this->helper->authenticateDashboard('mgr');

        $data = array(
            'operation' => 'list_users',
            'group' => $group
        );

        $response = $this->helper->post('controllers/user_admin.php', null, $data);

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals(200, $response[1]['http_code']);

        $data = $response[0];
        $expected = Json::loadFile(
            $this->getTestFiles()->getFile('controllers', $outputFile)
        );
        // Retrieve the users value and ensure that it is sorted in the correct order.
        $actualUsers = $data['users'];
        $expectedUsers = $expected['users'];

        $allFound = true;
        foreach ($expectedUsers as $key => $expectedUser) {
            $entryExists = $this->entryExists(
                $actualUsers,
                function ($key, $value) use ($expectedUser) {
                    if ($value['username'] === $expectedUser['username']) {
                        $success = true;
                        $diff = array_diff_assoc($expectedUser, $value);
                        array_walk_recursive(
                            $diff,
                            function ($value, $index, $properties) use (&$success) {
                                if (!in_array($index, $properties)) {
                                    $success = false;
                                }
                            },
                            array('last_logged_in', 'id', 'email_address')
                        );
                        return $success;
                    }
                    return false;
                }
            );
            if (!$entryExists) {
                $allFound = false;
                break;
            }
        }

        $this->assertTrue($allFound, "There were other differences besides the expected 'last_logged_in'");

        $this->helper->logoutDashboard();
    }

    public function listUsersGroupProvider()
    {
        return Json::loadFile(
            $this->getTestFiles()->getFile('controllers', 'list_users', 'input')
        );
    }

    public function testEnumUserTypesAndRoles()
    {
        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('controllers', 'enum_user_types_and_roles-update_enum_user_types_and_roles')
        );

        $this->helper->authenticateDashboard('mgr');

        $data = array(
            'operation' => 'enum_user_types_and_roles'
        );

        $response = $this->helper->post('internal_dashboard/controllers/controller.php', null, $data);

        $this->assertTrue(strpos($response[1]['content_type'], 'text/html') >= 0);
        $this->assertEquals(200, $response[1]['http_code']);

        $actual = json_decode($response[0], true);

        $this->assertArrayHasKey('success', $actual);
        $this->assertArrayHasKey('user_types', $actual);
        $this->assertArrayHasKey('user_roles', $actual);

        $this->assertTrue($actual['success'], "Expected the 'success' property to be true.");
        $this->assertTrue(count($actual['user_types']) > 0, "Expected there to be 1 or more user_types");
        $this->assertTrue(count($actual['user_roles']) > 0, "Expected there to be 1 or more user_roles");

        $this->assertEquals($expected, $actual, "Expected the actual results to equal the expected.");

        $this->helper->logoutDashboard();
    }

    public function testSabUserEnumTgUsers()
    {
        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('controllers', 'sab_user_enum_tg_users')
        );

        $this->helper->authenticateDashboard('mgr');

        $data = array(
            'start' => 0,
            'limit' => 300,
            'operation' => 'enum_tg_users',
            'pi_only' => 'n',
            'search_mode' => 'formal_name',
            'userManagement' => 'y',
            'dashboard_mode' => 1,
            'query' => ''
        );

        $response = $this->helper->post('controllers/sab_user.php', null, $data);

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals(200, $response[1]['http_code']);

        $data = $response[0];

        $this->assertArrayHasKey('success', $data, "Expected the returned data structure to contain a 'success' property.");
        $this->assertArrayHasKey('status', $data, "Expected the returned data structure to contain a 'status' property.");
        $this->assertArrayHasKey('message', $data, "Expected the returned data structure to contain a 'message' property.");
        $this->assertArrayHasKey('total_user_count', $data, "Expected the returned data structure to contain a 'total_user_count' property.");
        $this->assertArrayHasKey('users', $data, "Expected the returned data structure to contain a 'users' property");

        $this->assertEquals(true, $data['success'], "Expected the 'success' property to be true. Received: " . $data['success']);
        $this->assertEquals('success', $data['status'], "Expected the 'status' property to equal 'success'. Received: " . $data['status']);
        $this->assertEquals('success', $data['message'], "Expected the 'message' property to equal 'success'. Received: " . $data['message']);
        $this->assertCount(300, $data['users'], "Expected 300 users to be returned. Received: " . count($data['users']));

        $expectedUsers = $expected['users'];
        $actualUsers = $data['users'];

        $notFound = array();
        foreach($expectedUsers as $key => $expectedUser) {
            $found = array_filter(
                $actualUsers,
                function ($value) use ($expectedUser) {
                    return $expectedUser['person_name'] === $value['person_name'];
                }
            );
            if (empty($found)) {
                $notFound []= $expectedUser;
            }
        }
        $this->assertEmpty($notFound, "There were expected users missing in actual (person_id is not actually checked and may be different).\nExpected: " . json_encode($notFound) . "\nActual: " . json_encode($actualUsers));
        $this->helper->logoutDashboard();
    }

    public function testCreateUser()
    {
        $this->helper->authenticateDashboard('mgr');

        $data = array(
            'operation' => 'create_user',
            'account_request_id' => '',
            'first_name' => 'bob',
            'last_name' => 'smith',
            'email_address' => 'bsmith@test.com',
            'username' => 'bsmith',
            'acls' => json_encode(array('usr' => array())),
            'assignment' => 283,
            'institution' => -1,
            'user_type' => 1
        );

        $response = $this->helper->post('controllers/user_admin.php', null, $data);

        $this->assertTrue(strpos($response[1]['content_type'], 'text/html') >= 0);
        $this->assertEquals(200, $response[1]['http_code']);

        $data = $response[0];

        if (array_key_exists('user_type', $data)) {
            // Then this should be a completely successful test.
            $expectedMessage = 'User <b>bsmith</b> created successfully';

            $this->assertArrayHasKey('success', $data, "Expected the returned data structure to contain a 'success' property.");
            $this->assertArrayHasKey('user_type', $data, "");
            $this->assertArrayHasKey('message', $data, "Expected the returned data structure to contain a 'message' property.");

            $this->assertTrue($data['success'], "Expected the 'success' property to be true. Received: " . $data['success']);
            $this->assertEquals(1, $data['user_type'], "Expected the 'user_type' property to equal 1. Received: " . $data['user_type']);
            $this->assertEquals($expectedMessage, $data['message'], "Expected the 'message' property to be: $expectedMessage Received: " . $data['message']);
        } elseif (array_key_exists('count', $data)) {
            // If the server running XDMoD does not have sendmail setup then the creation may be successful but this will return an error.
            $expectedMessage = 'Could not execute: /usr/sbin/sendmail -t -i';

            $this->assertArrayHasKey('success', $data, "Expected the returned data structure to contain a 'success' property.");
            $this->assertArrayHasKey('success', $data, "Expected the returned data structure to contain a 'success' property.");
            $this->assertArrayHasKey('count', $data, "Expected the returned data structure to contain a 'count' property.");
            $this->assertArrayHasKey('total', $data, "Expected the returned data structure to contain a 'total' property.");
            $this->assertArrayHasKey('totalCount', $data, "Expected the returned data structure to contain a 'totalCount' property.");
            $this->assertArrayHasKey('results', $data, "Expected the returned data structure to contain a 'results' property.");
            $this->assertArrayHasKey('data', $data, "Expected the returned data structure to contain a 'data' property.");
            $this->assertArrayHasKey('message', $data, "Expected the returned data structure to contain a 'message' property.");

            $this->assertFalse($data['success']);
            $this->assertEquals($expectedMessage, $data['message'], "Expected the 'message' property to be: $expectedMessage Received: " . $data['message']);
        }

        $this->helper->logoutDashboard();
    }

    /**
     * @depends testCreateUser
     */
    public function testModifyUser()
    {
        $this->helper->authenticateDashboard('mgr');

        $users = $this->listUsers();

        $user = array_values(
            array_filter(
                $users,
                function ($item) {
                    return isset($item['username']) && $item['username'] === 'bsmith';
                }
            )
        )[0];
        $this->assertNotNull($user, "Unable to find the user that was previously created.");

        $data = array(
            'operation' => 'update_user',
            'uid' => $user['id'],
            'email_address' => $user['email_address'],
            'acls' => json_encode(
                array(
                    'pi' => array(),
                    'usr' => array()
                )
            ),
            'assigned_user' => 283,
            'institution' => -1,
            'user_type' => 1
        );

        $response = $this->helper->post('controllers/user_admin.php', null, $data);

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);


        $data = $response[0];

        $this->assertArrayHasKey('success', $data, "Expected the returned data structure to contain a 'success' property.");
        $this->assertArrayHasKey('status', $data, "Expected the returned data structure to contain a 'status' property.");
        $this->assertArrayHasKey('username', $data, "Expected the returned data structure to contain a 'username' property.");
        $this->assertArrayHasKey('user_type', $data, "Expected the returned data structure to contain a 'user_type' property.");

        $expectedStatus = 'User <b>bsmith</b> updated successfully';
        $expectedUsername = 'bsmith';
        $expectedUserType = '1';

        $this->assertTrue($data['success'], "Expected the 'success' property to be: true Received: " . $data['success']);
        $this->assertEquals($expectedStatus, $data['status'], "Expected the 'status' property to be: $expectedStatus Received: " . $data['status']);
        $this->assertEquals('bsmith', $data['username'], "Expected the 'username' property to be: $expectedUsername Received: " . $data['username']);
        $this->assertEquals($expectedUserType, $data['user_type'], "Expected the 'user_type' property to be $expectedUserType Received: " . $data['user_type']);
        $this->helper->logoutDashboard();
    }

    /**
     * @depends testModifyUser
     */
    public function testDeleteUser()
    {
        $this->helper->authenticateDashboard('mgr');

        $users = $this->listUsers();
        $user = array_values(
            array_filter(
                $users,
                function ($item) {
                    return isset($item['username']) && $item['username'] === 'bsmith';
                }
            )
        )[0];
        $this->assertNotNull($user, "Unable to find the user that was previously created.");
        $this->assertTrue(isset($user['id']), "Expected the returned user to have an 'id' property.");

        $data = array(
            'operation' => 'delete_user',
            'uid' => $user['id']
        );

        $response = $this->helper->post('controllers/user_admin.php', null, $data);

        $this->assertEquals('application/json', $response[1]['content_type']);
        $this->assertEquals(200, $response[1]['http_code']);

        $data = $response[0];

        $this->assertArrayHasKey('success', $data, "Expected the returned data structure to contain a 'success' property");
        $this->assertArrayHasKey('message', $data, "Expected the returned data structure to contain a 'message' property");

        $expectedMessage = 'User <b>bsmith</b> deleted from the portal';

        $this->assertTrue($data['success'], "Expected the 'success' property to be: true Received: " . $data['success']);
        $this->assertEquals($expectedMessage, $data['message'], "Expected the 'message' property to be: $expectedMessage received: " . $data['message']);

        $this->helper->logoutDashboard();
    }

    /**
     * @dataProvider provideEnumTargetAddresses
     *
     * @param array $options
     * @throws \Exception
     */
    public function testEnumTargetAddresses(array $options)
    {
        $testData = $options['data'];
        $expected = $options['expected'];

        $expectedFile = $expected['file'];
        $expectedFileName = $this->getTestFiles()->getFile('controllers', $expectedFile);
        $expectedContentType = array_key_exists('content_type', $expected) ? $expected['content_type'] : 'text/html; charset=UTF-8';
        $expectedHttpCode = array_key_exists('http_code', $expected) ? $expected['http_code'] : 200;

        $data = array_merge(
            array(
                'operation' => 'enum_target_addresses'
            ),
            $testData
        );

        $helper = $options['helper'];

        $response = $helper->post("internal_dashboard/controllers/mailer.php", null, $data);

        $this->assertEquals($expectedContentType, $response[1]['content_type']);
        $this->assertEquals($expectedHttpCode, $response[1]['http_code']);

        // the current responses are json but specify the text/html; charset=UTF-8;
        // content_type. Where as some of the exception cases specify
        // application/json but do not return valid json. To account for these
        // two cases we just default to attempting to decode the response data
        // and if that fails then just fallback to the full response body as is.
        try {
            $actual = json_decode($response[0], true);
        } catch (\Exception $e) {
            $actual = $response[0];
        }

        $expected = JSON::loadFile($expectedFileName);

        $this->assertEquals($expected, $actual);

        if (isset($options['last'])) {
            $helper->logoutDashboard();
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideEnumTargetAddresses()
    {
        $data = JSON::loadFile($this->getTestFiles()->getFile('controllers', 'enum_target_addresses-update_enum_user_types_and_roles', 'input'));

        $helper = new XdmodTestHelper();
        $helper->authenticateDashboard('mgr');

        foreach($data as $key => $test) {
            foreach($test[0]['data'] as $dataKey => $value) {
                $data[$key][0]['data'][$dataKey] = TestParameterHelper::processParam($value);
            }
            $data[$key][0]['helper'] = $helper;
        }
        $data[count($data) -1][0]['last'] = true;

        return $data;
    }

    public function listUsers($groupFilter = 'all', $roleFilter = 'any', $contextFilter = '')
    {
        $data = array(
            'group_filter' => $groupFilter,
            'role_filter' => $roleFilter,
            'context_filter' => $contextFilter,
            'operation' => 'enum_existing_users'
        );

        $response = $this->helper->post('internal_dashboard/controllers/controller.php', null, $data);
        $this->assertTrue(strpos($response[1]['content_type'], 'text/html') >= 0);
        $this->assertEquals(200, $response[1]['http_code']);

        $data = json_decode($response[0], true);

        $this->assertArrayHasKey('success', $data, "Expected the returned data structure to contain a 'success' property");
        $this->assertArrayHasKey('count', $data, "Expected the returned data structure to contain a 'count' property");
        $this->assertArrayHasKey('response', $data, "Expected the returned data structure to contain a 'response' property.");

        $this->assertTrue(is_array($data['response']), "Expected that the 'response' value would be an array.");
        $this->assertTrue(count($data['response']) > 0, "Expected that there would be one or more items in 'response'");

        return $data['response'];
    }


    public function arrayRecursiveDiff($aArray1, $aArray2)
    {
        $aReturn = array();

        foreach ($aArray1 as $mKey => $mValue) {
            if (array_key_exists($mKey, $aArray2)) {
                if (is_array($mValue)) {
                    $aRecursiveDiff = $this->arrayRecursiveDiff($mValue, $aArray2[$mKey]);
                    if (count($aRecursiveDiff)) {
                        $aReturn[$mKey] = $aRecursiveDiff;
                    }
                } else {
                    if ($mValue != $aArray2[$mKey]) {
                        $aReturn[$mKey] = $mValue;
                    }
                }
            } else {
                $aReturn[$mKey] = $mValue;
            }
        }
        return $aReturn;
    }

    protected function entryExists(array $source, callable $predicate)
    {
        foreach ($source as $key => $value) {
            $found = $predicate($key, $value);
            if ($found === true) {
                return true;
            }
        }
        return false;
    }
}

<?php namespace IntegrationTests\Controllers;

require_once __DIR__ . '/../../bootstrap.php';

use TestHarness\XdmodTestHelper;

class ControllerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var XdmodTestHelper
     */
    protected $helper;

    protected function setUp()
    {
        $this->helper = new XdmodTestHelper();
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

        $actual = $response[0];
        $data = json_decode($actual, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('count', $data);
        $this->assertArrayHasKey('response', $data);

        $this->assertEquals(true, $data['success']);
        $this->assertTrue(
            $data['count'] > 0,
            "Expected the integer property 'count' to have a value greater than 0."
        );
        $this->assertTrue(
            count($data['response']) > 0,
            "Expected the array property 'response' to have 1 or more items, found 0"
        );

        $expected = <<<JSON
{
    "success": true,
    "count": 6,
    "response": [
        {
            "formal_name": "Reed Bunting",
            "id": "3",
            "username": "centerdirector",
            "first_name": "Reed",
            "last_name": "Bunting",
            "email_address": "centerdirector@example.com",
            "user_type": "1",
            "role_type": "User, Center Director",
            "account_is_active": "1",
            "last_logged_in": "0"
        },
        {
            "formal_name": "Turtle Dove",
            "id": "4",
            "username": "centerstaff",
            "first_name": "Turtle",
            "last_name": "Dove",
            "email_address": "centerstaff@example.com",
            "user_type": "1",
            "role_type": "User, Center Staff",
            "account_is_active": "1",
            "last_logged_in": "0"
        },
        {
            "formal_name": "Caspian Tern",
            "id": "5",
            "username": "principal",
            "first_name": "Caspian",
            "last_name": "Tern",
            "email_address": "principal@example.com",
            "user_type": "1",
            "role_type": "User, Principal Investigator",
            "account_is_active": "1",
            "last_logged_in": "0"
        },
        {
            "formal_name": "Public User",
            "id": "1",
            "username": "Public User",
            "first_name": "Public",
            "last_name": "User",
            "email_address": "public@ccr.xdmod.org",
            "user_type": "2",
            "role_type": "Public User",
            "account_is_active": "1",
            "last_logged_in": "0"
        },
        {
            "formal_name": "Admin User",
            "id": "2",
            "username": "admin",
            "first_name": "Admin",
            "last_name": "User",
            "email_address": "admin@localhost",
            "user_type": "2",
            "role_type": "Manager",
            "account_is_active": "1",
            "last_logged_in": "1509029443.7082"
        },
        {
            "formal_name": " Whimbrel",
            "id": "6",
            "username": "normaluser",
            "first_name": "",
            "last_name": "Whimbrel",
            "email_address": "normaluser@example.com",
            "user_type": "1",
            "role_type": "User",
            "account_is_active": "1",
            "last_logged_in": "0"
        }
    ]
}
JSON;

        $expectedJson = json_decode($expected, true);
        $users = $expectedJson['response'];
        uksort(
            $users,
            function ($leftIndex, $rightIndex) use ($users) {
                return $users[$leftIndex]['id'] - $users[$rightIndex]['id'];
            }
        );
        $newUsers = array();
        foreach($users as $key => $value) {
            $newUsers[] = $value;
        }
        // Set the newly ordered users back into the data structure.
        $expectedJson['response'] = $newUsers;
        $diff = $this->arrayRecursiveDiff($expectedJson, $data);
        $success = true;
        array_walk_recursive(
            $diff,
            function ($value, $index, $property) use (&$success) {
                if ($index !== $property) {
                    $success = false;
                }
            },
            'last_logged_in'
        );

        $this->assertTrue($success, "There were other differences besides the expected 'last_logged_in'");

        $this->helper->logoutDashboard();
    }

    public function testEnumUserTypes()
    {
        $expected = '{"success":true,"status":"success","user_types":[{"id":"4","type":"Demo"},{"id":"1","type":"External"},{"id":"5","type":"Federated"},{"id":"2","type":"Internal"},{"id":"3","type":"Testing"}]}';

        $this->helper->authenticateDashboard('mgr');

        $data = array(
            'operation' => 'enum_user_types'
        );

        $response = $this->helper->post('controllers/user_admin.php', null, $data);

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals(200, $response[1]['http_code']);

        $actual = json_encode($response[0]);
        $data = $response[0];

        $this->assertArrayHasKey('success', $data, "Expected the returned data structure to have a 'success' property.");
        $this->assertArrayHasKey('status', $data, "Expected the returned data structure to have a 'status' property.");
        $this->assertArrayHasKey('user_types', $data, "Expected the returned data structure to have a 'user_types' property.");

        $this->assertEquals(true, $data['success'], "Expected the 'success' property to be true, found: ". $data['success']);
        $this->assertEquals('success', $data['status'], "Expected the 'status' property to be 'success', found: " . $data['status']);
        $this->assertTrue(count($data['user_types']) > 0, "Expected there to be more than 0 'user_types' returned.");

        $this->assertEquals($expected, $actual, "Expected the actual results to match the expected results");

        $this->helper->logoutDashboard();
    }

    public function testEnumRoles()
    {
        $expected = json_decode(<<<JSON
        {
    "success": true,
    "status": "success",
    "acls": [
        {
            "acl": "Center Director",
            "acl_id": "cd",
            "include": false,
            "primary": false,
            "requires_center": false
        },
        {
            "acl": "Center Staff",
            "acl_id": "cs",
            "include": false,
            "primary": false,
            "requires_center": false
        },
        {
            "acl": "Manager",
            "acl_id": "mgr",
            "include": false,
            "primary": false,
            "requires_center": false
        },
        {
            "acl": "Principal Investigator",
            "acl_id": "pi",
            "include": false,
            "primary": false,
            "requires_center": false
        },
        {
            "acl": "Public User",
            "acl_id": "pub",
            "include": false,
            "primary": false,
            "requires_center": false
        },
        {
            "acl": "User",
            "acl_id": "usr",
            "include": false,
            "primary": false,
            "requires_center": false
        }
    ]
}
JSON
        , true
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

        $diff = $this->arrayRecursiveDiff($data, $expected);
        $this->assertTrue(
            count($diff) === 0,
            "There were differences in between the actual data and the expected. \nExpected: " . json_encode($expected) . "\nActual: " . json_encode($data)
        );

        $this->helper->logoutDashboard();

    }

    /**
     * @dataProvider listUsersGroupProvider
     * @param int   $group
     * @param array $expected
     */
    public function testListUsers($group, $expected)
    {
        $this->helper->authenticateDashboard('mgr');

        $data = array(
            'operation' => 'list_users',
            'group' => $group
        );

        $response = $this->helper->post('controllers/user_admin.php', null, $data);

        $this->assertEquals($response[1]['content_type'], 'application/json');
        $this->assertEquals(200, $response[1]['http_code']);

        $data = $response[0];

        // Retrieve the users value and ensure that it is sorted in the correct order.
        $users = $data['users'];
        uksort(
            $users,
            function ($leftIndex, $rightIndex) use ($users) {
                return $users[$leftIndex]['id'] - $users[$rightIndex]['id'];
            }
        );
        $newUsers = array();
        foreach($users as $key => $value) {
            $newUsers[] = $value;
        }
        // Set the newly ordered users back into the data structure.
        $data['users'] = $newUsers;

        $diff = $this->arrayRecursiveDiff($expected, $data);
        $success = true;
        array_walk_recursive(
            $diff,
            function ($value, $index, $property) use (&$success) {
                if ($index !== $property) {
                    $success = false;
                }
            },
            'last_logged_in'
        );
        $this->assertTrue($success, "There were other differences besides the expected 'last_logged_in'");

        $this->helper->logoutDashboard();
    }

    public function listUsersGroupProvider()
    {
        return array(
            // External
            array(
                1,
                json_decode(<<<JSON
{
    "success": true,
    "status": "success",
    "users": [
        {
            "id": "3",
            "username": "centerdirector",
            "first_name": "Reed",
            "last_name": "Bunting",
            "account_is_active": "1",
            "last_logged_in": 0
        },
        {
            "id": "4",
            "username": "centerstaff",
            "first_name": "Turtle",
            "last_name": "Dove",
            "account_is_active": "1",
            "last_logged_in": 0
        },
        {
            "id": "5",
            "username": "principal",
            "first_name": "Caspian",
            "last_name": "Tern",
            "account_is_active": "1",
            "last_logged_in": 0
        },
        {
            "id": "6",
            "username": "normaluser",
            "first_name": "",
            "last_name": "Whimbrel",
            "account_is_active": "1",
            "last_logged_in": 0
        }
    ]
}
JSON
                    , true)
            ),
            // Internal
            array(
                2,
                json_decode(<<<JSON
{
    "success": true,
    "status": "success",
    "users": [
        {
            "id": "1",
            "username": "Public User",
            "first_name": "Public",
            "last_name": "User",
            "account_is_active": "1",
            "last_logged_in": 0
        },
        {
            "id": "2",
            "username": "admin",
            "first_name": "Admin",
            "last_name": "User",
            "account_is_active": "1",
            "last_logged_in": 1509035416000
        }
    ]
}
JSON
                    , true
                )
            ),
            // Testing
            array(
                3,
                json_decode('{"success":true,"status":"success","users":[]}', true)
            ),
            // Demo
            array(
                4,
                json_decode('{"success":true,"status":"success","users":[]}', true)
            ),
            // Federated
            array(
                5,
                json_decode('{"success":true,"status":"success","users":[]}', true)
            ),
            array(
                700,
                json_decode('{"success":true,"status":"success","users":[]}', true)
            )
        );
    }

    public function testEnumUserTypesAndRoles()
    {
        $expected = implode(
            '',
            array(
                '{"user_types":[{"id":"1","type":"External","color":"#000000"},{"id":"2","type":"Internal","color":"#0000ff"},{"id":"3","type":"Testing","color":"#008800"},{"id":"4","type":"Demo","color":"#808000"},{"id":"5","type":"Federated","color":"#FFCC00"},{"id":700,"type":"XSEDE","color":"#b914f6"}],"user_roles":[{"description":"Center Director","role_id":"1"},{"description":"Center Staff","role_id":"5"},{"description":"Developer","role_id":"7"},{"descript',
                'ion":"Manager","role_id":"0"},{"description":"Principal Investigator","role_id":"4"},{"description":"Public","role_id":"8"},{"description":"User","role_id":"3"}],"success":true}'
            )
        );

        $this->helper->authenticateDashboard('mgr');

        $data = array(
            'operation' => 'enum_user_types_and_roles'
        );

        $response = $this->helper->post('internal_dashboard/controllers/controller.php', null, $data);

        $this->assertTrue(strpos($response[1]['content_type'], 'text/html') >= 0);
        $this->assertEquals(200, $response[1]['http_code']);

        $actual = $response[0];
        $data = json_decode($actual, true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('user_types', $data);
        $this->assertArrayHasKey('user_roles', $data);

        $this->assertTrue($data['success'], "Expected the 'success' property to be true.");
        $this->assertTrue(count($data['user_types']) > 0, "Expected there to be 1 or more user_types");
        $this->assertTrue(count($data['user_roles']) > 0, "Expected there to be 1 or more user_roles");

        $this->assertEquals($expected, $actual, "Expected the actual results to equal the expected.");

        $this->helper->logoutDashboard();
    }

    /**
     * @dataProvider testSabUsersEnumTgUsersExpected
     * @param $expected
     */
    public function testSabUserEnumTgUsers($expected)
    {
        $this->helper->authenticateDashboard('mgr');

        $data = array(
            'start' => 0,
            'limit' => 300,
            'operation' => 'enum_tg_users',
            'pi_only' => 'n',
            'search_mode' => 'formal_name',
            'userManagement' => 'y',
            'dashboard_mode'=> 1,
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

        $diff = $this->arrayRecursiveDiff($expected, $data);
        $this->assertCount(0, $diff, "Expected there to be no difference between the actual and expected data. Differences: " . json_encode($diff));

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
            $expectedMessage = 'User <b>bsmith<\/b> created successfully';

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
        $this->assertEquals($expectedMessage, $data['message'], "Expected the 'message' property to be: $expectedMessage received: ". $data['message']);

        $this->helper->logoutDashboard();
    }

    public function listUsers($groupFilter = 'all', $roleFilter = 'any', $contextFilter = '')
    {
        $data = array(
            'group_filter' => $groupFilter,
            'role_filter'=> $roleFilter,
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

    public function testSabUsersEnumTgUsersExpected()
    {
        return array(
            array(
                json_decode(<<<JSON
{
    "success": true,
    "status": "success",
    "message": "success",
    "total_user_count": "605",
    "users": [
        {
            "id": 1,
            "person_id": "67",
            "person_name": "Accentor, Alpine"
        },
        {
            "id": 2,
            "person_id": "498",
            "person_name": "Albatross, Black-browed"
        },
        {
            "id": 3,
            "person_id": "603",
            "person_name": "Albatross, Yellow-nosed"
        },
        {
            "id": 4,
            "person_id": "110",
            "person_name": "Amherst's, Lady"
        },
        {
            "id": 5,
            "person_id": "181",
            "person_name": "Auk, Great"
        },
        {
            "id": 6,
            "person_id": "31",
            "person_name": "Auk, Little"
        },
        {
            "id": 7,
            "person_id": "197",
            "person_name": "Avocet, "
        },
        {
            "id": 8,
            "person_id": "236",
            "person_name": "Bee-eater, "
        },
        {
            "id": 9,
            "person_id": "261",
            "person_name": "Bee-eater, Blue-cheeked"
        },
        {
            "id": 10,
            "person_id": "69",
            "person_name": "Bittern, "
        },
        {
            "id": 11,
            "person_id": "121",
            "person_name": "Bittern, American"
        },
        {
            "id": 12,
            "person_id": "526",
            "person_name": "Bittern, Little"
        },
        {
            "id": 13,
            "person_id": "431",
            "person_name": "Black, White-crowned"
        },
        {
            "id": 14,
            "person_id": "418",
            "person_name": "Black, White-winged"
        },
        {
            "id": 15,
            "person_id": "567",
            "person_name": "Black-backed, Great"
        },
        {
            "id": 16,
            "person_id": "190",
            "person_name": "Black-backed, Lesser"
        },
        {
            "id": 17,
            "person_id": "160",
            "person_name": "Black-headed, Great"
        },
        {
            "id": 18,
            "person_id": "70",
            "person_name": "Blackbird, "
        },
        {
            "id": 19,
            "person_id": "422",
            "person_name": "Blackcap, "
        },
        {
            "id": 20,
            "person_id": "568",
            "person_name": "Blue, Great"
        },
        {
            "id": 21,
            "person_id": "525",
            "person_name": "Blue, Siberian"
        },
        {
            "id": 22,
            "person_id": "93",
            "person_name": "Bluetail, Red-flanked"
        },
        {
            "id": 23,
            "person_id": "185",
            "person_name": "Bluethroat, "
        },
        {
            "id": 24,
            "person_id": "309",
            "person_name": "Bobolink, "
        },
        {
            "id": 25,
            "person_id": "154",
            "person_name": "Bonelli's, Eastern"
        },
        {
            "id": 26,
            "person_id": "112",
            "person_name": "Bonelli's, Western"
        },
        {
            "id": 27,
            "person_id": "173",
            "person_name": "Brambling, "
        },
        {
            "id": 28,
            "person_id": "72",
            "person_name": "Bufflehead, "
        },
        {
            "id": 29,
            "person_id": "324",
            "person_name": "Bullfinch, "
        },
        {
            "id": 30,
            "person_id": "183",
            "person_name": "Bunting, Black-faced"
        },
        {
            "id": 31,
            "person_id": "4",
            "person_name": "Bunting, Black-headed"
        },
        {
            "id": 32,
            "person_id": "175",
            "person_name": "Bunting, Chestnut-eared"
        },
        {
            "id": 33,
            "person_id": "206",
            "person_name": "Bunting, Cirl"
        },
        {
            "id": 34,
            "person_id": "155",
            "person_name": "Bunting, Corn"
        },
        {
            "id": 35,
            "person_id": "283",
            "person_name": "Bunting, Cretzschmar's"
        },
        {
            "id": 36,
            "person_id": "209",
            "person_name": "Bunting, Indigo"
        },
        {
            "id": 37,
            "person_id": "285",
            "person_name": "Bunting, Lapland"
        },
        {
            "id": 38,
            "person_id": "512",
            "person_name": "Bunting, Little"
        },
        {
            "id": 39,
            "person_id": "90",
            "person_name": "Bunting, Ortolan"
        },
        {
            "id": 40,
            "person_id": "273",
            "person_name": "Bunting, Pine"
        },
        {
            "id": 41,
            "person_id": "42",
            "person_name": "Bunting, Reed"
        },
        {
            "id": 42,
            "person_id": "106",
            "person_name": "Bunting, Rock"
        },
        {
            "id": 43,
            "person_id": "187",
            "person_name": "Bunting, Rustic"
        },
        {
            "id": 44,
            "person_id": "148",
            "person_name": "Bunting, Snow"
        },
        {
            "id": 45,
            "person_id": "63",
            "person_name": "Bunting, Yellow-breasted"
        },
        {
            "id": 46,
            "person_id": "64",
            "person_name": "Bunting, Yellow-browed"
        },
        {
            "id": 47,
            "person_id": "323",
            "person_name": "Bush, Rufous"
        },
        {
            "id": 48,
            "person_id": "310",
            "person_name": "Bustard, Great"
        },
        {
            "id": 49,
            "person_id": "514",
            "person_name": "Bustard, Little"
        },
        {
            "id": 50,
            "person_id": "109",
            "person_name": "Bustard, Macqueen's"
        },
        {
            "id": 51,
            "person_id": "251",
            "person_name": "Buzzard, "
        },
        {
            "id": 52,
            "person_id": "304",
            "person_name": "Buzzard, Rough-legged"
        },
        {
            "id": 53,
            "person_id": "587",
            "person_name": "Canvasback, "
        },
        {
            "id": 54,
            "person_id": "410",
            "person_name": "Capercaillie, "
        },
        {
            "id": 55,
            "person_id": "493",
            "person_name": "Catbird, Grey"
        },
        {
            "id": 56,
            "person_id": "8",
            "person_name": "Chaffinch, "
        },
        {
            "id": 57,
            "person_id": "311",
            "person_name": "Chiffchaff, "
        },
        {
            "id": 58,
            "person_id": "85",
            "person_name": "Chiffchaff, Iberian"
        },
        {
            "id": 59,
            "person_id": "368",
            "person_name": "Chough, "
        },
        {
            "id": 60,
            "person_id": "9",
            "person_name": "Coot, "
        },
        {
            "id": 61,
            "person_id": "511",
            "person_name": "Coot, American"
        },
        {
            "id": 62,
            "person_id": "238",
            "person_name": "Cormorant, "
        },
        {
            "id": 63,
            "person_id": "257",
            "person_name": "Cormorant, Double-crested"
        },
        {
            "id": 64,
            "person_id": "375",
            "person_name": "Corncrake, "
        },
        {
            "id": 65,
            "person_id": "119",
            "person_name": "Courser, Cream-coloured"
        },
        {
            "id": 66,
            "person_id": "240",
            "person_name": "Cowbird, Brown-headed"
        },
        {
            "id": 67,
            "person_id": "138",
            "person_name": "Crake, Baillon's"
        },
        {
            "id": 68,
            "person_id": "394",
            "person_name": "Crake, Little"
        },
        {
            "id": 69,
            "person_id": "347",
            "person_name": "Crake, Spotted"
        },
        {
            "id": 70,
            "person_id": "12",
            "person_name": "Crane, "
        },
        {
            "id": 71,
            "person_id": "46",
            "person_name": "Crane, Sandhill"
        },
        {
            "id": 72,
            "person_id": "548",
            "person_name": "Crested, Great"
        },
        {
            "id": 73,
            "person_id": "398",
            "person_name": "Crested, Lesser"
        },
        {
            "id": 74,
            "person_id": "531",
            "person_name": "Crossbill, Common"
        },
        {
            "id": 75,
            "person_id": "231",
            "person_name": "Crossbill, Parrot"
        },
        {
            "id": 76,
            "person_id": "445",
            "person_name": "Crossbill, Scottish"
        },
        {
            "id": 77,
            "person_id": "486",
            "person_name": "Crossbill, Two-barred"
        },
        {
            "id": 78,
            "person_id": "224",
            "person_name": "Crow, Carrion"
        },
        {
            "id": 79,
            "person_id": "326",
            "person_name": "Crow, Hooded"
        },
        {
            "id": 80,
            "person_id": "147",
            "person_name": "Crowned, Eastern"
        },
        {
            "id": 81,
            "person_id": "235",
            "person_name": "Cuckoo, "
        },
        {
            "id": 82,
            "person_id": "230",
            "person_name": "Cuckoo, Black-billed"
        },
        {
            "id": 83,
            "person_id": "386",
            "person_name": "Cuckoo, Yellow-billed"
        },
        {
            "id": 84,
            "person_id": "276",
            "person_name": "Curlew, "
        },
        {
            "id": 85,
            "person_id": "113",
            "person_name": "Curlew, Eskimo"
        },
        {
            "id": 86,
            "person_id": "537",
            "person_name": "Curlew, Slender-billed"
        },
        {
            "id": 87,
            "person_id": "467",
            "person_name": "Desert, Asian"
        },
        {
            "id": 88,
            "person_id": "355",
            "person_name": "Dipper, "
        },
        {
            "id": 89,
            "person_id": "449",
            "person_name": "Diver, Black-throated"
        },
        {
            "id": 90,
            "person_id": "487",
            "person_name": "Diver, Pacific"
        },
        {
            "id": 91,
            "person_id": "566",
            "person_name": "Diver, Red-throated"
        },
        {
            "id": 92,
            "person_id": "546",
            "person_name": "Diver, White-billed"
        },
        {
            "id": 93,
            "person_id": "77",
            "person_name": "Dotterel, "
        },
        {
            "id": 94,
            "person_id": "229",
            "person_name": "Dove, Collared"
        },
        {
            "id": 95,
            "person_id": "134",
            "person_name": "Dove, Mourning"
        },
        {
            "id": 96,
            "person_id": "554",
            "person_name": "Dove, Rock"
        },
        {
            "id": 97,
            "person_id": "440",
            "person_name": "Dove, Stock"
        },
        {
            "id": 98,
            "person_id": "57",
            "person_name": "Dove, Turtle"
        },
        {
            "id": 99,
            "person_id": "284",
            "person_name": "Dowitcher, Long-billed"
        },
        {
            "id": 100,
            "person_id": "96",
            "person_name": "Dowitcher, Short-billed"
        },
        {
            "id": 101,
            "person_id": "211",
            "person_name": "Duck, Black"
        },
        {
            "id": 102,
            "person_id": "17",
            "person_name": "Duck, Ferruginous"
        },
        {
            "id": 103,
            "person_id": "180",
            "person_name": "Duck, Harlequin"
        },
        {
            "id": 104,
            "person_id": "298",
            "person_name": "Duck, Long-tailed"
        },
        {
            "id": 105,
            "person_id": "315",
            "person_name": "Duck, Mandarin"
        },
        {
            "id": 106,
            "person_id": "330",
            "person_name": "Duck, Ring-necked"
        },
        {
            "id": 107,
            "person_id": "438",
            "person_name": "Duck, Ruddy"
        },
        {
            "id": 108,
            "person_id": "228",
            "person_name": "Duck, Tufted"
        },
        {
            "id": 109,
            "person_id": "13",
            "person_name": "Dunlin, "
        },
        {
            "id": 110,
            "person_id": "331",
            "person_name": "Dunnock, "
        },
        {
            "id": 111,
            "person_id": "312",
            "person_name": "Eagle, Golden"
        },
        {
            "id": 112,
            "person_id": "335",
            "person_name": "Eagle, Short-toed"
        },
        {
            "id": 113,
            "person_id": "294",
            "person_name": "Eagle, White-tailed"
        },
        {
            "id": 114,
            "person_id": "7",
            "person_name": "Egret, Cattle"
        },
        {
            "id": 115,
            "person_id": "126",
            "person_name": "Egret, Little"
        },
        {
            "id": 116,
            "person_id": "78",
            "person_name": "Egret, Snowy"
        },
        {
            "id": 117,
            "person_id": "411",
            "person_name": "Eider, "
        },
        {
            "id": 118,
            "person_id": "220",
            "person_name": "Eider, King"
        },
        {
            "id": 119,
            "person_id": "365",
            "person_name": "Eider, Steller's"
        },
        {
            "id": 120,
            "person_id": "494",
            "person_name": "Falcon, Amur"
        },
        {
            "id": 121,
            "person_id": "565",
            "person_name": "Falcon, Eleonora's"
        },
        {
            "id": 122,
            "person_id": "466",
            "person_name": "Falcon, Gyr"
        },
        {
            "id": 123,
            "person_id": "547",
            "person_name": "Falcon, Red-footed"
        },
        {
            "id": 124,
            "person_id": "18",
            "person_name": "Fieldfare, "
        },
        {
            "id": 125,
            "person_id": "424",
            "person_name": "Finch, Citril"
        },
        {
            "id": 126,
            "person_id": "136",
            "person_name": "Finch, Trumpeter"
        },
        {
            "id": 127,
            "person_id": "150",
            "person_name": "Firecrest, "
        },
        {
            "id": 128,
            "person_id": "269",
            "person_name": "Flycatcher, Brown"
        },
        {
            "id": 129,
            "person_id": "491",
            "person_name": "Flycatcher, Collared"
        },
        {
            "id": 130,
            "person_id": "191",
            "person_name": "Flycatcher, Pied"
        },
        {
            "id": 131,
            "person_id": "247",
            "person_name": "Flycatcher, Red-breasted"
        },
        {
            "id": 132,
            "person_id": "132",
            "person_name": "Flycatcher, Spotted"
        },
        {
            "id": 133,
            "person_id": "97",
            "person_name": "Flycatcher, Taiga"
        },
        {
            "id": 134,
            "person_id": "505",
            "person_name": "Frigatebird, Ascension"
        },
        {
            "id": 135,
            "person_id": "595",
            "person_name": "Frigatebird, Magnificent"
        },
        {
            "id": 136,
            "person_id": "79",
            "person_name": "Fulmar, "
        },
        {
            "id": 137,
            "person_id": "107",
            "person_name": "Gadwall, "
        },
        {
            "id": 138,
            "person_id": "1",
            "person_name": "Gallinule, Allen's"
        },
        {
            "id": 139,
            "person_id": "557",
            "person_name": "Gannet, "
        },
        {
            "id": 140,
            "person_id": "252",
            "person_name": "Garganey, "
        },
        {
            "id": 141,
            "person_id": "579",
            "person_name": "Godwit, Bar-tailed"
        },
        {
            "id": 142,
            "person_id": "327",
            "person_name": "Godwit, Black-tailed"
        },
        {
            "id": 143,
            "person_id": "262",
            "person_name": "Godwit, Hudsonian"
        },
        {
            "id": 144,
            "person_id": "161",
            "person_name": "Goldcrest, "
        },
        {
            "id": 145,
            "person_id": "454",
            "person_name": "Golden, American"
        },
        {
            "id": 146,
            "person_id": "559",
            "person_name": "Golden, Pacific"
        },
        {
            "id": 147,
            "person_id": "503",
            "person_name": "Goldeneye, "
        },
        {
            "id": 148,
            "person_id": "141",
            "person_name": "Goldeneye, Barrow's"
        },
        {
            "id": 149,
            "person_id": "162",
            "person_name": "Goldfinch, "
        },
        {
            "id": 150,
            "person_id": "303",
            "person_name": "Goosander, "
        },
        {
            "id": 151,
            "person_id": "576",
            "person_name": "Goose, Barnacle"
        },
        {
            "id": 152,
            "person_id": "317",
            "person_name": "Goose, Bean"
        },
        {
            "id": 153,
            "person_id": "592",
            "person_name": "Goose, Brent"
        },
        {
            "id": 154,
            "person_id": "577",
            "person_name": "Goose, Cackling"
        },
        {
            "id": 155,
            "person_id": "380",
            "person_name": "Goose, Canada"
        },
        {
            "id": 156,
            "person_id": "15",
            "person_name": "Goose, Egyptian"
        },
        {
            "id": 157,
            "person_id": "570",
            "person_name": "Goose, Greylag"
        },
        {
            "id": 158,
            "person_id": "332",
            "person_name": "Goose, Pink-footed"
        },
        {
            "id": 159,
            "person_id": "92",
            "person_name": "Goose, Red-breasted"
        },
        {
            "id": 160,
            "person_id": "338",
            "person_name": "Goose, Snow"
        },
        {
            "id": 161,
            "person_id": "321",
            "person_name": "Goose, White-fronted"
        },
        {
            "id": 162,
            "person_id": "210",
            "person_name": "Goshawk, "
        },
        {
            "id": 163,
            "person_id": "432",
            "person_name": "Grasshopper, Pallas's"
        },
        {
            "id": 164,
            "person_id": "192",
            "person_name": "Grebe, Black-necked"
        },
        {
            "id": 165,
            "person_id": "469",
            "person_name": "Grebe, Little"
        },
        {
            "id": 166,
            "person_id": "40",
            "person_name": "Grebe, Pied-billed"
        },
        {
            "id": 167,
            "person_id": "451",
            "person_name": "Grebe, Red-necked"
        },
        {
            "id": 168,
            "person_id": "279",
            "person_name": "Grebe, Slavonian"
        },
        {
            "id": 169,
            "person_id": "376",
            "person_name": "Greenfinch, "
        },
        {
            "id": 170,
            "person_id": "144",
            "person_name": "Greenshank, "
        },
        {
            "id": 171,
            "person_id": "22",
            "person_name": "Grey, Great"
        },
        {
            "id": 172,
            "person_id": "29",
            "person_name": "Grey, Lesser"
        },
        {
            "id": 173,
            "person_id": "54",
            "person_name": "Grey, Southern"
        },
        {
            "id": 174,
            "person_id": "16",
            "person_name": "Grosbeak, Evening"
        },
        {
            "id": 175,
            "person_id": "585",
            "person_name": "Grosbeak, Pine"
        },
        {
            "id": 176,
            "person_id": "389",
            "person_name": "Grosbeak, Rose-breasted"
        },
        {
            "id": 177,
            "person_id": "349",
            "person_name": "Grouse, Black"
        },
        {
            "id": 178,
            "person_id": "600",
            "person_name": "Grouse, Red"
        },
        {
            "id": 179,
            "person_id": "367",
            "person_name": "Guillemot, "
        },
        {
            "id": 180,
            "person_id": "352",
            "person_name": "Guillemot, Black"
        },
        {
            "id": 181,
            "person_id": "465",
            "person_name": "Guillemot, Brunnich's"
        },
        {
            "id": 182,
            "person_id": "343",
            "person_name": "Gull, Audouin's"
        },
        {
            "id": 183,
            "person_id": "171",
            "person_name": "Gull, Black-headed"
        },
        {
            "id": 184,
            "person_id": "485",
            "person_name": "Gull, Bonaparte's"
        },
        {
            "id": 185,
            "person_id": "536",
            "person_name": "Gull, Caspian"
        },
        {
            "id": 186,
            "person_id": "212",
            "person_name": "Gull, Common"
        },
        {
            "id": 187,
            "person_id": "562",
            "person_name": "Gull, Franklin's"
        },
        {
            "id": 188,
            "person_id": "420",
            "person_name": "Gull, Glaucous"
        },
        {
            "id": 189,
            "person_id": "80",
            "person_name": "Gull, Glaucous-winged"
        },
        {
            "id": 190,
            "person_id": "412",
            "person_name": "Gull, Herring"
        },
        {
            "id": 191,
            "person_id": "342",
            "person_name": "Gull, Iceland"
        },
        {
            "id": 192,
            "person_id": "159",
            "person_name": "Gull, Ivory"
        },
        {
            "id": 193,
            "person_id": "481",
            "person_name": "Gull, Laughing"
        },
        {
            "id": 194,
            "person_id": "468",
            "person_name": "Gull, Little"
        },
        {
            "id": 195,
            "person_id": "574",
            "person_name": "Gull, Mediterranean"
        },
        {
            "id": 196,
            "person_id": "44",
            "person_name": "Gull, Ring-billed"
        },
        {
            "id": 197,
            "person_id": "421",
            "person_name": "Gull, Ross's"
        },
        {
            "id": 198,
            "person_id": "471",
            "person_name": "Gull, Sabine's"
        },
        {
            "id": 199,
            "person_id": "178",
            "person_name": "Gull, Slaty-backed"
        },
        {
            "id": 200,
            "person_id": "381",
            "person_name": "Gull, Slender-billed"
        },
        {
            "id": 201,
            "person_id": "98",
            "person_name": "Gull, Yellow-legged"
        },
        {
            "id": 202,
            "person_id": "23",
            "person_name": "Harrier, Hen"
        },
        {
            "id": 203,
            "person_id": "179",
            "person_name": "Harrier, Marsh"
        },
        {
            "id": 204,
            "person_id": "499",
            "person_name": "Harrier, Montagu's"
        },
        {
            "id": 205,
            "person_id": "522",
            "person_name": "Harrier, Northern"
        },
        {
            "id": 206,
            "person_id": "305",
            "person_name": "Harrier, Pallid"
        },
        {
            "id": 207,
            "person_id": "221",
            "person_name": "Hawfinch, "
        },
        {
            "id": 208,
            "person_id": "442",
            "person_name": "Heron, Green"
        },
        {
            "id": 209,
            "person_id": "101",
            "person_name": "Heron, Grey"
        },
        {
            "id": 210,
            "person_id": "307",
            "person_name": "Heron, Purple"
        },
        {
            "id": 211,
            "person_id": "123",
            "person_name": "Heron, Squacco"
        },
        {
            "id": 212,
            "person_id": "413",
            "person_name": "Herring, American"
        },
        {
            "id": 213,
            "person_id": "275",
            "person_name": "Hobby, "
        },
        {
            "id": 214,
            "person_id": "24",
            "person_name": "Honey-buzzard, "
        },
        {
            "id": 215,
            "person_id": "157",
            "person_name": "Hoopoe, "
        },
        {
            "id": 216,
            "person_id": "20",
            "person_name": "Ibis, Glossy"
        },
        {
            "id": 217,
            "person_id": "26",
            "person_name": "Jackdaw, "
        },
        {
            "id": 218,
            "person_id": "202",
            "person_name": "Jay, "
        },
        {
            "id": 219,
            "person_id": "172",
            "person_name": "Junco, Dark-eyed"
        },
        {
            "id": 220,
            "person_id": "158",
            "person_name": "Kestrel, "
        },
        {
            "id": 221,
            "person_id": "383",
            "person_name": "Kestrel, American"
        },
        {
            "id": 222,
            "person_id": "86",
            "person_name": "Kestrel, Lesser"
        },
        {
            "id": 223,
            "person_id": "572",
            "person_name": "Killdeer, "
        },
        {
            "id": 224,
            "person_id": "195",
            "person_name": "Kingfisher, "
        },
        {
            "id": 225,
            "person_id": "131",
            "person_name": "Kingfisher, Belted"
        },
        {
            "id": 226,
            "person_id": "270",
            "person_name": "Kite, Black"
        },
        {
            "id": 227,
            "person_id": "274",
            "person_name": "Kite, Red"
        },
        {
            "id": 228,
            "person_id": "129",
            "person_name": "Kittiwake, "
        },
        {
            "id": 229,
            "person_id": "354",
            "person_name": "Knot, "
        },
        {
            "id": 230,
            "person_id": "539",
            "person_name": "Knot, Great"
        },
        {
            "id": 231,
            "person_id": "28",
            "person_name": "Lapwing, "
        },
        {
            "id": 232,
            "person_id": "364",
            "person_name": "Lark, Bimaculated"
        },
        {
            "id": 233,
            "person_id": "478",
            "person_name": "Lark, Black"
        },
        {
            "id": 234,
            "person_id": "73",
            "person_name": "Lark, Calandra"
        },
        {
            "id": 235,
            "person_id": "333",
            "person_name": "Lark, Crested"
        },
        {
            "id": 236,
            "person_id": "268",
            "person_name": "Lark, Shore"
        },
        {
            "id": 237,
            "person_id": "581",
            "person_name": "Lark, Short-toed"
        },
        {
            "id": 238,
            "person_id": "414",
            "person_name": "Lark, White-winged"
        },
        {
            "id": 239,
            "person_id": "501",
            "person_name": "Linnet, "
        },
        {
            "id": 240,
            "person_id": "214",
            "person_name": "Magpie, "
        },
        {
            "id": 241,
            "person_id": "513",
            "person_name": "Mallard, "
        },
        {
            "id": 242,
            "person_id": "11",
            "person_name": "Martin, Crag"
        },
        {
            "id": 243,
            "person_id": "540",
            "person_name": "Martin, House"
        },
        {
            "id": 244,
            "person_id": "41",
            "person_name": "Martin, Purple"
        },
        {
            "id": 245,
            "person_id": "94",
            "person_name": "Martin, Sand"
        },
        {
            "id": 246,
            "person_id": "544",
            "person_name": "May, Cape"
        },
        {
            "id": 247,
            "person_id": "378",
            "person_name": "Merganser, Hooded"
        },
        {
            "id": 248,
            "person_id": "457",
            "person_name": "Merganser, Red-breasted"
        },
        {
            "id": 249,
            "person_id": "199",
            "person_name": "Merlin, "
        },
        {
            "id": 250,
            "person_id": "399",
            "person_name": "Mockingbird, Northern"
        },
        {
            "id": 251,
            "person_id": "36",
            "person_name": "Moorhen, "
        },
        {
            "id": 252,
            "person_id": "297",
            "person_name": "Murrelet, Ancient"
        },
        {
            "id": 253,
            "person_id": "203",
            "person_name": "Murrelet, Long-billed"
        },
        {
            "id": 254,
            "person_id": "578",
            "person_name": "Night, Black-crowned"
        },
        {
            "id": 255,
            "person_id": "194",
            "person_name": "Nighthawk, Common"
        },
        {
            "id": 256,
            "person_id": "254",
            "person_name": "Nightingale, "
        },
        {
            "id": 257,
            "person_id": "400",
            "person_name": "Nightingale, Thrush"
        },
        {
            "id": 258,
            "person_id": "358",
            "person_name": "Nightjar, "
        },
        {
            "id": 259,
            "person_id": "374",
            "person_name": "Nightjar, Egyptian"
        },
        {
            "id": 260,
            "person_id": "340",
            "person_name": "Nightjar, Red-necked"
        },
        {
            "id": 261,
            "person_id": "217",
            "person_name": "Northern, Great"
        },
        {
            "id": 262,
            "person_id": "363",
            "person_name": "Nutcracker, "
        },
        {
            "id": 263,
            "person_id": "89",
            "person_name": "Nuthatch, "
        },
        {
            "id": 264,
            "person_id": "177",
            "person_name": "Nuthatch, Red-breasted"
        },
        {
            "id": 265,
            "person_id": "256",
            "person_name": "Olivaceous, Eastern"
        },
        {
            "id": 266,
            "person_id": "37",
            "person_name": "Oriole, Baltimore"
        },
        {
            "id": 267,
            "person_id": "115",
            "person_name": "Oriole, Golden"
        },
        {
            "id": 268,
            "person_id": "320",
            "person_name": "Orphean, Western"
        },
        {
            "id": 269,
            "person_id": "601",
            "person_name": "Osprey, "
        },
        {
            "id": 270,
            "person_id": "314",
            "person_name": "Ouzel, Ring"
        },
        {
            "id": 271,
            "person_id": "38",
            "person_name": "Ovenbird, "
        },
        {
            "id": 272,
            "person_id": "120",
            "person_name": "Owl, Barn"
        },
        {
            "id": 273,
            "person_id": "470",
            "person_name": "Owl, Hawk"
        },
        {
            "id": 274,
            "person_id": "322",
            "person_name": "Owl, Little"
        },
        {
            "id": 275,
            "person_id": "156",
            "person_name": "Owl, Long-eared"
        },
        {
            "id": 276,
            "person_id": "250",
            "person_name": "Owl, Scops"
        },
        {
            "id": 277,
            "person_id": "149",
            "person_name": "Owl, Short-eared"
        },
        {
            "id": 278,
            "person_id": "182",
            "person_name": "Owl, Snowy"
        },
        {
            "id": 279,
            "person_id": "549",
            "person_name": "Owl, Tawny"
        },
        {
            "id": 280,
            "person_id": "521",
            "person_name": "Owl, Tengmalm's"
        },
        {
            "id": 281,
            "person_id": "405",
            "person_name": "Oystercatcher, "
        },
        {
            "id": 282,
            "person_id": "492",
            "person_name": "Parakeet, Ring-necked"
        },
        {
            "id": 283,
            "person_id": "281",
            "person_name": "Partridge, Grey"
        },
        {
            "id": 284,
            "person_id": "43",
            "person_name": "Partridge, Red-legged"
        },
        {
            "id": 285,
            "person_id": "124",
            "person_name": "Parula, Northern"
        },
        {
            "id": 286,
            "person_id": "39",
            "person_name": "Peregrine, "
        },
        {
            "id": 287,
            "person_id": "316",
            "person_name": "Petrel, Capped"
        },
        {
            "id": 288,
            "person_id": "91",
            "person_name": "Petrel, Fea's"
        },
        {
            "id": 289,
            "person_id": "301",
            "person_name": "Petrel, Leach's"
        },
        {
            "id": 290,
            "person_id": "596",
            "person_name": "Petrel, Storm"
        },
        {
            "id": 291,
            "person_id": "397",
            "person_name": "Petrel, Swinhoe's"
        },
        {
            "id": 292,
            "person_id": "542",
            "person_name": "Petrel, White-faced"
        },
        {
            "id": 293,
            "person_id": "288",
            "person_name": "Petrel, Wilson's"
        },
        {
            "id": 294,
            "person_id": "538",
            "person_name": "Phalarope, Grey"
        },
        {
            "id": 295,
            "person_id": "216",
            "person_name": "Phalarope, Red-necked"
        },
        {
            "id": 296,
            "person_id": "286",
            "person_name": "Phalarope, Wilson's"
        },
        {
            "id": 297,
            "person_id": "226",
            "person_name": "Pheasant, "
        },
        {
            "id": 298,
            "person_id": "259",
            "person_name": "Pheasant, Golden"
        },
        {
            "id": 299,
            "person_id": "594",
            "person_name": "Phoebe, Eastern"
        },
        {
            "id": 300,
            "person_id": "213",
            "person_name": "Pintail, "
        }
    ]
}
JSON
                    , true
                )
            )
        );
    }
}

<?php

namespace IntegrationTests\Controllers;

use TestHarness\XdmodTestHelper;

class UserAdminTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var XdmodTestHelper
     */
    protected $helper;

    protected function setUp()
    {
        $this->helper = new XdmodTestHelper();
    }

    /**
     * @dataProvider provideCreateUserFails
     * @param array $params
     * @param array $expected
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

    public function provideCreateUserFails()
    {
        $params = array(
            'operation' => 'create_user',
            'account_request_id' => '',
            'first_name' => 'Bob',
            'last_name' => 'Smith',
            'email_address' => 'bsmith@test.com',
            'username' => 'bsmith',
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
            "success"=> false,
            "count" => 0,
            "total" => 0,
            "totalCount" => 0,
            "results" => [],
            "data" => [],
            "message" => ''
        );

        return array(
            array(
                $this->copyAndRemove($params, array('username')),
                $this->copyAndReplace($expected, array('message' => "'username' not specified."))
            ),
            array(
                $this->copyAndReplace($params, array('username' => '')),
                $this->copyAndReplace($expected, array('message' => "Invalid value specified for 'username'."))
            ),
            array(
                $this->copyAndRemove($params, array('first_name')),
                $this->copyAndReplace($expected, array('message' => "'first_name' not specified."))
            ),
            array(
                $this->copyAndReplace($params, array('first_name' => '')),
                $this->copyAndReplace($expected, array('message' => "Invalid value specified for 'first_name'."))
            ),
            array(
                $this->copyAndRemove($params, array('last_name')),
                $this->copyAndReplace($expected, array('message' => "'last_name' not specified."))
            ),
            array(
                $this->copyAndReplace($params, array('last_name' => '')),
                $this->copyAndReplace($expected, array('message' => "Invalid value specified for 'last_name'."))
            ),
            array(
                $this->copyAndRemove($params, array('user_type')),
                $this->copyAndReplace($expected, array('message' => "'user_type' not specified."))
            ),
            array(
                $this->copyAndReplace($params, array('user_type' => '')),
                $this->copyAndReplace($expected, array('message' => "Invalid value specified for 'user_type'."))
            ),
            array(
                $this->copyAndRemove($params, array('email_address')),
                $this->copyAndReplace($expected, array('message' => "'email_address' not specified."))
            ),
            array(
                $this->copyAndReplace($params, array('email_address' => '')),
                $this->copyAndReplace($expected, array('message' => "Failed to assert 'email_address'."))
            ),
            array(
                $this->copyAndRemove($params, array('acls')),
                $this->copyAndReplace($expected, array('message' => "Acl information is required"))
            ),
            array(
                $this->copyAndReplace($params, array('acls' => '')),
                $this->copyAndReplace($expected, array('message' => "Acl information is required"))
            ),
            array(
                $this->copyAndReplace($params, array('acls' => '{"dev": []}')),
                $this->copyAndReplace($expected, array('message' => 'Select another acl other than "Manager" or "Developer"'))
            ),
            array(
                $this->copyAndReplace($params, array('acls' => '{"mgr": []}')),
                $this->copyAndReplace($expected, array('message' => 'Select another acl other than "Manager" or "Developer"'))
            ),
            array(
                $this->copyAndReplace($params, array('acls' => '{"dev": [], "mgr": []}')),
                $this->copyAndReplace($expected, array('message' => 'Select another acl other than "Manager" or "Developer"'))
            )
        );
    }

    private function copyAndReplace(array $data, array $replace)
    {
        $results = $data;
        foreach($replace as $key => $value) {
            if (array_key_exists($key, $results)) {
                $results[$key] = $value;
            }
        }
        return $results;
    }


    private function copyAndRemove(array $data, array $remove)
    {
        $results = $data;
        foreach($remove as $key) {
            if (array_key_exists($key, $results)) {
                unset($results[$key]);
            }
        }
        return $results;
    }


}

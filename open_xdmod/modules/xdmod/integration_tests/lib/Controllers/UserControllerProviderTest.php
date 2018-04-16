<?php

namespace IntegrationTests\Controllers;

use CCR\Json;

class UserControllerProviderTest extends BaseUserAdminTest
{
    /**
     * Tests the UserControllerProvider route: GET /users/current
     *
     * @dataProvider provideGetCurrentUser
     *
     * @param array $options
     * @throws \Exception if unable to authenticate as the specified user role.
     */
    public function testGetCurrentUser(array $options)
    {
        $user = $options['user'];
        $expected = $options['expected'];
        $expectedFile = $expected['file'];
        $expectedHttpCode = $expected['http_code'];
        $expectedContentType = $expected['content_type'];


        if ($user !== 'pub') {
            $this->helper->authenticate($user);
        }

        $response = $this->helper->get('rest/v1/users/current');

        $this->validateResponse($response, $expectedHttpCode, $expectedContentType);

        $expected = JSON::loadFile(
            $this->getTestFiles()->getFile('user_controller', $expectedFile)
        );

        $actual = $response[0];

        $this->assertEquals($expected, $actual);

        if ($user !== 'pub') {
            $this->helper->logout();
        }
    }

    /**
     * @return array|object
     * @throws \Exception
     */
    public function provideGetCurrentUser()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('user_controller', 'get_current_user-8.0.0', 'input')
        );
    }
}

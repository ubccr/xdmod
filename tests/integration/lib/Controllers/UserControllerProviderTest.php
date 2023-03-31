<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use Exception;
use Models\Services\Tokens;
use stdClass;
use TestHarness\Utilities;
use TestHarness\XdmodTestHelper;

class UserControllerProviderTest extends BaseUserAdminTest
{
    /**
     * Tests the UserControllerProvider route: GET /users/current
     *
     * @dataProvider provideGetCurrentUser
     *
     * @param array $options
     * @throws Exception if unable to authenticate as the specified user role.
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
     * @dataProvider provideBaseRoles
     */
    public function testAPITokensCRD($role)
    {
        if ('pub' === $role) {
            foreach (array('get', 'create', 'revoke') as $action) {
                $this->makeTokenRequest($action, 401, 'authentication_error');
            }
        } else {
            $this->helper->authenticate($role);
            $this->helper->delete('rest/users/current/api/token');
            $this->makeTokenRequest('get', 200, 'get_failure');
            $this->makeTokenRequest('create', 200, 'create_success', 'schema');
            $this->makeTokenRequest('create', 200, 'create_failure');
            $this->makeTokenRequest('get', 200, 'get_success', 'schema');
            $this->makeTokenRequest('revoke', 200, 'revoke_success');
            $this->makeTokenRequest('revoke', 200, 'revoke_failure');
            $this->helper->logout();
        }
    }

    /**
     * @return array|object
     * @throws Exception
     */
    public function provideGetCurrentUser()
    {
        return JSON::loadFile(
            $this->getTestFiles()->getFile('user_controller', 'get_current_user-8.0.0', 'input')
        );
    }

    private function makeTokenRequest(
        $action,
        $httpCode,
        $outputFileName,
        $validationType = 'exact'
    ) {
        if ('get' === $action) {
            $verb = 'get';
        } elseif ('create' === $action) {
            $verb = 'post';
        } else {
            $verb = 'delete';
        }
        return $this->makeRequest(
            $this->helper,
            'rest/users/current/api/token',
            $verb,
            null,
            null,
            $httpCode,
            'application/json',
            'integration/rest/user/api_token',
            $outputFileName . ($validationType === 'schema' ? '.spec' : ''),
            $validationType
        );
    }
}

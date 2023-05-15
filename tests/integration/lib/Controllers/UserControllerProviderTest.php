<?php

namespace IntegrationTests\Controllers;

use CCR\Json;
use Exception;
use IntegrationTests\TokenAuthTest;
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
     * Test expected successes and failures at creating, getting, and revoking
     * API tokens.
     *
     * @dataProvider provideBaseRoles
     */
    public function testAPITokensCRD($role)
    {
        if ('pub' === $role) {
            // If the user is not logged in; attempting to get, create, or
            // revoke their tokens should fail.
            foreach (array('get', 'post', 'delete') as $method) {
                $this->makeTokenRequest($method, 'authentication_error');
            }
        } else {
            // Log the user in so we can create, get, and revoke their tokens.
            $this->helper->authenticate($role);
            // Revoke the token in case the user already has one.
            $this->helper->delete('rest/users/current/api/token');
            // Since the user now doesn't have a token, getting it should fail.
            $this->makeTokenRequest('get', 'get_failure');
            // Since the user still doesn't have a token, creating one should
            // succeed.
            $this->makeTokenRequest('post', 'create_success');
            // Now that the user has a token, getting it should succeed.
            $this->makeTokenRequest('get', 'get_success');
            // Since the user still has a token and can only have one at a
            // time, creating a new one should fail.
            $this->makeTokenRequest('post', 'create_failure');
            // Since the user has a token, revoking it should succeed.
            $this->makeTokenRequest('delete', 'revoke_success');
            // Now that the user does not have a token, revoking one should
            // fail.
            $this->makeTokenRequest('delete', 'revoke_failure');
            // We are finished manipulating tokens, so we can log the user out.
            $this->helper->logout();
            // If tokens have been generated for use in other tests, those
            // tokens have now been invalidated and need to be regenerated.
            TokenAuthTest::nullifyTokens();
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

    /**
     * Make an HTTP request that creates, gets, or revokes a token, and make
     * assertions about the JSON response's status code, content type, and
     * body.
     *
     * @param string $method the HTTP method to use: 'get', 'post', or
     *                       'delete'.
     * @param string $key a key in the test artifact output file
     *                    (integration/rest/user/api_token/output/crd.json)
     *                    whose value is the expected response body.
     * @throws Exception if there is an error making the request, loading the
     *                   JSON output file, or running the validation of it.
     */
    private function makeTokenRequest($method, $key) {
        $testGroup = 'integration/rest/user/api_token';
        $fileName = 'crd';
        $input = parent::loadJsonTestArtifact(
            $testGroup,
            $fileName,
            'input'
        );
        $input['method'] = $method;
        $output = parent::loadJsonTestArtifact(
            $testGroup,
            $fileName,
            'output'
        );
        return parent::requestAndValidateJson(
            $this->helper,
            $input,
            $output[$key]
        );
    }
}
